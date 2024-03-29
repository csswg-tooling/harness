<?php
/*******************************************************************************
 *
 *  Copyright © 2011 Hewlett-Packard Development Company, L.P.
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


/**
 * Utility class to remove part of a test file that don't affect results
 */
class NormalizedTest
{
  protected $mURI;
  protected $mContent;

  function __construct($uri)
  {
    $this->mURI = $uri;

    if (file_exists($this->mURI)) {
      $this->mContent = file_get_contents($this->mURI);

      if ($this->mContent) {
        $this->_normalize();
      }
    }
    else {
      $this->mContent = null;
    }
  }


  function getContent()
  {
    return $this->mContent;
  }


  protected function _warning($message)
  {
    fprintf(STDERR, "WARNING: {$message} in {$this->mURI}\n");
  }


  protected function _strip($from, $contains, $to, $replace)
  {
    $index = 0;
    do {
      $start = mb_stripos($this->mContent, $from, $index);
      if (FALSE === $start) {
        return;
      }
      $end = mb_stripos($this->mContent, $to, $start + mb_strlen($from));
      if (FALSE === $end) {
        $this->_warning("end not found '{$from}'-'{$to}'");
        return;
      }
      $index = $end + mb_strlen($to);
      if (0 < mb_strlen($contains)) {
        $testStart = $start + mb_strlen($from);
        $test = mb_substr($this->mContent, $testStart, $end - $testStart);
        if (FALSE !== mb_stripos($test, $contains)) {
          $this->mContent = mb_substr($this->mContent, 0, $start) . $replace . mb_substr($this->mContent, $index);
          $index = $start + mb_strlen($replace);
        }
      }
      else {
        $this->mContent = mb_substr($this->mContent, 0, $start) . $replace . mb_substr($this->mContent, $index);
        $index = $start + mb_strlen($replace);
      }
    } while (1);
  }


  protected function _parseAttributes($attrString)
  {
    $attrs = array();

    $length = mb_strlen($attrString);
    $start = 0;
    $index = -1;
    while (++$index < $length) {
      if (('"' == $attrString[$index]) || ("'" == $attrString[$index])) {
        $quote = $attrString[$index];
        $index = mb_strpos($attrString, $quote, $index + 1);
        if (FALSE === $index) {
          $index = $length;
        }
      }
      elseif (' ' == $attrString[$index]) {
        $attr = trim(mb_substr($attrString, $start, $index - $start));
        if ('' != $attr) {
          $equals = mb_strpos($attr, '=');
          if (FALSE === $equals) {
            $attrs[$attr] = TRUE;
          }
          else {
            $name = trim(mb_substr($attr, 0, $equals));
            $value = trim(mb_substr($attr, ($equals + 1)), "'\" \t\0\x0B\r\n");
            $attrs[$name] = $value;
          }
        }
        while (($index < ($length - 1)) && (' ' == $attrString[$index + 1])) {
          $index++;
        }
        $start = $index;
      }
    }
    if ($start < $index) {
      $attr = trim(mb_substr($attrString, $start, $index - $start));
      if ('' != $attr) {
        $equals = mb_strpos($attr, '=');
        if (FALSE === $equals) {
          $attrs[$attr] = TRUE;
        }
        else {
          $name = trim(mb_substr($attr, 0, $equals));
          $value = trim(mb_substr($attr, ($equals + 1)), "'\" \t\0\x0B\r\n");
          $attrs[$name] = $value;
        }
      }
    }

    return $attrs;
  }

  protected function _attributesToString(Array $attributes)
  {
    $attrs = array();
    foreach ($attributes as $name => $value) {
      if (is_string($value)) {
        if (FALSE === mb_strpos($value, '"')) {
          $attrs[] = $name . '="' . $value . '"';
        }
        else {
          $attrs[] = $name . "='" . $value . "'";
        }
      }
      else {
        $attrs[] = $name;
      }
    }
    return implode(' ', $attrs);
  }


  protected function _sortAttributes($element)
  {
    $index = 0;
    do {
      $search = "<{$element} ";
      $start = mb_stripos($this->mContent, $search, $index);
      if (FALSE === $start) {
        return;
      }
      $end = mb_stripos($this->mContent, '/>', $start + mb_strlen($search));
      if (FALSE === $end) {
        $end = mb_stripos($this->mContent, '>', $start + mb_strlen($search));
        if (FALSE === $end) {
          $this->_warning("end '>' not found for <{$element}");
          return;
        }
        $index = $end + 1;
      }
      else {
        $index = $end + 2;
      }

      $attrStart = $start + mb_strlen($search);
      $attrString = trim(mb_substr($this->mContent, $attrStart, $end - $attrStart));
      $attributes = $this->_parseAttributes($attrString);
      ksort($attributes);
      $attrString = $this->_attributesToString($attributes);
      $this->mContent = mb_substr($this->mContent, 0, $attrStart) . $attrString . mb_substr($this->mContent, $end);

    } while (1);
  }


  protected function _stripPath($element, Array $withAttr, $pathAttr)
  {
    $index = 0;
    do {
      $search = "<{$element} ";
      $start = mb_stripos($this->mContent, $search, $index);
      if (FALSE === $start) {
        return;
      }
      $end = mb_stripos($this->mContent, '/>', $start + mb_strlen($search));
      if (FALSE === $end) {
        $end = mb_stripos($this->mContent, '>', $start + mb_strlen($search));
        if (FALSE === $end) {
          $this->_warning("end '>' not found for <{$element}");
          return;
        }
        $index = $end + 1;
      }
      else {
        $index = $end + 2;
      }

      $attrStart = $start + mb_strlen($search);
      $attrString = trim(mb_substr($this->mContent, $attrStart, $end - $attrStart));
      $attributes = $this->_parseAttributes($attrString);

      if (array_key_exists($pathAttr, $attributes)) {
        foreach ($withAttr as $name => $value) {
          if (array_key_exists($name, $attributes) && (0 == strcasecmp($attributes[$name], $value))) {
            $attributes[$pathAttr] = basename($attributes[$pathAttr]);
            break;
          }
        }
      }

//      ksort($attributes);
      $attrString = $this->_attributesToString($attributes);
      $this->mContent = mb_substr($this->mContent, 0, $attrStart) . $attrString . mb_substr($this->mContent, $end);

    } while (1);
  }

  protected function _normalize()
  {
    $this->_strip('<!DOCTYPE', '', '>', '<==remove==>');
    $this->_strip('<!--', '', '-->', '<==remove==>');
    $this->_strip('<title', '', '</title>', '<==remove==>');
    $this->_strip('<meta ', 'name="assert"', '>', '<==remove==>');
    $this->_strip('<meta ', "name='assert'", '>', '<==remove==>');
    $this->_strip('<link ', 'rel="help"', '>', '<==remove==>');
    $this->_strip('<link ', "rel='help'", '>', '<==remove==>');
    $this->_strip('<link ', 'rel="author"', '>', '<==remove==>');
    $this->_strip('<link ', "rel='author'", '>', '<==remove==>');
    $this->_stripPath('link', array('rel' => 'match'), 'href');
    $this->_stripPath('link', array('rel' => 'mismatch'), 'href');
    $this->_sortAttributes('meta');

    $search = array("ahem",   // XXX use regex for w*<==remove==>{|}
                    "          <==remove==>\r\n",
                    "          <==remove==>\n",
                    "        <==remove==>\r\n",
                    "        <==remove==>\n",
                    "      <==remove==>\r\n",
                    "      <==remove==>\n",
                    "    <==remove==>\r\n",
                    "    <==remove==>\n",
                    "  <==remove==>\r\n",
                    "  <==remove==>\n",
                    "<==remove==>\r\n",
                    "<==remove==>\n",
                    "\t\t\t<==remove==>\r\n",
                    "\t\t\t<==remove==>\n",
                    "\t\t<==remove==>\r\n",
                    "\t\t<==remove==>\n",
                    "\t<==remove==>\r\n",
                    "\t<==remove==>\n",
                    "<==remove==>");
    $replace = array("Ahem");
    $this->mContent = str_ireplace($search, $replace, $this->mContent);
  }
}

?>