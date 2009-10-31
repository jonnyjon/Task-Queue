<?php defined('SYSPATH') OR die('No direct access allowed.');
return array(
	'file' => array(
		'path' => APPPATH . 'cache',
		'extension' => '.txt',
		'lifetime' => 3600,
	),

	'eaccelerator' => array(
		'lifetime' => 3600,
	),

	'memcache' => array(
		'lifetime' => 3600,
		'servers' => array(
			array(
				'host' => '127.0.0.1',
				'port' => 11211,
				'persistent' => false,
				'weight' => 100
			),
		),
	),

);
?>