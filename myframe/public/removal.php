<?php

/**
 * 数据迁移入口文件
 */

define('DS',DIRECTORY_SEPARATOR);
define('ROOT',dirname(dirname(__FILE__)));

// php removal.php special run

// php removal.php article run

// php removal.php picture run

// php removal.php video run

// php removal.php event run

// php removal.php book run

// php removal.php people run

// php removal.php organization run

// php removal.php link run


// php removal.php special clear
// php removal.php article clear
// php removal.php picture clear
// php removal.php video clear
// php removal.php event clear
// php removal.php book clear
// php removal.php people clear
// php removal.php organization clear

if(!empty($_SERVER['argv'][1]) && !empty($_SERVER['argv'][2])){
	$controller = $_SERVER['argv'][1];
	$action = $_SERVER['argv'][2];
	$url = $controller.'/'.$action;
	require_once(ROOT.DS.'library'.DS.'bootstrap.php');		
}else{
	echo "参数传输错误：正确的访问格式为#php removal.php controller action";
}
