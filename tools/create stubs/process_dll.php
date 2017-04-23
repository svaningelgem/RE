<?php
require_once(__DIR__.'/class.php');
require_once(__DIR__.'/output_stubs.php');

function process_dll($dll) {
  $dll = realpath($dll);
  if ( !$dll ) {
    echo(" !! Can't find this dll.\n\n");
    return false;
  }

  echo "Going to stub '{$dll}'\n";

  $cache_directory    = __DIR__.'/'.pathinfo($dll, PATHINFO_FILENAME).'.'.sha1_file($dll);
  $debug_dumpbin_file = $cache_directory.'/exports.txt';
  $debug_filtered_file = $cache_directory.'/exports.filtered.txt';
  @mkdir($cache_directory, 0755, true);
  if ( !DEBUG || !file_exists($debug_filtered_file) ) {
    exec( escapeshellarg(__DIR__ . '/../dumpbin/dumpbin.exe').' /exports '.escapeshellarg($dll).' 2>&1', $output, $return_var );
    $output = implode("\n", $output);
    file_put_contents($debug_dumpbin_file, $output);

    exec( escapeshellarg(__DIR__ . '/../undname/undname_v14.exe').' '.escapeshellarg($debug_dumpbin_file).' > '.escapeshellarg($debug_filtered_file), $output, $return_var );
  }

  $output = file_get_contents($debug_filtered_file);

  $ordinal_pos = strpos($output, "ordinal");
  $RVA_pos     = strpos($output, "RVA", $ordinal_pos);
  $summary_pos = strrpos($output, "Summary");
  $output = substr($output, $RVA_pos, $summary_pos - $RVA_pos);
  
  preg_match_all("~^\s*\d+\s+[0-9a-z]+\s+[0-9a-z]+\s+(.*)$~iUxms", $output, $matches);

  $demangled_functions = array_map('trim', $matches[1]);

  $functions = array();

  foreach( $demangled_functions as $line ) {
    $functions[] = new func($line);
  }

  output_stubs($cache_directory, $functions);

  return true;
}
