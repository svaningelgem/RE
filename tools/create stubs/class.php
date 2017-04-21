<?php
  class func {
    const REGEX_FUNC          = '~^(public|protected|private):\s*(.*)?\s*(__[^\s]+\s)\s*(.*)\((.*)\)(\s*const\s*)?\s*$~iUxms';
    const VTABLE              = '~^const (.*(?:vbtable|vftable).*)$~iUxms';
    const STATIC_VAR          = '~^(.*)\s+`([^`]*)\'\s*::`?(\d+)\'?\s*::(.*)$~iUxms';
    const REGEX_EXPORTED_FUNC = '~^(.*)\s+(__[^\s]+\s)\s*(.*)\((.*)\)\s*$~iUxms';

    private $original           = '';
    private $access             = ''; // public, protected, private
    private $is_virtual         = false;
    private $is_static          = false;
    private $is_const           = false;
    private $className          = '';
    private $functionName       = '';
    private $returnValue        = '';
    private $functionArgs       = '';
    private $type               = '';
    private $calling_convention = '';
    private $static_in_function = '';
    private $static_decimal     = '';
    private $static_name        = '';
    private $export_type        = '';

    static function interpret($line) {
      $obj = new func();
      $obj->original           = $line;
      if ( preg_match(func::REGEX_FUNC, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $obj->export_type        = 'function';
        $obj->access             = $matches[1];
        $obj->returnValue        = $matches[2];
        $obj->calling_convention = $matches[3];
        $obj->className          = $matches[4]; // need rework
        $obj->functionArgs       = $matches[5];
        $obj->is_const           = trim(@$matches[6]) == 'const';
      }
      else if ( preg_match(func::STATIC_VAR, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $obj->export_type        = 'static var';
        $obj->type               = $matches[1];
        $obj->static_in_function = $matches[2];
        $obj->static_decimal     = $matches[3];
        $obj->static_name        = $matches[4];
      }
      else if ( preg_match(func::VTABLE, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $obj->export_type        = 'vtable';
        $obj->className          = $matches[1]; // need rework
      }
      else if ( strpos($line, '(') === false ) { // Or it's a static var, or an extern "C" function style
        if ( strpos($line, ' ') === false ) { // extern "C"
          $obj->export_type      = 'externC';
          $obj->functionName     = $line;
        }
        else {
          $obj->export_type      = 'exported var';

          $last_space = strrpos($line, ' ');

          $obj->static_name      = substr($line, $last_space+1);
          $obj->type             = substr($line, 0, $last_space);
        }
      }
      else if ( preg_match(func::REGEX_EXPORTED_FUNC, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $obj->export_type        = 'exported func';
        $obj->returnValue        = $matches[1];
        $obj->calling_convention = $matches[2];
        $obj->functionName       = $matches[3];
        $obj->functionArgs       = $matches[4];
      }
      else { // global function exported
        echo "Can't interpret: $line\n";
      }

      func::reworkClassName($obj);
      
      return $obj;
    }

    static function reworkClassName(func &$obj) {
      $className = $obj->className;

      if ( $className == '' ) {
        return;
      }

      $arr = array_map('trim', explode('::', $className));

      $opened_brackets = 0;
      $total = '';
      $searching_class = true;
      foreach( $arr as $el ) {
        $total .= $el . '::';
        $opened_brackets += substr_count($el, '<');
        $opened_brackets -= substr_count($el, '>');
        if ( $opened_brackets == 0 ) {
          $obj->{$searching_class ? 'className' : 'functionName'} = rtrim($total, ':');
          $searching_class = false;
          $total = '';
        }
      }
    }
  }

