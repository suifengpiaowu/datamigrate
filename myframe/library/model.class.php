<?php

/**
 * 模型基类
 */
class Model{
	
	protected $_model;

	function __construct() {
		$this->_model = get_class($this);
		$this->_table = strtolower($this->_model);
	}

	function __destruct() {
	}
}