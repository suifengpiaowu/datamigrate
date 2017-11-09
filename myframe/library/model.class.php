<?php

/**
 * 模型基类
 */
class Model
{
	
	protected  $_model,$odb,$ndb;

	function __construct() {
		$this->_model = get_class($this);

		// 引入数据库模型
		$this->odb = loadloddb(config("olddb"));
		$this->ndb = loadnewdb(config("newdb"));
	}

	function __destruct() {
	}
}