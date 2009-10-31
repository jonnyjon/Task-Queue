<?php defined('SYSPATH') or die('No direct script access.');

class Model_Task extends ORM {
	protected $_db = 'default';

	public function __set($column,$value)
	{
		if ( $column === 'uri') {
			$value = serialize($value);
		}
		parent::__set($column,$value);
	}
	
	public function __get($column)
	{
		return $column === 'uri' ? unserialize(parent::__get($column)) : parent::__get($column);
	}
	
}