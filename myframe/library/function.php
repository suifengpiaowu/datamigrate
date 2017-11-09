<?php 
/*
* 	@by gn
* 	@自定义函数集合
*/

/**
 * 加载框架类库
 *	
 * @param  [string] $libname[]文件在'library/helper下的文件名称，有目录使用 . 隔开，文件名称需要与实例化的类名保持一致
 * @return [] [无]
 */
function library($libname){
	$dirs = explode('.', $libname);
	$classname = end($dirs);
	$filename = ROOT.DS.'library';
	foreach ($dirs as $k => $v)
	{
		$filename .= DS.$v;
	}

	$filename .= ".class.php";

	if(!is_file($filename))
	{
		echo $filename.":文件不存在！";
	}

	require_once($filename);
	return;
}

/**
 * 加载助手类库
 *	
 * @param  [string] $libname[]文件在'library/helper下的文件名称，有目录使用 . 隔开，文件名称需要与实例化的类名保持一致
 * @return [] [无]
 */
function helper($libname){
	$dirs = explode('.', $libname);
	$classname = end($dirs);
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
 * 加载数据库
 *	
 * @param  [array] $config[config文件夹中的数据库连接信息]
 * @return [] [无]
 */
function loadloddb($config){
	$filename = ROOT.DS.'library'.DS.'db.class.php';
	require_once($filename);

	static $db = false;
	if(!$db){
		$db = new db($config);
	}

	return $db;
}

function loadnewdb($config){
	$filename = ROOT.DS.'library'.DS.'db.class.php';
	require_once($filename);

	static $db = false;
	if(!$db){
		$db = new db($config);
	}

	return $db;
}

/**
 * 加载model类
 *	
 * @param  [string] $model [加载在'application/models下的文件名称，
 *         									有目录使用 . 隔开，
 *         									文件名称需要与实例化的类名保持一致，
 *         									注意：参数的大小写与实际文件的大小写一致
 *         									]
 * @return [object] $return 返回实例化的对象
 */
function model($model){
	$dirs = explode('.', $model);
	$classname = end($dirs);
	$filename = ROOT.DS.'application'.DS.'models';
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
	$return = new $classname();
	return $return;
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
	$file = ROOT.'\tmp\logs\\'.$file.'.log';
  $result = false;
  $data = date('Y-m-d H:i:s',time()).' '.$data;
  if ($fp = @fopen($file, $append ? 'ab' : 'wb'))
  {
    $result = @fwrite($fp, $data);
    @fclose($fp);
    @chmod($file, 0777);
  }
	return $result;
}

/**
 * 将结果输入到浏览器控制台，chrome浏览器使用，需要安装phpconsole插件
 * @param     [all]                   $message [多类型的数据格式，输出内容到控制台]
 * @return    []                        [无]
 */
function console($message = NULL){

	$type = gettype($message);

	static 	$handler = false;
	if(!$handler){
		helper('PhpConsole.__autoload');
		
		/*// 设置输出控制台的密码
		$password = 'ltmz';
		if(!$password) {
			echo '请设置PHPConsole密码！';
			return true;
		}
		$connector = PhpConsole\Connector::getInstance();
		$connector->setPassword($password);
		if(!$connector->isAuthorized())
		{
			$connector->getDebugDispatcher()->dispatchDebug('输入的密码错误！');
			return true;
		}else{
			$connector->getDebugDispatcher()->dispatchDebug('恭喜你通过了验证！');
		}*/

		$handler = PhpConsole\Handler::getInstance();
		$handler->start();
	} 

	$handler->debug($message, 'PHP-Console-'.$type);
}

/**
 * @信息记录到日志文件中，保存地址 ROOT_PATH./tmp/logs [不能自动创建目录，这个问题后续排查]
 * 
 * @param     [string]                  $string [记录的字符内容]
 * @param     [string]                  $filenam [文件名]
 * @return    []                        [无返回结果]
 */
function insertlog($string,$filename = 'logfile'){
	//引入日志模型
	static 	$log = false;
	if(!$log){
		helper("log");
		$log = new log(); 
	}

	$datatime = date('Y-m-d',time());
	$log->set_options(array('log'=>true,'filename'=>$filename.$datatime.'.log'));
	$log->append($string, log::INFO);
}

    
/**
 * @name php获取中文字符拼音首字母
 * @param $str
 * @return null|string
 * @author 潘军伟<panjunwei@ruiec.cn>
 * @time 2015-09-14 17:58:14
 */
 function inital($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
    $s1 = iconv('UTF-8', 'gb2312', $str);
    $s2 = iconv('gb2312', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284) return 'A';
    if ($asc >= -20283 && $asc <= -19776) return 'B';
    if ($asc >= -19775 && $asc <= -19219) return 'C';
    if ($asc >= -19218 && $asc <= -18711) return 'D';
    if ($asc >= -18710 && $asc <= -18527) return 'E';
    if ($asc >= -18526 && $asc <= -18240) return 'F';
    if ($asc >= -18239 && $asc <= -17923) return 'G';
    if ($asc >= -17922 && $asc <= -17418) return 'H';
    if ($asc >= -17417 && $asc <= -16475) return 'J';
    if ($asc >= -16474 && $asc <= -16213) return 'K';
    if ($asc >= -16212 && $asc <= -15641) return 'L';
    if ($asc >= -15640 && $asc <= -15166) return 'M';
    if ($asc >= -15165 && $asc <= -14923) return 'N';
    if ($asc >= -14922 && $asc <= -14915) return 'O';
    if ($asc >= -14914 && $asc <= -14631) return 'P';
    if ($asc >= -14630 && $asc <= -14150) return 'Q';
    if ($asc >= -14149 && $asc <= -14091) return 'R';
    if ($asc >= -14090 && $asc <= -13319) return 'S';
    if ($asc >= -13318 && $asc <= -12839) return 'T';
    if ($asc >= -12838 && $asc <= -12557) return 'W';
    if ($asc >= -12556 && $asc <= -11848) return 'X';
    if ($asc >= -11847 && $asc <= -11056) return 'Y';
    if ($asc >= -11055 && $asc <= -10247) return 'Z';
    return null;
}

/**
* 将字符串转换为数组
*
* @param	string	$data	字符串
* @return	array	返回数组格式，如果，data为空，则返回空数组
*/
function string2array($data) {
	$data = trim($data);
	if($data == '') return array();
	if(strpos($data, 'array')===0){
		@eval("\$array = $data;");
	}else{
		if(strpos($data, '{\\')===0) $data = stripslashes($data);
		$array=json_decode($data,true);
		if(strtolower(CHARSET)=='gbk'){
			$array = mult_iconv("UTF-8", "GBK//IGNORE", $array);
		}
	}
	return $array;
}
 