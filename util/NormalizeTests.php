<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
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

define('COMMAND_LINE', TRUE);


  function strip($input, $from, $contains, $to, $replace)
  {
    $index = 0;
    do {
      $start = stripos($input, $from, $index);
      if (FALSE === $start) {
        return $input;
      }
      $end = stripos($input, $to, $start + strlen($from));
      if (FALSE === $end) {
        exit("ERROR: end not found '{$from}'-'{$to}'\n");
      }
      $index = $end + strlen($to);
      if (0 < strlen($contains)) {
        $testStart = $start + strlen($from);
        $test = substr($input, $testStart, $end - $testStart);
        if (FALSE !== stripos($test, $contains)) {
          $input = substr($input, 0, $start) . $replace . substr($input, $index);
          $index = $start + strlen($replace);
        }
      }
      else {
        $input = substr($input, 0, $start) . $replace . substr($input, $index);
        $index = $start + strlen($replace);
      }
    } while (1);
    return $input;
  }
  
  function parseAttributes($attrString)
  {
    $attrs = null;
    
    $length = strlen($attrString);
    $start = 0;
    $index = -1;
    while (++$index < $length) {
      if (('"' == $attrString[$index]) || ("'" == $attrString[$index])) {
        $quote = $attrString[$index];
        $index = strpos($attrString, $quote, $index + 1);
        if (FALSE === $index) {
          $index = $length;
        }
      }
      elseif (' ' == $attrString[$index]) {
        $attrs[] = substr($attrString, $start, $index - $start);
        while (($index < ($length - 1)) && (' ' == $attrString[$index + 1])) {
          $index++;
        }
        $start = $index;
      }
    }
    if ($start < $index) {
      $attr = trim(substr($attrString, $start, $index - $start));
      if ('' != $attr) {
        $attrs[] = $attr;
      }
    }
    
    return $attrs;
  }
  
  function sortAttributes($input, $element)
  {
    $index = 0;
    do {
      $search = "<{$element} ";
      $start = stripos($input, $search, $index);
      if (FALSE === $start) {
        return $input;
      }
      $end = stripos($input, '/>', $start + strlen($search));
      if (FALSE === $end) {
        $end = stripos($input, '>', $start + strlen($search));
        if (FALSE === $end) {
          exit("ERROR: end '>' not found for <{$element}\n");
        }
        $index = $end + 1;
      }
      else {
        $index = $end + 2;
      }
      
      $attrStart = $start + strlen($search);
      $attrString = trim(substr($input, $attrStart, $end - $attrStart));
      $attributes = parseAttributes($attrString);
      sort($attributes, SORT_STRING);
      $attrString = implode(' ', $attributes);
      $input = substr($input, 0, $attrStart) . $attrString . substr($input, $end);
      
    } while (1);
    return $input;
  }
  
  function fixup($inPath, $inPattern, $outPath) 
  {
    foreach (glob("{$inPath}/{$inPattern}") as $fileName) {
      $fileInfo = pathinfo($fileName);
      $outFileName = "{$outPath}/{$fileInfo['filename']}.out";

      echo "{$fileName} => {$outFileName}\n";
      
      $contents = file_get_contents($fileName);
      
      $contents = strip($contents, '<title', '', '</title>', '<==remove==>');
      $contents = strip($contents, '<meta ', 'name="assert"', '>', '<==remove==>');
      $contents = strip($contents, '<link ', 'rel="help"', '>', '<==remove==>');
      $contents = strip($contents, '<link ', 'rel="author"', '>', '<==remove==>');
      $contents = strip($contents, '<link ', 'rel="reference"', '>', '<==remove==>');
      $contents = sortAttributes($contents, 'meta');

      $search = array("ahem",
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
                      "\t\t<==remove==>\r\n",
                      "\t\t<==remove==>\n",
                      "\t<==remove==>\r\n",
                      "\t<==remove==>\n",
                      "<==remove==>");
      $replace = array("Ahem");
      $contents = str_ireplace($search, $replace, $contents);
      
      file_put_contents($outFileName, $contents);
    }
  }
  
  
  $inPath = $argv[1];
  $outPath = $argv[2];
  
  fixup($inPath, "xhtml1/*.xht", $outPath);
  fixup($inPath, "html4/*.htm", $outPath);

?>