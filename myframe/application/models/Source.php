<?php
/**
 * 记录迁移的新旧数据对应关系记录到cmstop数据库中，cmstop_search
 */
class Source extends Model
{
	function __construct() {
		parent::__construct();
		$this->_table = 'cmstop_source';
		$this->datadeal = model('Migrate');
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	function insert($name){
		
		$get = self::get($name);
		if($get){
			$upsql = "update $this->_table set count=count+1 where sourceid=".$get[0]['sourceid'];
			$this->ndb->update($upsql);
			$res = $get[0]['sourceid'];
		}else{
			$data = array(
									'name'=> $name,
									'initial'=> strtolower(inital($name)),
									'count'=> 1,
								);
			$insert = $this->datadeal->getsql($this->_table,$data);
			$res = $this->ndb->insert($insert);
		}

		$copyfrom = $this->odb->select("select * from reform_copyfrom where sitename='".$name."' order by siteurl desc limit 1");

		if($copyfrom){
			$upsql = "update $this->_table set count=count+1,url='".$copyfrom[0]['siteurl']."',logo='".$copyfrom[0]['thumb']."' where sourceid=".$get[0]['sourceid'];
			$this->ndb->update($upsql);			
		}

		return $res;
	}

	function get($name){
		$sql = "select sourceid,count from $this->_table where name='".$name."'";
		$data = $this->ndb->select($sql);
		return $data;
	}



	function delete($sourceid){
		$delsql = "delete from $this->_table where `sourceid`=$sourceid";

		$res = $this->ndb->delete($delsql);
		if($res){
			$log = "Delete执行成功：".$sourceid."\r\n";
			write_file('source',$log,true);
		}
		return true;
	}
}