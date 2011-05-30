<?php
/*******************************************************************************
 *
 *  Copyright © 2007 World Wide Web Consortium
 *  Copyright © 2008-2011 Hewlett-Packard Development Company, L.P. 
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


class Config 
{
  static $gConfigData;
  static $gDebugMode = FALSE;
  static $gLocked = FALSE;
  
  
  static function Get($key, $default = null)
  {
    if (array_key_exists($key, self::$gConfigData)) {
      return self::$gConfigData[$key];
    }
    self::DebugError("Unknown Configuration Key: {$key}");
    if (null !== $default) {
      return $default;
    }
    return FALSE;
  }
  
  static function Set($key, $value)
  {
    if (! self::$gLocked) {
      self::$gConfigData[$key] = $value;
    }
    else {
      self::DebugError('Configuration locked, only override configuration settings in Config.local.php');
    }
  }
  
  static function IsDebugMode()
  {
    return self::$gDebugMode;
  }
  
  static function SetDebugMode($mode)
  {
    self::$gDebugMode = $mode;
  }
  
  static function DebugError($message)
  {
    if (self::IsDebugMode()) {
      trigger_error($message, E_USER_ERROR);
    }
  }
  
  static function _Lock()
  {
    self::$gLocked = TRUE;
  }
  
}

require_once('lib/Config.default.php');
@include_once('lib/Config.local.php');

Config::_Lock();


/**
 * Debug setup
 */
if (Config::IsDebugMode()) {
  error_reporting(E_ALL | E_STRICT);
  assert_options(ASSERT_ACTIVE,     1);
  assert_options(ASSERT_WARNING,    1);
  assert_options(ASSERT_BAIL,       1);
  assert_options(ASSERT_QUIET_EVAL, 0);
  assert_options(ASSERT_CALLBACK,   null);
}
else {
  assert_options(ASSERT_ACTIVE,     0);
  assert_options(ASSERT_WARNING,    0);
  assert_options(ASSERT_BAIL,       0);
  assert_options(ASSERT_QUIET_EVAL, 0);
  assert_options(ASSERT_CALLBACK,   null);
}


?>
