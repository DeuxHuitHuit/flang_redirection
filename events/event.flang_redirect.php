<?php
	/**
	 * Copyright: Deux Huit Huit 2012-2017
	 * License: MIT, see the LICENCE file
	 * 
	 * This class is mostly a copy of https://github.com/klaftertief/language_redirect/blob/master/events/event.language_redirect.php
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	if (@file_exists(EXTENSIONS.'/frontend_localisation/lib/class.FLang.php')) {
		require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLang.php');
	}

	class eventflang_redirect extends Event {

		const ROOTELEMENT = 'flang-redirect';

		public static function about()
		{
			return array(
				'name' => __('Frontend Localisation Redirect'),
				'author' => array(
					'name' => 'Deux Huit Huit',
					'website' => 'https://deuxhuithuit.com',
					'email' => 'open-source (at) deuxhuithuit (dot) com'
				),
				'version' => '2.0.0',
				'release-date' => '2017-01-06',
				'trigger-condition' => ''
			);
		}

		public function load()
		{
			try {
				return $this->__trigger();
			}
			catch (Exception $ex) {
				if (Symphony::Log()) {
					Symphony::Log()->pushExceptionToLog($ex, true);
				}
			}
		}

		public static function documentation()
		{
			return __('This event redirects users to a language version of the page depending on browser settings or cookies.');
		}

		protected function __trigger()
		{
			// Abort if frontend does not exists
			if (!class_exists('Frontend', false)) {
				return;
			}

			// Always define the XSTL variable
			if (!isset(Frontend::Page()->_param['current-language'])) {
				Frontend::Page()->_param['current-language'] = '';
			}

			// Abort if the page is in an erroneous state
			if (Frontend::instance()->getException() != null) {
				return;
			}

			// all supported languages
			$supported_language_codes = FLang::getLangs();

			// only do something when there is a set of supported languages defined
			if (!empty($supported_language_codes)) {
				
				// all languages known
				$all_languages = FLang::getAllLangs();
				
				// main (default) language
				$default_language = FLang::getMainLang();
				// main (default) region
				$default_region = FLang::getMainReg();
				
				// exit early if no default is found
				if (empty($default_language)) {
					return;
				}
				
				// url language
				$url_language =  isset($_REQUEST['fl-language']) ? General::sanitize($_REQUEST['fl-language']) : '';
				$url_region = isset($_REQUEST['fl-region']) ? General::sanitize($_REQUEST['fl-region']) : '';
				$url_language_code = FLang::buildLanguageCode($url_language, $url_region);
				
				$hasUrlLanguage = General::strlen($url_language_code) > 1;
				
				// if we have a url language and this lang is valid
				// no redirect, set current language and region in cookie
				if ($hasUrlLanguage && FLang::validateLangCode($url_language)) {
					
					// set as the current language
					FLang::setLangCode($url_language_code);
					
					// save it in a cookie
					setcookie(
						'flang-redirect',
						$url_language_code,
						time() + TWO_WEEKS,
						'/',
						'.' . Session::getDomain()
					);
				}
				
				// No url language found in url
				// redirect to language-code depending on cookie or browser settings
				else {
					
					// get current path
					$current_path = $hasUrlLanguage ? Frontend::Page()->_param['current-path'] : substr(Frontend::Page()->_param['current-path'], strlen($current_language_code) + 1);
					
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
						// wrap extracting since it may throw
						try {
							// extract language bits (Fixing #6)
							$language_bits = FLang::extractLanguageBits($language);
							$language_code = $language_bits[0];
							// check if language code is a valid symphony language
							if (FLang::validateLangCode($language_code)) {
								$in_browser_languages = true;
								$browser_language = $language_code;
								$browser_region = isset($language_bits[1]) && !empty($default_region) ? $language_bits[1] : null;
								break;
							};
						}
						catch (Exception $ex) {
							// ignore
							continue;
						}
					}
					
					// get the cookie value
					$cookie_language_code = isset($_COOKIE['flang-redirect']) ? General::sanitize($_COOKIE['flang-redirect']) : null;
					$cookie_region = null;
					// validate it
					try {
						$cookie_bits = FLang::extractLanguageBits($cookie_language_code);
						if (!FLang::validateLangCode($cookie_bits[0])) {
							throw new Exception('Invalid cookie lang: ' . $cookie_language_code);
						}
						$cookie_language_code = $cookie_bits[0];
						$cookie_region = isset($cookie_bits[1]) && !empty($default_region) ? $cookie_bits[1] : null;
					}
					catch (Exception $ex) {
						// ignore
						$cookie_language_code = null;
					}

					if (strlen($cookie_language_code) > 0) {
						$language_code = FLang::buildLanguageCode($cookie_language_code, $cookie_region);
					}
					else if ($in_browser_languages) {
						$language_code = FLang::buildLanguageCode($browser_language, $browser_region);
					}
					else {
						$language_code = FLang::buildLanguageCode($default_language, $default_region);
					}
					
					// redirect (with querystring) and exit
					$new_url = '/' . $language_code . '/' . $current_path;
					
					if (substr($new_url, -1) !== '/') {
						$new_url .= '/';
					}
					
					// if query string is longer than 1 (more than only the ? char)
					if (!empty($current_query_string)) {
						if ($current_query_string[0] === '?' && strlen($current_query_string) > 1) {
							$new_url .= $current_query_string;
						} else if (strlen($current_query_string) > 0) {
							$new_url .= '?' . $current_query_string;
						}
					}
					
					// make sure the domain name is present
					// fixes #4
					redirect(Frontend::Page()->_param['root'] . $new_url);
					return true;
				}
				
				// add XML data for this event
				$result = new XMLElement(self::ROOTELEMENT);
				$current_language_xml = new XMLElement('current-language', $all_languages[FLang::getLang()]);
				$current_language_xml->setAttribute('handle', FLang::getLang());
				$result->appendChild($current_language_xml);

				$supported_languages_xml = new XMLElement('supported-languages');
				foreach ($supported_language_codes as $language) {
					$language_code = new XMLElement('item', $all_languages[$language] ? $all_languages[$language] : $language);
					$language_code->setAttribute('handle', $language);
					$supported_languages_xml->appendChild($language_code);
				}
				$result->appendChild($supported_languages_xml);
				
				// param output - this allow DS filtering
				Frontend::Page()->_param['current-language'] = FLang::getLang();

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
		public static function getBrowserLanguages()
		{
			static $languages;
			if (is_array($languages)) {
				return $languages;
			}

			$languages = array();

			if (strlen(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])) < 1) {
				return $languages;
			}
			if (!preg_match_all('/(\w+(?:-\w+)?,?)+(?:;q=(?:\d+\.\d+))?/', preg_replace('/\s+/', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches)) {
				return $languages;
			}

			$priority = 1.0;
			$languages = array();
			foreach ($matches[0] as $def) {
				list($list, $q) = explode(';q=', $def);
				if (!empty($q)) {
					$priority = floatval($q);
				}
				$list = explode(',', $list);
				foreach ($list as $lang) {
					$languages[strtolower($lang)] = $priority;
					$priority -= 0.000000001;
				}
			}
			arsort($languages);
			$languages = array_keys($languages);
			// return list sorted by descending priority, e.g., array('en-gb','en');
			return $languages;
		}
	}
