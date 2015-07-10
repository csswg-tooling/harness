<?php
/*******************************************************************************
 *
 *  Copyright © 2008-2015 Hewlett-Packard Development Company, L.P. 
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


require_once("lib/HarnessPage.php");
require_once("lib/Sections.php");
require_once("lib/TestCases.php");
require_once("lib/TestCase.php");

require_once("modules/testsuite/TestSuite.php");
require_once("modules/useragent/UserAgent.php");


/**
 * Class for generateing the page to select which tests will be run
 */
class UploadResultsPage extends HarnessPage
{  
  protected $mTestCases;
  protected $mDate;
  protected $mFileName;
  protected $mImportStatus;
  protected $mImportResult;
  protected $mErrorMessages;
  protected $mErrorClasses;

  static function GetPageKey()
  {
    return 'upload_results';
  }


  protected function _initPage()
  {
    parent::_initPage();

    $this->mDate = null;
    $this->mFileName = null;
    $this->mImportStatus = 0;
    $this->mImportResult = null;
    $this->mErrorMessages = array();
    $this->mErrorClasses = array();
    
    if ($this->mTestSuite) {
      if (('Upload' == $this->_postData('action') && $this->mUser->hasRole('tester'))) {
        $this->mDate = $this->_requestData('date');

        if (isset($_FILES['file'])) {
          $this->mFileName = $_FILES['file']['name'];
        }

        if ($this->mDate) {
          $now = new DateTime(null, self::_GetServerTimeZone());
          $this->mDate = trim($this->mDate);
          $dateTime = DateTime::createFromFormat("Y-m-d H:i:s O", $this->mDate, $this->mPreferences->getTimeZone());
          if (! $dateTime) {
            $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $this->mDate, $this->mPreferences->getTimeZone());
          }
          if (! $dateTime) {
            $dateTime = DateTime::createFromFormat("Y-m-d", $this->mDate, $this->mPreferences->getTimeZone());
          }
          if (! $dateTime) {
            $this->_addError('Date must be provided in the form: yyyy-mm-dd hh:mm:ss (+/-hhmm).', 'date');
          }
          elseif ($now < $dateTime) {
            $this->_addError('Date can not be in the future.', 'date');
          }
        }
        else {
          $this->_addError('Date must be provided.', 'date');
        }

        if (! $this->mFileName) {
          $this->_addError('File must be selected.', 'file');
        }

        if ((! $this->mErrorMessages) && $this->mFileName) {
          $this->mImportStatus = $this->_importData($_FILES['file']['tmp_name'], $dateTime);
        }
      }

      $this->mTestCases = new TestCases($this->mTestSuite);
      $this->mSubmitData['suite'] = $this->mTestSuite->getName();
    }
    
    if (! $this->mUserAgent->isActualUA()) {
      $this->mSubmitData['ua'] = $this->mUserAgent->getId();
    }
    
  }
  
  function getRedirectURI()
  {
    if (! $this->mUser->hasRole('tester')) {
      return $this->buildPageURI('home');
    }
    return null;
  }
  
  
  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Run Tests";
      $args['suite'] = ($this->mTestSuite ? $this->mTestSuite->getName() : '');
      $args['ua'] = $this->mUserAgent->getId();

      $uri = $this->buildPageURI('testsuite', $args);
      $uris[] = compact('title', 'uri');
      
      $title = "Batch Upload";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }
  
  
  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    parent::writeHeadStyle();
    
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.testsuite'));
  }


  function _importData($filePath, $dateTime)
  {
    $dateTime = clone $dateTime;
    $dateTime->setTimezone(self::_GetUTCTimeZone());

    $args[] = $filePath;
    $args[] = $this->mTestSuite->getName();
    $args[] = $dateTime->format('Y-m-d\TH:i:s');
    $args[] = $this->mUser->getName();
    $args[] = $this->mUserAgent->getUAString();

    $this->mUserAgent->update();

    list($status, $output) = ShellProcess::ExecuteSynchnonousFor($this->mUser, $this->_GetInstallPath(),
                                                                 'python/ImportImplementationReport.py', null, $args);

    $this->mImportResult = implode("\n", $output);
    return $status;
  }


  function writeUASelect()
  {
    $this->openElement('p', array('class' => 'ua'));
    $this->addTextContent("You are about upload tests for the following user agent: ");
    
    if ($this->mUserAgent->isActualUA()) {
      $this->addAbbrElement($this->mUserAgent->getUAString(), null, $this->mUserAgent->getDescription());

      $args = $this->_uriData();
      $uri = $this->buildPageURI('select_ua', $args);
      
      $this->openElement('span', null, FALSE);
      $this->addTextContent(' (');
      $this->addHyperLink($uri, null, 'Change');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    else {
      $this->addAbbrElement($this->mUserAgent->getUAString(),
                            array('class' => 'other'), 
                            $this->mUserAgent->getDescription());

      $args = $this->_uriData();
      unset($args['ua']);
      $uri = $this->buildPageURI('upload_results', $args);
      $this->openElement('span', null, FALSE);
      $this->addTextContent(' (');
      $this->addHyperLink($uri, null, 'Reset');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    $this->closeElement('p');
  }

  function writeImportResult()
  {
    if ($this->mImportResult) {
      $this->addElement('p', array('class' => 'import_result'), (0 == $this->mImportStatus) ? 'Import Successful' : 'Import Failed');
      $this->addElement('pre', null, $this->mImportResult, TRUE, FALSE);
    }
  }

  
  protected function _addError($message, $class)
  {
    $this->mErrorMessages[] = $message;
    $this->mErrorClasses[] = $class;
  }

  protected function _rowClass($class)
  {
    if (in_array($class, $this->mErrorClasses)) {
      return array('class' => $class . ' error');
    }
    return array('class' => $class);
  }


  function writeUploadInformation()
  {
    if ($this->mTestCases->getCount()) {
      $this->addElement('p', null, "The {$this->mTestSuite->getTitle()} contains {$this->mTestCases->getCount()} test cases. ");
      $this->openElement('p', null, FALSE);
      $this->addTextContent("The uploaded data must match the format of the ");
      $uri = $this->_CombinePath($this->mTestSuite->getURI(), 'implementation-report-TEMPLATE.data');
      $this->addHyperLink($uri, null, 'implementation report template');
      $this->addTextContent(".");
      $this->closeElement('p');
    }
  }

  function writeUploadControls()
  {
    if ($this->mTestCases->getCount()) {
      $this->openFormElement('', 'post', null, array('enctype' => 'multipart/form-data'));

      $this->openElement('fieldset');
      $this->addElement('legend', null, 'Result Data');

      if ($this->mErrorMessages) {
        $this->addElement('p', array('class' => 'error'), implode(' ', $this->mErrorMessages));
      }

      $this->openElement('table');
      $this->openElement('tbody');

      $this->openElement('tr', $this->_rowClass('date'));
      $this->openElement('th');
      $this->addLabelElement('date', 'Report Date: ');
      $this->closeElement('th');
      $this->openElement('td');
      $this->addInputElement('text', 'date', $this->mDate, 'date', array('size' => 40));
      $this->closeElement('td');
      $this->addElement('td', null, "(yyyy-mm-dd hh:mm:ss) In {$this->mPreferences->getTimeZone()->getName()} time zone");
      $this->closeElement('tr');

      $this->openElement('tr', $this->_rowClass('file'));
      $this->openElement('th');
      $this->addLabelElement('date', 'Upload File: ');
      $this->closeElement('th');
      $this->openElement('td');
      $this->addInputElement('file', 'file', $this->mFileName);
      $this->closeElement('td');
      $this->closeElement('tr');

      $this->closeElement('tbody');
      $this->closeElement('table');

      $this->closeElement('fieldset');

      $this->addInputElement('submit', 'action', 'Upload');
      $this->closeElement('form');
    }
  }
  
  
  function writeBodyContent()
  {
    $this->openElement('div', array('class' => 'body'));

    if ((! $this->mTestSuite) || (! $this->mTestSuite->isValid())) {
      $this->addElement('p', null, 'Unknown test suite.');
    }
    elseif (! $this->mTestCases->getCount()) {
      $this->addElement('p', null, "The {$this->mTestSuite->getTitle()} does not contain any test cases.");
    }
    elseif (! $this->mUser->hasRole('tester')) {
      $this->addElement('p', null, "You must be logged in to an account with upload rights to access this page.");
    }
    else {
      $this->writeUASelect();

      $this->writeUploadInformation();

      $this->writeImportResult();
      
      $this->writeUploadControls();
    }

    $this->closeElement('div');
  }
}

?>