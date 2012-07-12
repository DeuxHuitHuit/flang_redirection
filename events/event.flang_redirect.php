<?php
	/*
	Copyight: Deux Huit Huit 2012
	License: MIT, see the LICENCE file
	 * 
	This class is mostly a copy of https://github.com/klaftertief/language_redirect/blob/master/events/event.language_redirect.php
	*/
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLang.php');	
	
	Class eventflang_redirect extends Event {

		const ROOTELEMENT = 'language-redirect';
		
		public static function about(){
			return array(
				'name' => __('Frontend Localisation Redirect'),
				'author' => array(
							array(
								'name' => 'Deux Huit Huit',
								'website' => 'http://www.deuxhuithuit.com',
								'email' => 'open-source (at) deuxhuithuit (dot) com'
							),
						),
				'version' => '1.0',
				'release-date' => '2012-07-12',
				'trigger-condition' => '');
		}

		public function load(){
			return $this->__trigger();
		}

		public static function documentation(){
			return __('This event redirects users to a language version of the page depending on browser settings or cookies.');
		}

		protected function __trigger(){
			// all supported languages
			$supported_language_codes = FLang::getLangs();
			// all languages known
			$all_languages = FLang::getAllLangs();

			// main (default) language
			$default_language = FLang::getMainLang();
			
			// url language
			$url_language = General::sanitize($_REQUEST['language']);
			$url_region = General::sanitize($_REQUEST['region']);
			$url_language_code = FLang::buildLanguageCode($url_language, $url_region);
			
			$hasUrlLanguage = isset($url_language_code) && strlen($url_language_code) > 0;
			
			// only do something when there is a set of supported languages defined
			if ( !empty($supported_language_codes)) {
				
				// if we have a url language and this lang is valid
				// no redirect, set current language and region in cookie
				if ($hasUrlLanguage && FLang::validateLangCode($url_language_code)) {
						
					FLang::setLangCode($url_language_code);
						
					$Cookie = new Cookie(__SYM_COOKIE_PREFIX_ . 'language-redirect', TWO_WEEKS, __SYM_COOKIE_PATH__);
					$Cookie->set('language', FLang::getLang());
					$Cookie->set('region', FLang::getReg());
				}
				
				// No url language found
				// redirect to language-code depending on cookie or browser settings
				else {
					$current_path = $hasUrlLanguage ? $this->_env['param']['current-path'] : substr($this->_env['param']['current-path'],strlen($current_language_code)+1);
					
					// get browser value
					$browser_languages = $this->getBrowserLanguages();
					$browser_language = null;
					foreach ($browser_languages as $language) {
						if (FLang::validateLangCode($language)) {
							$in_browser_languages = true;
							$browser_language = $language;
							break;
						};
					}
					
					// get the cookie value
					$Cookie = new Cookie(__SYM_COOKIE_PREFIX_ . 'language-redirect', TWO_WEEKS, __SYM_COOKIE_PATH__);
					$cookie_language_code = $Cookie->get('language');
					
					if (strlen($cookie_language_code) > 0) {
						$language_code = $Cookie->get('region') ? $cookie_language_code.'-'.$Cookie->get('region') : $cookie_language_code;
					}
					elseif ($in_browser_languages) {
						$language_code = $browser_language;
					}
					else { // use default
						$language_code = $default_language;
					}
					
					// redirect and exit
					redirect($this->_env['param']['root'].'/'.$language_code.'/'.$current_path);
					die();
				}
				
				// add XML data for this event
				$result = new XMLElement('language-redirect');
				$current_language_xml = new XMLElement('current-language', $all_languages[$current_language_code] ? $all_languages[$current_language_code] : $current_language_code);
				$current_language_xml->setAttribute('handle', $current_language_code);
				$result->appendChild($current_language_xml);

				$supported_languages_xml = new XMLElement('supported-languages');
				foreach($supported_language_codes as $language) {
					$language_code = new XMLElement('item', $all_languages[$language] ? $all_languages[$language] : $language);
					$language_code->setAttribute('handle', $language);
					$supported_languages_xml->appendChild($language_code);
				}
				$result->appendChild($supported_languages_xml);

				return $result;
			}

			return false;
		}

		/**
		 * Get browser languages
		 *
		 * Return languages accepted by browser as an array sorted by priority
		 * @return array language codes, e. g. 'en'
		 */	 
		public static function getBrowserLanguages() {
			static $languages;
			if(is_array($languages)) return $languages;

			$languages = array();

			if(strlen(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])) < 1) return $languages;
			if(!preg_match_all('/(\w+(?:-\w+)?,?)+(?:;q=(?:\d+\.\d+))?/', preg_replace('/\s+/', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches)) return $languages;

			$priority = 1.0;
			$languages = array();
			foreach($matches[0] as $def){
				list($list, $q) = explode(';q=', $def);
				if(!empty($q)) $priority=floatval($q);
				$list = explode(',', $list);
				foreach($list as $lang){
					$languages[$lang] = $priority;
					$priority -= 0.000000001;
				}
			}
			arsort($languages);
			$languages = array_keys($languages);
			// return list sorted by descending priority, e.g., array('en-gb','en');
			return $languages;
		}

	}