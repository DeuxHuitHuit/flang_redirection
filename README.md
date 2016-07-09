# Frontend Localisation Redirection

Version 2.0.x    
<https://deuxhuithuit.com>

#### Adds redirection capabilities to Frontend Localisation Extension

## Requierments

- Symphony CMS version 2.5 and up (as of the day of the last release of this extension)
- Frontend localisation extension version 2.7.0
- FLang detection gtlds extension version 2.0.0

## Installation

- Install [frontend_localisation](https://github.com/DeuxHuitHuit/frontend_localisation/)
- Install [flang_detection_gtlds](https://github.com/DeuxHuitHuit/flang_detection_gtlds/)
- Unzip the flang_redirection.zip file
- (re)Name the folder **flang_redirection**
- Put into the extension directory
- Enable/install just like any other extension

*Voila !*

## Usage

This extension provides a event that will redirect the user to the localised page.
You just have to link the event to all pages you want. 

## Credits

This extension is a rewrite of an existing extension that worked great in Symphony 2.2.x, [language_redirect](https://github.com/klaftertief/language_redirect).
Since multilingual support is now offered thought a new extension, I wanted to port the existing features of the 
language redirect in this new environnement.

I wanted to say thanks to [@klaftertief](https://github.com/klaftertief) for his nice work and 
[@vlad-ghita](https://github.com/vlad-ghita) for unifying the localisation support.
