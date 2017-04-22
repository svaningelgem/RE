<?php
  class func {
    const CALLING_CONVENTIONS    = '(__thiscall|__cdecl|__stdcall|__fastcall|__clrcall)';
    const REGEX_FUNC             = '~^(public|protected|private):\s*(.*)?\s*'.func::CALLING_CONVENTIONS.'\s+(.*)\((.*)\)(\s*const\s*)?\s*$~iUxms';
    const VTABLE                 = '~^const (.*(?:vbtable|vftable).*)$~iUxms';
    const STATIC_VAR_IN_FUNCTION = '~^(.*)\s+`([^`]*)\'\s*::`?(\d+)\'?\s*::(.*)$~iUxms';
    const STATIC_CLASS_VAR       = '~^(public|protected|private):\s*(.*)?\s*'.func::CALLING_CONVENTIONS.'?\s*((?:class)?\s*[a-z_0-9:]+)(\<(?:[^<>]|(?5))*\>)?::\s*([a-z_0-9]+)\s*$~iUxms';
    const REGEX_EXPORTED_FUNC    = '~^(.*)\s+'.func::CALLING_CONVENTIONS.'\s+(.*)\((.*)\)\s*$~iUxms';

    public $original           = '';
    public $access             = ''; // public, protected, private
    public $is_virtual         = false;
    public $is_static          = false;
    public $is_const           = false;
    public $is_subclass        = false;
    public $className          = array();
    public $functionName       = '';
    public $returnValue        = '';
    public $functionArgs       = '';
    public $type               = '';
    public $calling_convention = '';
    public $static_in_function = '';
    public $static_decimal     = '';
    public $static_name        = '';
    public $export_type        = '';
    public $inheritance        = array();

    function __construct($line) {
#      if ( $line == 'const OmsKeyValueStore::`vftable\'{for `PcsUsageCounter\'}' ) {
#        $BreakHere = true;
#      }

      $this->original = $line;

      if ( preg_match(func::REGEX_FUNC, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'function';
        $this->access             = $matches[1];
        $this->returnValue        = $matches[2];
        $this->calling_convention = $matches[3];
        $this->className          = $matches[4]; // need rework
        $this->functionArgs       = $matches[5];
        $this->is_const           = trim(@$matches[6]) == 'const';
      }
      else if ( preg_match(func::STATIC_VAR_IN_FUNCTION, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'static var in function';
        $this->type               = $matches[1];
        $this->static_in_function = $matches[2];
        $this->static_decimal     = $matches[3];
        $this->static_name        = $matches[4];
      }
      else if ( preg_match(func::STATIC_CLASS_VAR, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);
        
        $this->export_type        = 'static class var';
        $this->access             = $matches[1];
        $this->is_static          = strtolower(substr($matches[2], 0, 7)) == 'static ';
        $this->type               = $this->is_static ? substr($matches[2], 7) : $matches[2];
        $this->calling_convention = $matches[3];
        $this->className          = $matches[4].$matches[5]; // rework?
        $this->static_name        = $matches[6];
      }
      else if ( preg_match(func::VTABLE, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'vtable';
        $this->className          = $matches[1]; // need rework
      }
      else if ( strpos($line, '(') === false ) { // Or it's a static var, or an extern "C" function style
        if ( strpos($line, ' ') === false ) { // extern "C"
          $this->export_type      = 'externC';
          $this->functionName     = $line;
        }
        else {
          $this->export_type      = 'exported var';

          $last_space = strrpos($line, ' ');

          $this->static_name      = substr($line, $last_space+1);
          $this->type             = substr($line, 0, $last_space);
        }
      }
      else if ( preg_match(func::REGEX_EXPORTED_FUNC, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'exported func';
        $this->returnValue        = $matches[1];
        $this->calling_convention = $matches[2];
        $this->functionName       = $matches[3];
        $this->functionArgs       = $matches[4];
      }
      else { // global function exported
        echo "$line\n";
      }

      $this->reworkClassName();
      $this->reworkArgs();
      $this->prepareDependencies();

      # function              : OK
      # static var in function: OK
      # static class var      : OK
      # vtable                : OK
      # externC               : OK
      # exported var          : OK
      # exported func         : OK
#      if ( $this->export_type == 'function' ) {
#        print_r($this);
#      }

      return $this;
    }

    // Split a string while taking care not to break objects|class identifiers
    private static function goodSplit($str, $split) {
      $arr = array_map('trim', explode($split, $str));
      $retVal = array();

      $opened_brackets = 0;
      $total = '';
      foreach( $arr as $el ) {
        $total .= $el . $split;
        $opened_brackets += substr_count($el, '<');
        $opened_brackets -= substr_count($el, '>');
        if ( $opened_brackets == 0 ) {
          $retVal[] = rtrim($total, $split);
          $total = '';
        }
      }

      return $retVal;
    }

    // Convert the basic classname we got into a class + function
    private function reworkClassName() {
      $className = $this->className;

      if ( (is_array($className) && (count($className) == 0)) || (is_string($className) && (strlen($className) == 0)) ) {
        $this->className = array();
        return;
      }

      $arr = self::goodSplit($className, '::');
      // If this array > 2 elements: it's a subclass [example: OmsEvent::Account::Account]
      $this->className = $arr;
      if ( count($this->className) > 1 ) {
        $this->functionName = array_pop($this->className);
        $this->is_subclass  = count($this->className) > 1;
      }
    }

    // Convert the arguments into an array
    private function reworkArgs() {
      $this->functionArgs = array_diff(self::goodSplit($this->functionArgs, ','), array(''));
    }

    # Need to find a better way to handle this!!
    #  --> I can't properly handle:
    #  class OmsGUID const * *
    #  class OmsString *
    const GET_CLASS_1 = '~^(?:class|struct)\s+(.*)(?:\s*\bconst\b(?:\s*(?:&|\*))*)*$~iUxms';
    const GET_CLASS_2 = '~^(?:class|struct)\s+(.*)(?:\s+const\s*)?(?:&|\*)?$~iUxms';
    private static function removeClassIdentifier($subject) {
      if ( $subject == '' ) {
        return $subject;
      }

      $subject = preg_replace('~^(class|struct)\s+~ixms', '', $subject);
      $subject = preg_replace('~((?:\bconst\b)?\s*(&|\*)?\s*)*$~ixms', '', $subject);
      return trim($subject);
    }

    const GET_FROM_BRACKETS = '~\<([^<>]|(?R))*\>~iUxms';
    private static function recursiveBracketMatching($subject, &$dump_in) {
      if ( $subject == '' ) {
        return;
      }

      $dump_in[] = $subject;
      if ( preg_match(self::GET_FROM_BRACKETS, $subject, $matches) > 0 ) {
        foreach( $matches as $match ) {
          $match = self::removeClassIdentifier(substr($match, 1, -1));
          $tmp = self::goodSplit($match, ',');
          foreach( $tmp as $t ) {
            $t = self::removeClassIdentifier($t);
            self::recursiveBracketMatching($t, $dump_in);
          }
        }
      }
    }

    // Try to figure out which classes are needed to be defined before I can use this function
    const DEFAULT_LANGUAGE_CONSTRUCTS = '~^((?:const\s+)?(?:unsigned\s+)?(?:void|char|bool|int|short|long|float|double|__int\d+)\s*(?:const)?(?:\s*(?:\*|&))*)$~iUxms';
    private function prepareDependencies() {
      $dependency = array();
      
      $search = array_merge($this->functionArgs, $this->className);
      $search[] = $this->returnValue;
      $search[] = $this->type;
      if ( ($this->export_type == 'vtable') && (preg_match('~for\s*`\s*(.*)\s*\'~iUxms', $this->functionName, $matches) > 0) ) {
        $search[] = $matches[1];
      }
      foreach( $search as $param ) {
        $param = self::removeClassIdentifier($param);
        self::recursiveBracketMatching($param, $dependency);
      }

      $tmp = array_unique(array_map('trim', $dependency));
      // 1: strip out all default language constructs like int/char/...
      foreach( $tmp as $idx => $el ) {
        if ( preg_match(self::DEFAULT_LANGUAGE_CONSTRUCTS, $el, $matches) > 0 ) {
          unset($tmp[$idx]);
        }
      }

      // 2: strip out the classname
      $total = '';
      foreach( array_reverse($this->className) as $part ) {
        $total = $part . ($total == '' ? '' : '::') . $total;
        $tmp = array_diff($tmp, array($total));
      }
      $this->inheritance = array_values($tmp);
    }
  }

