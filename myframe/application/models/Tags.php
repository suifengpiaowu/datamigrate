<?php
/**
 * 记录迁移的新旧数据对应关系记录到cmstop数据库中，cmstop_tag
 */
class Tags extends Model
{
	function __construct() {
		parent::__construct();
		$this->_table = 'cmstop_tag';
		$this->datadeal = model('Datadeal');
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	function insert($data){

		$tags = explode(' ', $data['tags']);
		foreach ($tags as $value) {
			$getsql = "select tagid from  $this->_table where `tag`='".$value."'";
			$res = $this->ndb->select($getsql);
			if(!$res){
				$insertdata['tag'] = $value;
				$insertdata['initial'] = strtolower(inital($value));
				$insertdata['usetimes'] = 1;
				$insertsql = $this->datadeal->getsql($this->_table,$insertdata);
				$tagid = $this->ndb->insert($insertsql);
				
				self::insertbindtag($data['contentid'],$tagid);
			}else{
				$upsql = "update $this->_table set usetimes=usetimes+1 where tagid=".$res[0]['tagid'];
				$this->ndb->update($upsql);
				self::insertbindtag($data['contentid'],$res[0]['tagid']);
			}

		}
	}

	function insertbindtag($contentid,$tagid){
		$tabel = "cmstop_content_tag";
		$data['contentid'] = $contentid;
		$data['tagid'] = $tagid;
		$sql = $this->datadeal->getsql($tabel,$data);
		$tagid = $this->ndb->insert($sql);
	}

	function delete(){
		$delsql = "delete from $this->_table";
		$res = $this->ndb->delete($delsql);
		if($res){
			$log = "$this->_table 删除执行成功 \r\n";
			write_file($this->_table,$log,true);
		}
		return true;
	}
}