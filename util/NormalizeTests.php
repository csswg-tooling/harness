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

require_once('lib/NormalizedTest.php');


function fixup($inPath, $inPattern, $outPath) 
{
  foreach (glob("{$inPath}/{$inPattern}") as $fileName) {
    $fileInfo = pathinfo($fileName);
    $outFileName = "{$outPath}/{$fileInfo['filename']}.out";  // XXX make safe for PHP < 5.2

    echo "{$fileName} => {$outFileName}\n";
    
    $test = new NormalizedTest($fileName);
    
    file_put_contents($outFileName, $test->getContent());
  }
}


$inPath = $argv[1];
$outPath = $argv[2];

fixup($inPath, "xhtml1/*.xht", $outPath);
fixup($inPath, "html4/*.htm", $outPath);

?>