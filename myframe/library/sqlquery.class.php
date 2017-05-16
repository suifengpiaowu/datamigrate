<?php

/**
 * 数据库操作基类
 */
class SQLQuery {

	protected $_dbHandle;
	protected $_result;

	/**
	 * 连接数据库
	 * @param  [str] $address [连接地址]
	 * @param  [str] $account [账号]
	 * @param  [str] $pwd     [密码]
	 * @param  [str] $name    [数据库名]
	 * @return [bool]
	 */
	function connect($address, $account, $pwd, $name) {
		$this->_dbHandle = @mysql_connect($address, $account, $pwd);
		if ($this->_dbHandle != 0) {
			if (mysql_select_db($name, $this->_dbHandle)) {
				return 1;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 中断数据库连接
	 * @return [bool]
	 */
	function disconnect() {
		if (@mysql_close($this->_dbHandle) != 0) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * 查询所有数据表内容
	 * @return [arr]
	 */
	function selectAll() {
		$query = 'select * from `'.$this->_table.'`';
		return $this->query($query);
	}

	/** 
	 * 查询数据表指定列内容
	 * @param  [int] $id
	 * @return [arr]
	 */
	function select($id = 0, $field = '*') {
		$query = 'select ' . $field . ' from `'.$this->_table.'` where `id` = \''.mysql_real_escape_string($id).'\'';
		return $this->query($query, 1);
	}

	/**
	 * 自定义SQL查询语句
	 * @param  [str]  $query
	 * @param  integer $singleResult [只取一条]
	 * @return [arr]
	 */
	function query($query, $singleResult = 0) {
		$this->_result = mysql_query($query, $this->_dbHandle);
		if (preg_match("/select/i",$query)) {
			$result = array();
			$table = array();
			$field = array();
			$tempResults = array();
			$numOfFields = mysql_num_fields($this->_result);
			for ($i = 0; $i < $numOfFields; ++$i) {
				array_push($table,mysql_field_table($this->_result, $i));
				array_push($field,mysql_field_name($this->_result, $i));
			}
			while ($row = mysql_fetch_row($this->_result)) {
				for ($i = 0;$i < $numOfFields; ++$i) {
					$table[$i] = trim(ucfirst($table[$i]),"s");
					$tempResults[$table[$i]][$field[$i]] = $row[$i];
				}
				if ($singleResult == 1) {
					mysql_free_result($this->_result);
					return $tempResults;
				}
				array_push($result,$tempResults);
			}
			mysql_free_result($this->_result);
			return($result);
		}
	}

	/**
	 * 返回结果集行数
	 * @return [int]
	 */
	function getNumRows() {
		return mysql_num_rows($this->_result);
	}

	/**
	 * 释放结果集内存
	 * @return [bool]
	 */
	function freeResult() {
		mysql_free_result($this->_result);
	}

	/**
	 * 返回MySQL操作错误信息
	 * @return [str]
	 */
	function getError() {
		return mysql_error($this->_dbHandle);
	}
}
