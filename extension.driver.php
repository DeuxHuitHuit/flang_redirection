<?php
	/*
	Copyight: Deux Huit Huit 2012
	License: MIT, see the LICENCE file
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	Class extension_flang_redirection extends Extension{

		public function install(){
			if (!class_exists('FLang', false)) {
				throw new Exception('The class Flang doesn\'t exist, you need to install the extension `Frontend Localisation` to solve this issue');
			}
		}

		public function update($previousVersion=false) {
			return true;
		}

		public function uninstall(){
			return true;
		}

	}
