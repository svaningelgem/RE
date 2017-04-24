<?php

class clazz {
  const DEFAULT_BASE_NAME = '**default';
  /** @var clazz[] **/
  public $inner = array();
  /** @var func[] **/
  public $methods = array();
  /** @var string[] **/
  public $vtable  = array();
  /** @var boolean **/
  public $has_vbtable = false;
  /** @var func[] **/
  public $virtual_functions = array();

  /**
  * put your comment there...
  * 
  * @param func $func
  * @param string[] $class_hierarchy
  * @param clazz $array
  */
  function add_subclass($func, $class_hierarchy, &$array = null) {
    $sub_class = array_shift($class_hierarchy);

    if ( is_null($array) ) {
      $array = $this;
    }

    if ( !isset($array->inner[$sub_class]) ) {
      $array->inner[$sub_class] = new clazz();
    }
    $array = $array->inner[$sub_class];

    if ( count($class_hierarchy) == 0 ) {
      if ( $func->export_type == 'vtable' ) {
        $array->vtable[] = count($func->depending_on) > 0 ? $func->depending_on[0] : self::DEFAULT_BASE_NAME;
      }
      else if ( $func->functionName == '`vbase destructor\'' ) {
        $array->has_vbtable = true;
      }
      else {
        $array->methods[] = $func;
        if ( $func->is_virtual ) {
          $array->virtual_functions[] = $func;
        }
      }
    }
    else {
      $this->add_subclass($func, $class_hierarchy, $array);
    }
  }

  function write_to_file(&$structure, $fpHeader, $fpSource, &$statics_in_function = array(), $className = '', $indent = -1) {
    $front = str_repeat('  ', max(0, $indent));

    if ( $className != '' ) { # first level
      // Try to guess from the methods if this is a class or a struct
      $is_class = true;
      foreach( $this->methods as $func ) {
        if ( ($func->functionName == 'operator=') && (substr($func->returnValue, 0, 7) == 'struct ') ) {
          $is_class = false;
          break;
        }
      }

      fwrite($fpHeader, $front.($is_class ? 'class' : 'struct')." LIBRARY_API {$className}\r\n");
      $has_virtual_inheritance = $this->has_vbtable;
      $has_default_inheritance = in_array(self::DEFAULT_BASE_NAME, $this->vtable);
      $baseClasses = array_diff($this->vtable, array(self::DEFAULT_BASE_NAME));
      $has_inheritance = count($this->vtable) > 0;
/*
      foreach( $this->vtable as $baseClass ) {
        if ( 
      }
*/
      fwrite($fpHeader, $front."{\r\n");
      fwrite($fpHeader, $front."public:\r\n");
    }

    // Write inners
    foreach( $this->inner as $subClassName => $clazz ) {
      $clazz->write_to_file($structure, $fpHeader, $fpSource, $statics_in_function, $subClassName, $indent+1);
    }
    // Write methods
    usort($this->methods, array('func', 'sort_by_access'));
    $current_access = 'public';
    foreach( $this->methods as $func ) {
      $func->write_to_header_file($fpHeader, $indent+1, $current_access);
      $func->write_to_source_file($fpSource);
    }

    if ( $className != '' ) { # first level
      fwrite($fpHeader, $front."};\r\n\r\n");
    }
  }
};

/**
* Generate C++ stubs
* 
* @param string $cache_directory
* @param func[] $functions
*/
function output_stubs($cache_directory, $functions) {
  // structure[className][inner] > clazz
  // structure[className][methods] > methods
  $structure = new clazz();
  // Temporary storage for later easy retrieval
  $static_in_function = array();
  
  // 1: Write out the easy things to write out.
  // 2: group everything together in easy to digest things:
  //   - class structure
  //   - static vars in function
  //   - subclasses
  //   !! Warn for anything else that I might have missed.
  $fp1 = fopen($cache_directory.'/exported.vars.cpp', 'wb');
  $fp2 = fopen($cache_directory.'/externC.cpp', 'wb');
  $fp3 = fopen($cache_directory.'/exported.funcs.cpp', 'wb');
  foreach( $functions as $idx => $func ) {
    if ( $func->export_type == 'exported var' ) {
      fwrite($fp1, func::class_convertor($func->returnValue)." LIBRARY_API {$func->static_name}\r\n");
    }
    else if ( $func->export_type == 'externC' ) {
      fwrite($fp2, "\r\nextern \"C\" LIBRARY_API void {$func->functionName}( ) {\r\n}\r\n");
    }
    else if ( $func->export_type == 'exported func' ) {
      fwrite($fp3, "\r\nLIBRARY_API ".func::class_convertor($func->returnValue)." {$func->functionName}( ".implode(', ', array_map(array('func', 'class_convertor'), $func->functionArgs))." ) {\r\n".
        "  return ".$func::get_default_return($func->returnValue).";\r\n".
        "}\r\n");
    }
    else if ( $func->export_type == 'static var in function' ) {
      $static_in_function[] = $func;
    }
    else if ( count($func->className) == 0 ) {
      echo "Unknown how to classify this:\n";
      print_r($func);
    }
    else {
      $structure->add_subclass($func, $func->className);
    }
  }
  fclose($fp1);
  fclose($fp2);
  fclose($fp3);

  // 2: Now we need to group things together into the class structure.
  $fp4 = fopen($cache_directory.'/classes.hpp', 'wb');
  $fp5 = fopen($cache_directory.'/classes.cpp', 'wb');
  $structure->write_to_file($structure, $fp4, $fp5, $static_in_function);
  fclose($fp4);
  fclose($fp5);

  print_r($structure);
}
