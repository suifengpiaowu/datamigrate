<?php
define('LOG_PATH', ROOT.DS.'tmp'.DS.'logs'.DS);

class log
{
	const EMERG   = 'EMERG';   // Emergency: system is unusable
	const ALERT   = 'ALERT';   // Alert: action must be taken immediately
	const CRIT    = 'CRIT';    // Critical: critical conditions
	const ERR     = 'ERR';     // Error: error conditions
	const WARN    = 'WARN';    // Warning: warning conditions
	const NOTICE  = 'NOTICE';  // Notice: normal but significant condition
	const INFO    = 'INFO';    // Informational: informational messages
	const DEBUG   = 'DEBUG';   // Debug: debug messages

    protected $_priorities = array(
		self::EMERG  => true,
		self::ALERT  => true,
		self::CRIT   => true,
		self::ERR    => true,
		self::WARN   => true,
		self::NOTICE => true,
		self::INFO   => true,
		self::DEBUG  => true,
    ),
	$_date_format = 'Y-m-d H:i:s',
	$_log = array(),
	$_cached_size = 0,
	$_cache_chunk_size = 65536,
    $_filename,
    $_writeable = false,
    $options = array();
    
    function __construct()
    {
    	$this->options = array('log'=>false,'filename'=>'default.log');
    }

    function set_options(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

	static function add($msg, $type = self::DEBUG)
	{
		static $instance;
        if (is_null($instance))
        {
			$instance = new log();
		}
		$instance->append($msg, $type);
    }

	function append($msg, $type = self::DEBUG)
	{
		if (!isset($this->_priorities[$type])) return;
        $this->_log[] = array(time(), $msg, $type);
        $this->_cached_size += strlen($msg);
        if ($this->_writeable && $this->_cached_size >= $this->_cache_chunk_size)
        {
            $this->flush();
        }
    }

    function flush()
    {
        if (empty($this->_log)) return;
        if (!$this->_writeable)
        {
        	// $keys = explode(',', $this->options['priorities']);
        	// $arr = array();
        	// foreach ($keys as $key)
        	// {
        	// 	if (!isset($this->_priorities[$key]))
        	// 	{
        	// 		continue;
        	// 	}
        	// 	$arr[$key] = true;
        	// }
        	// $this->_priorities = $arr;

	        $filename = $this->options['filename'];
	        $this->_filename = LOG_PATH.$filename;
            if (!is_dir(dirname($this->_filename)))
            {
                helper('folder');
                folder::create($this->_filename);
            }
	        $chunk_size = intval($this->options['cache_chunk_size']);
	        if ($chunk_size < 1) $chunk_size = 64;
	        $this->_cache_chunk_size = $chunk_size * 1024;
	        $this->_writeable = true;
        }

        $string = '';
        foreach ($this->_log as $offset => $item)
        {
            list($time, $msg, $type) = $item;
            unset($this->_log[$offset]);
            if (!isset($this->_priorities[$type]))
            {
            	continue;
            }
            $string .= date('c', $time) . " {$type}: {$msg}\n";

        }
        
        if ($string)
        {
        	write_file($this->_filename, $string, true);
        }
        $this->_log = array();
        $this->_cached_size = 0;
    }
    
	function __destruct()
	{
		$this->flush();
	}
}