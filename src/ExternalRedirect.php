<?php
/* ExternalRedirect - MediaWiki extension to allow redirects to external sites.
 * Copyright (C) 2013-2022 Davis Mosenkovs
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\Extension\ExternalRedirect;
use Parser;

$wgHooks['ParserFirstCallInit'][] = 'wfExternalRedirectParserInit';

class ExternalRedirect {

    /*** Default configuration ***/
    // Array with NUMERIC namespace IDs where external redirection should be allowed.
    private $wgExternalRedirectNsIDs = array();

    // Better avoid. Array with page names (see magic word {{FULLPAGENAME}}) where external redirection should be allowed.
    private $wgExternalRedirectPages = array();

    // Avoid or be extremely careful. Use whitelisting approach and very precise expressions. PCRE regex used to determine whether redirection to particular target URL is allowed.
    private $wgExternalRedirectURLRegex = '';

    // Whether to display link to redirection URL (along with error message) in case externalredirect is used where it is not allowed.
    private $wgExternalRedirectDeniedShowURL = false;
    /*****************************/
    
    public static function wfExternalRedirectParserInit( Parser $parser ) {
        $parser->setFunctionHook( 'externalredirect', [ self::class, 'wfExternalRedirectRender' ]);
        return true;
    }
    
    public static function wfExternalRedirectRender($parser, $url = '') {
        global $wgCommandLineMode, $wgExternalRedirectNsIDs, $wgExternalRedirectPages, $wgExternalRedirectURLRegex, $wgExternalRedirectDeniedShowURL;
        $parser->getOutput()->updateCacheExpiry(0);
        if(!wfParseUrl($url) || strpos($url, chr(13))!==false || strpos($url, chr(10))!==false || strpos($url, chr(0))!==false) {
            return wfMessage('externalredirect-invalidurl')->text();
        }
        if((in_array($parser->getTitle()->getNamespace(), $wgExternalRedirectNsIDs, true) || in_array($parser->getTitle()->getPrefixedText(), $wgExternalRedirectPages, true))
          && (is_null($wgExternalRedirectURLRegex) || preg_match($wgExternalRedirectURLRegex, $url)===1)) {
            if($wgCommandLineMode!==true) {
                header('Location: '.$url);
            }
            return wfMessage('externalredirect-text', $url)->text();
        } else {
            return wfMessage('externalredirect-denied')->text().($wgExternalRedirectDeniedShowURL 
              ? ' '.wfMessage('externalredirect-denied-url', $url)->text() : "");
        }
    }
}