<?php

/**
 * 项目启动文件
 */
// 设置字符集
header("Content-Type:text/html;charset=utf-8"); 
// 引入配置文件
require_once(ROOT.DS.'config'.DS .'config.php');
// 引入自定义函数文件
require_once(ROOT.DS.'library'.DS .'function.php');
// 引入框架加载项
require_once(ROOT.DS.'library'.DS .'shared.php');