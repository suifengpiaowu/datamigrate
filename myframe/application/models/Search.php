<?php
/**
 * 记录迁移的新旧数据对应关系记录到cmstop数据库中，cmstop_search
 */
class Search extends Model
{
	function __construct() {
		parent::__construct();
		$this->_table = 'cmstop_search';
		$this->datadeal = model('Migrate');
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	function insert($data){
		
		$data =array(
										'contentid'=>	$data['contentid'],
										'content'=> isset($data['content']) ? $data['content'] : null,
										);
		$insert = $this->datadeal->getsql($this->_table,$data);

		$res = $this->ndb->insert($insert);

		if($res){
			$log = "Insert日志记录：".$data['contentid']."\r\n";
			write_file('Search',$log,true);
		}else{
			self::delete($data['contentid']);
		}

		return $res;
	}

	function delete($contentid){
		$delsql = "delete from $this->_table where `contentid`=$contentid";

		$res = $this->ndb->delete($delsql);
		if($res){
			$log = "Delete执行成功：".$contentid."\r\n";
			write_file('Search',$log,true);
		}
		return true;
	}
}