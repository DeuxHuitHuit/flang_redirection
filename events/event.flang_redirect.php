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

		const ROOTELEMENT = 'flang-redirect';
		
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
				'version' => '1.1',
				'release-date' => '2012-11-13',
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

			// only do something when there is a set of supported languages defined
			if ( !empty($supported_language_codes)) {
				
				// all languages known
				$all_languages = FLang::getAllLangs();
				
				// main (default) language
				$default_language = FLang::getMainLang();
					
				// url language
				$url_language =  isset($_REQUEST['fl-language']) ? General::sanitize($_REQUEST['fl-language']) : '';
				$url_region = isset($_REQUEST['fl-region']) ? General::sanitize($_REQUEST['fl-region']) : '';
				$url_language_code = FLang::buildLanguageCode($url_language, $url_region);
					
				$hasUrlLanguage = strlen($url_language_code) > 1;
				
				// if we have a url language and this lang is valid
				// no redirect, set current language and region in cookie
				if ($hasUrlLanguage && FLang::validateLangCode($url_language_code)) {
					
					// set as the current language
					FLang::setLangCode($url_language_code);
					
					// save it in a cookie
					setcookie('flang-redirect', $url_language_code, mktime() + TWO_WEEKS, '/', '.'.Session::getDomain());
				}
				
				// No url language found in url
				// redirect to language-code depending on cookie or browser settings
				else {
					
					// get current path
					$current_path = $hasUrlLanguage ? Frontend::Page()->_param['current-path'] : substr(Frontend::Page()->_param['current-path'],strlen($current_language_code)+1);
					
					// get current query string from Symphony Frontend Page object
					$current_query_string = Frontend::Page()->_param['current-query-string'];
					
					// un-cdata the querystring
					$current_query_string = str_replace('<![CDATA[', '', $current_query_string);
					$current_query_string = str_replace(']]>',       '', $current_query_string);
					
					// get browser value
					$browser_languages = $this->getBrowserLanguages();
					$browser_language = null;
					$in_browser_languages = false;
					
					foreach ($browser_languages as $language) {
						if (FLang::validateLangCode($language)) {
							$in_browser_languages = true;
							$browser_language = $language;
							break;
						};
					}
					
					// get the cookie value
					$cookie_language_code = General::sanitize($_COOKIE['flang-redirect']);
					
					if (strlen($cookie_language_code) > 0) {
						$language_code = $cookie_language_code;
					}
					else if ($in_browser_languages) {
						$language_code = $browser_language;
					}
					else { // use default
						$language_code = $default_language;
					}
					
					// redirect (with querystring) and exit
					$new_url = '/'.$language_code.'/'.$current_path;
					
					if (substr($new_url, -1)!=='/') { 
						$new_url .= '/';
					}
					
					//var_dump($current_query_string);
					
					// if query string is longer than 1 (more than only the ? char)
					if (!empty($current_query_string) && strlen($current_query_string) > 1) {
						$new_url .= $current_query_string;
					}
					
					//var_dump($new_url);
					$root=Frontend::Page()->_param['root'];
					header("Location: $root$new_url");
					//redirect($new_url);
					die();
				}
				
				// add XML data for this event
				$result = new XMLElement(self::ROOTELEMENT);
				$current_language_xml = new XMLElement('current-language', $all_languages[FLang::getLang()]);
				$current_language_xml->setAttribute('handle', FLang::getLang());
				$result->appendChild($current_language_xml);

				$supported_languages_xml = new XMLElement('supported-languages');
				foreach($supported_language_codes as $language) {
					$language_code = new XMLElement('item', $all_languages[$language] ? $all_languages[$language] : $language);
					$language_code->setAttribute('handle', $language);
					$supported_languages_xml->appendChild($language_code);
				}
				$result->appendChild($supported_languages_xml);

				return $result;
				
			} // end no language set

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
