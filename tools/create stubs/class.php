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
    public $depending_on       = array();

    function __construct($line) {
#      if ( $line == 'const OmsKeyValueStore::`vftable\'{for `PcsUsageCounter\'}' ) {
#        $BreakHere = true;
#      }

      $this->original = $line;

      if ( preg_match(func::REGEX_FUNC, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'function';
        $this->access             = $matches[1];
        $this->returnValue        = trim(preg_replace('~\b(virtual|static)\b~iUxms', '', $matches[2]));
        $this->calling_convention = $matches[3];
        $this->className          = $matches[4]; // need rework
        $this->functionArgs       = $matches[5];
        $this->is_const           = trim(@$matches[6]) == 'const';
        $this->is_static          = preg_match('~\bstatic\b~iUxms', $matches[2]) > 0;
        $this->is_virtual         = preg_match('~\bvirtual\b~iUxms', $matches[2]) > 0;
      }
      else if ( preg_match(func::STATIC_VAR_IN_FUNCTION, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'static var in function';
        $this->returnValue        = $matches[1];
        $this->static_in_function = $matches[2];
        $this->static_decimal     = $matches[3];
        $this->static_name        = $matches[4];
      }
      else if ( preg_match(func::STATIC_CLASS_VAR, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);
        
        $this->export_type        = 'static class var';
        $this->access             = $matches[1];
        $this->is_static          = preg_match('~\bstatic\b~iUxms', $matches[2]) > 0;
        $this->returnValue        = trim(preg_replace('~\b(virtual|static)\b~iUxms', '', $matches[2]));
        $this->calling_convention = $matches[3];
        $this->className          = $matches[4].$matches[5]; // rework?
        $this->static_name        = $matches[6];
      }
      else if ( preg_match(func::VTABLE, $line, $matches) > 0 ) {
        $matches = array_map('trim', $matches);

        $this->export_type        = 'vtable';
        $this->access             = 'public';
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
          $this->returnValue      = substr($line, 0, $last_space);
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
        echo "!! Can't understand: $line\n";
      }

      $this->reworkClassName();
      $this->reworkArgs();
      $this->prepareDependencies();

      # function              : OK
      # static var in function: OK
      # static class var      : OK
      # vtable                : OK
      # externC               : OK -- handled
      # exported var          : OK -- handled
      # exported func         : OK -- handled

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
      $this->depending_on = array_values($tmp);
    }

    static public function class_convertor($type) {
      return str_replace('class std::basic_string<char,struct std::char_traits<char>,class std::allocator<char> >', 'std::string', $type);
    }

    static public function get_default_return($type) {
      if ( substr($type, -1) == '*' ) {
        return 'NULL';
      }
      else if ( in_array($type, array('int', 'char', 'short', 'long', 'double')) ) {
        return '0';
      }
      else {
        echo " --> unrecognized type: '{$type}'\n";
        exit();
      }
    }

    static public function sort_by_access(func $a, func $b) {
      if ( $a->access == $b->access ) {
        return 0;
      }
      else if ( $a->access == 'public' ) {
        return -1; # B is prot|priv
      }
      else if ( $b->access == 'public' ) {
        return 1; # A is prot|priv
      }
      else if ( $a->access == 'protected' ) {
        return -1; # B is priv
      }
      else if ( $b->access == 'protected' ) {
        return 1; # A is priv
      }
      else {
        return 0; # wtf??
      }
    }

    public function write_to_source_file($fp) {
    }
    
    public function write_to_header_file($fp, $indent, &$current_access) {
      $front = str_repeat('  ', max(0, $indent));

      if ( $this->export_type == 'vtable' ) {
        fwrite($fp, "{$front}{$this->functionName};\r\n");
        return;
      }

      // Change access if needed
      if ( $current_access != $this->access ) {
        $current_access = $this->access;
        $front_access = str_repeat('  ', max(0, $indent-1));
        fwrite($fp, "\r\n{$front_access}{$current_access}:\r\n");
      }

      // Write out my function
      if ( (($this->calling_convention != '__thiscall') && !($this->is_static && ($this->calling_convention == '__cdecl')) && !($this->is_static && ($this->calling_convention == '') && ($this->export_type == 'static class var'))) || ($this->static_in_function != '') || ($this->static_decimal != '') || !in_array($this->export_type, array('function', 'static class var')) ) {
        $break = true;
      }

      $txt = '';
      if ( $this->is_virtual ) {
        $txt = trim($txt) . ' virtual';
      }

      if ( $this->is_static ) {
        $txt = trim($txt) . ' static';
      }

      $txt = trim($txt) . ' ' . func::class_convertor($this->returnValue);

      if ( $this->export_type == 'static class var' ) {
        $txt = trim($txt) . ' ' . $this->static_name;
      }
      else if ( $this->export_type == 'function' ) {
        $args = implode(', ', array_map(array('func', 'class_convertor'),$this->functionArgs));
        if ( $args == 'void' ) {
          $args = '';
        }

        $txt = trim($txt) . ' ' . $this->functionName . '(' . $args . ')';

        if ( $this->is_const ) {
          $txt = trim($txt) . ' const';
        }
      }
      else {
        echo "WTF?!";
        die();
      }

      $txt = trim($txt);
      
      fwrite($fp, "{$front}{$txt};\r\n");
    }
  }

