<?php
	$_SERVER['argv'][1] = 'C:\tmp\RE\test dll\build\Release\testDLL.dll';
	$_SERVER['argc'] = count($_SERVER['argv']);

	if ( $_SERVER['argc'] < 2 ) {
		exit("Run as: {$_SERVER['argv'][0]} [dll-to-stub]\n");
	}

	$dll = $_SERVER['argv'][1];
	echo "Going to stub '{$dll}'\n";

	$dll = realpath($dll);
	if ( !$dll ) {
		exit(" !! Can't find this dll.\n");
	}

	chdir( __DIR__ . '/../dumpbin' );
	exec( "dumpbin /exports ".escapeshellarg($dll)." | ".escapeshellarg(__DIR__.'/../vc++filt/vc++filt')." 2>&1", $output, $return_var );
	$output = implode("\n", $output);
	$ordinal_pos = strpos($output, "ordinal");
	$RVA_pos     = strpos($output, "RVA", $ordinal_pos);
	$summary_pos = strrpos($output, "Summary");
	$output = substr($output, $RVA_pos, $summary_pos - $RVA_pos);
	
	preg_match_all("~^\s*\d+\s+[0-9a-z]+\s+[0-9a-z]+\s+(.*)$~iUxms", $output, $matches);

	$demangled_functions = $matches[1];

	$functions = array();
	class func {
		private $access		  = ''; // public, protected, private
		private $is_virtual   = false;
		private $is_static    = false;
		private $is_const     = false;
		private $className    = '';
		private $functionName = '';
		private $returnValue  = '';
		private $functionArgs = '';
		private $type         = '';
		private $calling_convention = '';

		static function interpret($line) {
			$line = trim($line);

			$obj = new func();
			if ( substr($line, -5) == 'const' ) {
				$obj->is_const = true;
				$line = substr($line, 0, -5);
			}

			$a = array_map('trim', explode(' ', $line));
			$first = array_shift($a);
			switch( strtolower($first) ) {
			case 'public:':
			case 'protected:':
			case 'private:':
				$obj->access = substr($first, 0, -1);
				func::interpret_modifiers($obj, $a);
				break;

			case 'const':
				$obj->type = 'enum';
				func::interpret_className($obj, implode(' ', $a));
				break;

			default:
				break;
			}

			return $obj;
		}

		static function interpret_modifiers(&$obj, $line) {
			$first = array_shift($line);
			switch( strtolower($first) ) {
			case 'virtual':
				$obj->is_virtual = true;
				func::interpret_modifiers($obj, $line);
				break;

			case 'static':
				$obj->is_static = true;
				func::interpret_modifiers($obj, $line);
				break;

			default:
				array_unshift($line, $first);
				func::interpret_return($obj, $line);
				break;
			}
		}

		static function interpret_return(&$obj, $line) {
			$line = implode(' ', $line);

			foreach( array('__thiscall', '__cdecl') as $search ) {
				$pos_this_call = strpos($line, $search);
				if ( $pos_this_call !== false ) {
					$obj->returnValue = trim(substr($line, 0, $pos_this_call));
					$obj->calling_convention = $search;
					$line = trim(substr($line, $pos_this_call + strlen($search)));
					return func::interpret_className($obj, $line);
				}
			}

			exit("Can't interpret {$line}\n");
		}

		static function interpret_className(&$obj, $line) {
		}
	}

	foreach( $demangled_functions as $line ) {
		$functions[] = func::interpret($line);
	}

	// 1: public: __thiscall MultiDerivedprivateprotectedM::MultiDerivedprivateprotectedM(void)
	// 98: public: class VirtualBaseB & __thiscall VirtualBaseB::operator=(class VirtualBaseB const &)
	// 100: const MultiVirtualDerivedprivateprotectedM::`vftable'{for `VirtualBaseA'}
	// 188: public: static int __cdecl NormalBaseA::doSomething(void)
	// 196: class std::basic_string<char,struct std::char_traits<char>,class std::allocator<char> > `private: class std::basic_string<char,struct std::char_traits<char>,class std::allocator<char> > const & __thiscall NormalBaseA::AprivateFunc2(class std::basic_string<char,struct std::char_traits<char>,class std::allocator<char> > const &)'::`2'::a
	$ctr = 196;
	var_dump($demangled_functions);
	print_r($functions[$ctr]);

	echo "\n\n -- \n\n";