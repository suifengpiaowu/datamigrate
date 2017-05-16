<?php 
/*
* 	自定义函数集合
*/


/**
 * 加载助手类库
 *	
 * @param $libname string 文件在'library/helper下的文件名称，有目录使用 . 隔开，文件名称需要与实例化的类名保持一致
 * @return $libname 返回实例化的对象
 */
function helper($libname){
	$dirs = explode('.', $libname);
	$libname = end($dirs);
	$filename = ROOT.DS.'library'.DS.'helper';
	foreach ($dirs as $k => $v)
	{
		$filename .= DS.$v;
	}

	$filename .= ".php";

	if(!is_file($filename))
	{
		echo $filename.":文件不存在！";
	}

	require_once($filename);
	return;
}


/**
 * 加载配置文件
 *	
 * @param $fileconf 在cofnig目录下的文件名称
 * @return $config 返回配置文件中的数组
 */
function config($fileconf){
	$fileconf = ROOT.DS.'config'.DS.$fileconf.'.php';
	$config = require_once($fileconf);
	if(!is_file($fileconf))
	{
		echo $fileconf.":文件不存在！";
	}
	if(!is_array($config))
	{
		echo $fileconf.":不是数组，不正确！";
	}else{
		return $config;
	}
}

/**
 * 写入文件
 *
 * @param string $file 文件名
 * @param string $data 文件内容
 * @param boolean $append 是否追加写入
 * @return int
 */
function write_file($file, $data, $append = false)
{
	$dir = dirname($file);
	if (!is_dir($dir)) folder::create($dir);

    $result = false;

    if ($fp = @fopen($file, $append ? 'ab' : 'wb'))
    {
        $result = @fwrite($fp, $data);
        @fclose($fp);
        @chmod($file, 0777);
    }
	return $result;
}