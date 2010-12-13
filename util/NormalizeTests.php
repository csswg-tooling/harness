<?php

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
  
  function sortAttributes($input, $element)
  {
    $index = 0;
    do {
      $start = stripos($input, "<{$element} ", $index);
      if (FALSE === $start) {
        return $input;
      }
      $end = stripos($input, '>', $start + strlen($element) + 2);
      if (FALSE === $end) {
        exit("ERROR: end '>' not found for <{$element}\n");
      }
      $index = $end + 1;
      
      $attrStart = $start + strlen($element) + 2;
      $attrString = substr($input, $attrStart, $end - $attrStart);
      $attributes = explode(' ', $attrString);
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

      $search = array("        <==remove==>\n",
                      "      <==remove==>\n",
                      "    <==remove==>\n",
                      "  <==remove==>\n",
                      "<==remove==>\n",
                      "\t\t<==remove==>\n",
                      "\t<==remove==>\n",
                      "<==remove==>",
                      "ahem");
      $replace = array('',
                       '',
                       '',
                       '',
                       '',
                       '',
                       '',
                       '',
                       "Ahem");
      $contents = str_ireplace($search, $replace, $contents);
      
      file_put_contents($outFileName, $contents);
    }
  }
  
  
  $inPath = $argv[1];
  $outPath = $argv[2];
  
  fixup($inPath, "xhtml1/*.xht", $outPath);
  fixup($inPath, "html4/*.htm", $outPath);
  
?>