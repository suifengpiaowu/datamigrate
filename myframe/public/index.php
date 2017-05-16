<?php

/**
 * 项目入口文件
 */

define('DS',DIRECTORY_SEPARATOR);
define('ROOT',dirname(dirname(__FILE__)));
$url = $_GET['url'];
require_once(ROOT.DS.'library'.DS.'bootstrap.php');