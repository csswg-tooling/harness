<?php
/*******************************************************************************
 *
 *  Copyright © 2014 Hewlett-Packard Development Company, L.P.
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 *  Adapted from the Mobile Test Harness
 *  Copyright © 2007 World Wide Web Consortium
 *  http://dev.w3.org/cvsweb/2007/mobile-test-harness/
 * 
 ******************************************************************************/

require_once('core/URIConverter.php');


class HarnessURIConverter extends URIConverter
{
  /**
   * Convert a rewritten URI path back into baseURI and args array
   * Subclasses override to handle specific pages
   */
  function pathToArgs(Array &$components, Array &$queryArgs)
  {
    $pageKey = null;

    $base = array_shift($components);
    switch ($base) {
      case 'ua':
        $pageKey = 'home';
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');
        
        $this->_addQueryArg($queryArgs, 'ua', $components);
        break;

      case 'suite':
        $pageKey = 'testsuite';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');
        
        $this->_addQueryArg($queryArgs, 'suite', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'ua', $components);
        }
        break;
        
      case 'agent':
        $pageKey = 'select_ua';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');

        $this->_addQueryArg($queryArgs, 'suite', $components);
        break;

      case 'upload':
        $pageKey = 'upload_results';
        $this->_addQueryArg($queryArgs, 'suite', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'ua', $components);
        }
        break;
        
      case 'test':
        $pageKey = 'testcase';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');
        $this->_rekeyQueryArg($queryArgs, 'c', 'testcase'); // XXXX????
        $this->_rekeyQueryArg($queryArgs, 'sec', 'section');
        $this->_rekeyQueryArg($queryArgs, 'i', 'index');
        $this->_rekeyQueryArg($queryArgs, 'ref', 'reference');
        $this->_rekeyQueryArg($queryArgs, 'f', 'format');
        $this->_rekeyQueryArg($queryArgs, 'fl', 'flag');
        $this->_rekeyQueryArg($queryArgs, 'o', 'order');
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');

        $this->_addQueryArg($queryArgs, 'suite', $components);
        $queryArgs['order'] = 1;
        $next = array_shift($components);
        if ('single' == $next) {
          $this->_addQueryArg($queryArgs, 'testcase', $components);
        }
        else {
          if ('spec' == $next) {
            $this->_addQueryArg($queryArgs, 'spec', $components);
            $next = array_shift($components);
          }
          if ('section' == $next) {
            $this->_addQueryArg($queryArgs, 'section', $components);
            $next = array_shift($components);
          }
          if (('alpha' == $next) || ('all' == $next)) {
            $queryArgs['order'] = 0;
            $next = array_shift($components);
          }
          if (('ref' == $next) || ('format' == $next) || ('flag' == $next) || ('ua' == $next)) {
            array_unshift($components, $next);
          }
          else {
            $queryArgs['index'] = $next;
          }
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'ref':     $this->_addQueryArg($queryArgs, 'reference', $components); break;
            case 'format':  $this->_addQueryArg($queryArgs, 'format', $components); break;
            case 'flag':    $this->_addQueryArg($queryArgs, 'flag', $components); break;
            case 'ua':      $this->_addQueryArg($queryArgs, 'ua', $components); break;
          }
        }
        break;
        
      case 'done':
        $pageKey = 'success';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');

        $this->_addQueryArg($queryArgs, 'suite', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'ua', $components);
        }
        break;
        
      case 'review':
        $pageKey = 'review';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');

        $this->_addQueryArg($queryArgs, 'suite', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'ua', $components);
        }
        break;
        
      case 'results':
        $pageKey = 'results';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');
        $this->_rekeyQueryArg($queryArgs, 'sec', 'section');
        $this->_rekeyQueryArg($queryArgs, 'o', 'order');
        $this->_rekeyQueryArg($queryArgs, 'f', 'filter');
        $this->_rekeyQueryArg($queryArgs, 'm', 'date');
        $this->_rekeyQueryArg($queryArgs, 'e', 'engine');
        $this->_rekeyQueryArg($queryArgs, 'v', 'engine_version');
        $this->_rekeyQueryArg($queryArgs, 'b', 'browser');
        $this->_rekeyQueryArg($queryArgs, 'bv', 'browser_version');
        $this->_rekeyQueryArg($queryArgs, 'p', 'platform');
        $this->_rekeyQueryArg($queryArgs, 'pv', 'platform_version');

        $this->_addQueryArg($queryArgs, 'suite', $components);
        $next = array_shift($components);
        if ('grouped' == $next) {
          $queryArgs['order'] = 1;
          $next = array_shift($components);
        }
        if ('section' == $next) {
          $this->_addQueryArg($queryArgs, 'section', $components);
        }
        else {
          array_unshift($components, $next);
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'filter':            $this->_addQueryArg($queryArgs, 'filter', $components); break;
            case 'date':              $this->_addQueryArg($queryArgs, 'date', $components); break;
            case 'engine':            $this->_addQueryArg($queryArgs, 'engine', $components); break;
            case 'engine_version':    $this->_addQueryArg($queryArgs, 'engine_version', $components); break;
            case 'browser':           $this->_addQueryArg($queryArgs, 'browser', $components); break;
            case 'browser_version':   $this->_addQueryArg($queryArgs, 'browser_version', $components); break;
            case 'platform':          $this->_addQueryArg($queryArgs, 'platform', $components); break;
            case 'platform_version':  $this->_addQueryArg($queryArgs, 'platform_version', $components); break;
            case 'ua':                $this->_addQueryArg($queryArgs, 'ua', $components); break;
            default: $queryArgs['testcase'] = $key;
          }
        }
        break;
    
      case 'details':
        $pageKey = 'details';
        $this->_rekeyQueryArg($queryArgs, 's', 'suite');
        $this->_rekeyQueryArg($queryArgs, 'u', 'ua');
        $this->_rekeyQueryArg($queryArgs, 'sec', 'section');
        $this->_rekeyQueryArg($queryArgs, 'o', 'order');
        $this->_rekeyQueryArg($queryArgs, 'm', 'date');
        $this->_rekeyQueryArg($queryArgs, 'e', 'engine');
        $this->_rekeyQueryArg($queryArgs, 'v', 'engine_version');
        $this->_rekeyQueryArg($queryArgs, 'b', 'browser');
        $this->_rekeyQueryArg($queryArgs, 'bv', 'browser_version');
        $this->_rekeyQueryArg($queryArgs, 'p', 'platform');
        $this->_rekeyQueryArg($queryArgs, 'pv', 'platform_version');

        $this->_addQueryArg($queryArgs, 'suite', $components);
        $next = array_shift($components);
        if ('grouped' == $next) {
          $queryArgs['order'] = 1;
          $next = array_shift($components);
        }
        if ('section' == $next) {
          $this->_addQueryArg($queryArgs, 'section', $components);
        }
        else {
          array_unshift($components, $next);
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'date':              $this->_addQueryArg($queryArgs, 'date', $components); break;
            case 'engine':            $this->_addQueryArg($queryArgs, 'engine', $components); break;
            case 'engine_version':    $this->_addQueryArg($queryArgs, 'engine_version', $components); break;
            case 'browser':           $this->_addQueryArg($queryArgs, 'browser', $components); break;
            case 'browser_version':   $this->_addQueryArg($queryArgs, 'browser_version', $components); break;
            case 'platform':          $this->_addQueryArg($queryArgs, 'platform', $components); break;
            case 'platform_version':  $this->_addQueryArg($queryArgs, 'platform_version', $components); break;
            case 'ua':                $this->_addQueryArg($queryArgs, 'ua', $components); break;
            default: $queryArgs['testcase'] = $key;
          }
        }
        break;
        
      case 'status':
        $pageKey = 'status_query';
        break;
        
      default:
        $pageKey = '404';
    }
    if ('404' == $pageKey) {
      array_unshift($components, $base);
    }

    return $pageKey;
  }

  /**
   * Convert query arguments to URI path when rewriting is on
   * Subclasses override to handle specific pages
   *
   * @param string base uri
   * @param array associative array of aurguments
   * @return string uri
   */
  function argsToPath($pageKey, Array &$queryArgs) {
    $uriPath = '';
    
    $keys = explode('.', $pageKey);
    switch (array_shift($keys)) {
      case 'home':
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
        
      case 'testsuite':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'suite');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
        
      case 'select_ua':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'agent');
        break;
        
      case 'upload_results':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'upload');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;

      case 'testcase':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'test');
        if (! $this->_appendURI($uriPath, 'testcase', $queryArgs, 'single')) {
          $this->_appendURI($uriPath, 'spec', $queryArgs, 'spec');
          $this->_appendURI($uriPath, 'section', $queryArgs, 'section');
          $this->_appendURIBool($uriPath, 'order', $queryArgs, 'alpha', 0);
          $this->_appendURI($uriPath, 'index', $queryArgs);
        }
        unset($queryArgs['order']);
        $this->_appendURI($uriPath, 'reference', $queryArgs, 'ref');
        $this->_appendURI($uriPath, 'format', $queryArgs, 'format');
        $this->_appendURI($uriPath, 'flag', $queryArgs, 'flag');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
        
      case 'success':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'done');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
  
      case 'review':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'review');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
        
      case 'results':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'results');
        $this->_appendURIBool($uriPath, 'order', $queryArgs, 'grouped');
        if (! $this->_appendURI($uriPath, 'testcase', $queryArgs)) {
          $this->_appendURI($uriPath, 'section', $queryArgs, 'section');
        }
        $this->_appendURI($uriPath, 'filter', $queryArgs, 'filter');
        $this->_appendURI($uriPath, 'date', $queryArgs, 'date');
        $this->_appendURI($uriPath, 'engine', $queryArgs, 'engine');
        $this->_appendURI($uriPath, 'engine_version', $queryArgs, 'engine_version');
        $this->_appendURI($uriPath, 'browser', $queryArgs, 'browser');
        $this->_appendURI($uriPath, 'browser_version', $queryArgs, 'browser_version');
        $this->_appendURI($uriPath, 'platform', $queryArgs, 'platform');
        $this->_appendURI($uriPath, 'platform_version', $queryArgs, 'platform_version');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
        
      case 'details':
        $this->_appendURI($uriPath, 'suite', $queryArgs, 'details');
        $this->_appendURIBool($uriPath, 'order', $queryArgs, 'grouped');
        if (! $this->_appendURI($uriPath, 'testcase', $queryArgs)) {
          $this->_appendURI($uriPath, 'section', $queryArgs, 'section');
        }
        $this->_appendURI($uriPath, 'date', $queryArgs, 'date');
        $this->_appendURI($uriPath, 'engine', $queryArgs, 'engine');
        $this->_appendURI($uriPath, 'engine_version', $queryArgs, 'engine_version');
        $this->_appendURI($uriPath, 'browser', $queryArgs, 'browser');
        $this->_appendURI($uriPath, 'browser_version', $queryArgs, 'browser_version');
        $this->_appendURI($uriPath, 'platform', $queryArgs, 'platform');
        $this->_appendURI($uriPath, 'platform_version', $queryArgs, 'platform_version');
        $this->_appendURI($uriPath, 'ua', $queryArgs, 'ua');
        break;
        
      case 'status_query':
        $uriPath .= 'status/';
        break;
                
      case '404':
      default:
        break;
    }

    return $uriPath;
  }
}


?>