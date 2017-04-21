<?php
require_once(__DIR__.'/class.php');

function process_dll($dll) {
  $dll = realpath($dll);
  if ( !$dll ) {
    echo(" !! Can't find this dll.\n\n");
    return false;
  }

  echo "Going to stub '{$dll}'\n";

  $debug_dumpbin_file = __DIR__.'/'.pathinfo($dll, PATHINFO_FILENAME).'.'.sha1_file($dll).'.exports';
  if ( DEBUG && file_exists($debug_dumpbin_file) ) {
    $output = file_get_contents($debug_dumpbin_file);
  }
  else {
    exec( escapeshellarg(__DIR__ . '/../dumpbin/dumpbin.exe').' /exports '.escapeshellarg($dll).' | '.escapeshellarg(__DIR__.'/../vc++filt/vc++filt').' 2>&1', $output, $return_var );
    $output = implode("\n", $output);

    file_put_contents($debug_dumpbin_file, $output);
  }

  $ordinal_pos = strpos($output, "ordinal");
  $RVA_pos     = strpos($output, "RVA", $ordinal_pos);
  $summary_pos = strrpos($output, "Summary");
  $output = substr($output, $RVA_pos, $summary_pos - $RVA_pos);
  
  preg_match_all("~^\s*\d+\s+[0-9a-z]+\s+[0-9a-z]+\s+(.*)$~iUxms", $output, $matches);

  $demangled_functions = $matches[1];

  $functions = array();

  foreach( $demangled_functions as $line ) {
    $functions[] = func::interpret($line);
  }

  print_r($functions);

  return true;
}
