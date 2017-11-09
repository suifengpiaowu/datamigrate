<?php
/**
 * 记录迁移的新旧数据对应关系记录到cmstop数据库中，cmstop_removal_log
 */
class Log extends Model
{
	function __construct() {
		parent::__construct();
		$this->_table = 'cmstop_removal_log';
		$this->datadeal = model('Datadeal');
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [string]                   $tablename [表名]
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	function insert($data){
		$data = array(
										'type'=> $data['type'] ?substr($data['type'],0,50) : null,
										'newid'=> $data['newid'] ? $data['newid'] : null,
										'newurl'=> isset($data['newurl']) ? substr($data['newurl'],0,255) : null,
										'oldid'=> $data['oldid'] ? $data['oldid'] : null,
										'oldurl'=> isset($data['oldurl']) ? substr($data['oldurl'],0,255) : null,
										'message'=> $data['message'] ? substr($data['message'],0,255) : null,
										);
		$insert = $this->datadeal->getsql($this->_table,$data);

		$res = $this->ndb->insert($insert);

		if($res){
			$log = $data['type']."日志记录：".$res.'==>'.serialize($data)."r\n";
			write_file($data['type'],$log,true);
		}
	}
}