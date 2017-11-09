<?php

/**
 * 项目入口文件
 */

define('DS',DIRECTORY_SEPARATOR);
define('ROOT',dirname(dirname(__FILE__)));

if(!empty($_SERVER['argv'][1])){
	$param = $_SERVER['argv'][1];
	if($param == "cleararticle"){
		$url = 'removal/cleararticle';
	}elseif($param == "runarticle"){
		$url = 'removal/runarticle';
	}elseif($param == "runpicture"){
		$url = 'removal/runpicture';
	}elseif($param == "clearpicture"){
		$url = 'removal/clearpicture';
	}elseif($param == "runvideo"){
		$url = 'removal/runvideo';
	}elseif($param == "clearvideo"){
		$url = 'removal/clearvideo';
	}elseif($param == "runspecial"){
		$url = 'removal/runspecial';
	}elseif($param == "clearspecial"){
		$url = 'removal/clearspecial';
	}
	require_once(ROOT.DS.'library'.DS.'bootstrap.php');		
}else{
	echo "无参数输出！";
}
