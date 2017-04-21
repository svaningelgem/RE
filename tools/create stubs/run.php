<?php
  define('DEBUG', true);

  if ( DEBUG ) {
    $_SERVER['argv'] = array(__FILE__);
    $_SERVER['argv'][] = __DIR__.'/../../test dll/build/Release/testDLL.dll';
	  $_SERVER['argv'][] = 'C:\tmp\ISISP Plugin\ISIS\isiscomm\w3\lib\omsdt57.dll';

	  $_SERVER['argc'] = count($_SERVER['argv']);
  }

	if ( $_SERVER['argc'] < 2 ) {
		exit("Run as: {$_SERVER['argv'][0]} [dll-to-stub] [...]\n");
	}

  unset($_SERVER['argv'][0]);
  require_once(__DIR__.'/process_dll.php');

  foreach( $_SERVER['argv'] as $dll ) {
    process_dll($dll);
  }
