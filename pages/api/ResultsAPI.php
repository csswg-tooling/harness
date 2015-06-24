<?php
/*******************************************************************************
 *
 *  Copyright © 2015 Hewlett-Packard Development Company, L.P.
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 ******************************************************************************/


require_once('core/pages/APIPage.php');

require_once('lib/Result.php');


/**
 * Class for fetching result status information
 */
class ResultsAPI extends APIPage
{
  static function GetPageKey()
  {
    return 'api.results';
  }
  
  protected $mTestSuite;
  protected $mUserAgent;
  protected $mSpec;
  protected $mSpecType;
  protected $mSection;
  protected $mSections;
  protected $mResults;
  
  /**
   * Expected URL paramaters:
   * 
   */
  function _initPage()
  {
    parent::_initPage();

  }
  
  
  function secureRequired()
  {
    return TRUE;
  }
  
  function crossOriginAllowed()
  {
    return TRUE;
  }

  function getAPIName()
  {
    return 'results';
  }
  
  function getURITemplate()
  {
    return '';
  }
  
  function getOverview()
  {
    return 'Write This...';
  }

  function getArguments()
  {
//    $args['suite'] = $this->_defineArgument('param/suite', '<string>', 'name of test suite');
    
    return $args;
  }
  
  function getReturnValues()
  {
    $values = array();

    return $values;
  }
    
  
  function processCall($version)
  {
    if ('GET' == $this->_getRequestMethod()) {
    }
    elseif ('POST' == $this->_getRequestMethod()) {
      if ($this->mUser->hasRole('tester')) {
        $resultId = intval($this->_postData('result'));

        $result = new Result($resultId);
        if ($result->isValid()) {
          if ('delete' == $this->_postData('action')) {
            $result->ignore();
          }
        }
      }
    }
    return null;
  }
  


}

?>