<?php
/**
 * 记录迁移的新旧数据对应关系记录到cmstop数据库中，cmstop_search
 */
class Property extends Model
{
	function __construct() {
		parent::__construct();
		$this->_table = 'cmstop_content_property';
		$this->datadeal = model('Datadeal');
		$this->district = config('district');
		$this->bookproperty = config('bookproperty');
		

	}

	/*处理地区属性关系数据*/
	function insertdistrict($contentid,$proids){
		$array = explode(',',$proids);
		if(!empty($array)){
			foreach ($array as $value) {
				$proid = $this->district[$value];
				if($proid ){
					$idata['contentid'] = $contentid;
					$idata['proid'] = $proid;
					self::insert($idata);
				}else{
					continue;
				}
			}
		}
	}

	/*处理图书属性关系数据*/
	function insertbook($contentid,$oldcatid){
		if($proid = $this->bookproperty[$oldcatid]){
			$idata['contentid'] = $contentid;
			$idata['proid'] = $proid;
			self::insert($idata);
		}
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
									'proid'=> $data['proid'],
								);
		$insert = $this->datadeal->getsql($this->_table,$data);

		$res = $this->ndb->insert($insert);

		if($res){
			$log = "Insert日志记录：".$data['contentid']."\r\n";
			write_file($this->_table,$log,true);
		}else{
			$log = "Insert失败记录：".$data['contentid'].$insert."\r\n";
			write_file($this->_table,$log,true);
		}

		return $res;
	}

	function delete($contentid){
		$delsql = "delete from $this->_table where `contentid`!=$contentid";

		$res = $this->ndb->delete($delsql);
		if($res){
			$log = "Delete执行成功：".$contentid."\r\n";
			write_file($this->_table,$log,true);
		}
		return true;
	}
}