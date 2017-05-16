<?php

/**
 * 视图基类
 */
class Template {

	protected $variables = array();
	protected $_controller;
	protected $_action;

	function __construct($controller,$action) {
		$this->_controller = $controller;
		$this->_action = $action;
	}

	/**
	 * 设置变量
	 * @param [str] $name  [变量名]
	 * @param [arr] $value [变量值]
	 */
	function set($name,$value) {
		$this->variables[$name] = $value;
	}

	/**
	 * 显示模板
	 * @return [obj]
	 */
	function render($path = '') {
		extract($this->variables);
		$templateDir = ROOT.DS. 'application' .DS. 'views' .DS;
		$templateSubDir = strtolower($this->_controller) .DS;
		if (file_exists($templateDir . $templateSubDir . 'header.php')) {
			include($templateDir . $templateSubDir . 'header.php');
		}
		if ($path) {
			include ($templateDir . $path . '.php');
		}else{
			include ($templateDir . $templateSubDir . $this->_action . '.php');
		}
		if (file_exists($templateDir . $this->_controller . 'footer.php')) {
			include ($templateDir . $this->_controller . 'footer.php');
		}
	}
}