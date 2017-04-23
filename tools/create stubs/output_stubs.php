<?php


/**
* Generate C++ stubs
* 
* @param string $cache_directory
* @param func[] $functions
*/
function output_stubs($cache_directory, $functions) {

  // 1: these are the most easy to write out.
  $fp1 = fopen($cache_directory.'/exported.vars.cpp', 'wb');
  $fp2 = fopen($cache_directory.'/externC.cpp', 'wb');
  $fp3 = fopen($cache_directory.'/exported.funcs.cpp', 'wb');
  foreach( $functions as $idx => $func ) {
    if ( $func->export_type == 'exported var' ) {
      fwrite($fp1, func::class_convertor($func->type)." LIBRARY_API {$func->static_name}\r\n");
      unset($functions[$idx]);
    }
    else if ( $func->export_type == 'externC' ) {
      fwrite($fp2, "\r\nextern \"C\" LIBRARY_API void {$func->functionName}( ) {\r\n}\r\n");
      unset($functions[$idx]);
    }
    else if ( $func->export_type == 'exported func' ) {
      fwrite($fp3, "\r\nLIBRARY_API ".func::class_convertor($func->returnValue)." {$func->functionName}( ".implode(', ', array_map(array('func', 'class_convertor'), $func->functionArgs))." ) {\r\n".
        "  return ".$func::get_default_return($func->returnValue).";\r\n".
        "}\r\n");
      unset($functions[$idx]);
    }
  }
  fclose($fp1);
  fclose($fp2);
  fclose($fp3);

  // 2: Now we need to group things together into the class structure.
  // structure[className]['inner'] > class
  // structure[className]['methods'] > funcs
  $structure = array();
  foreach( $functions as $idx => $func ) {
  }
}
