<?php


/**
 * 示例模型
 */
class Link extends Model {
	function __construct() {
		parent::__construct();
		$this->_table = "cmstop_content";
		$this->datadeal = model('Datadeal');
		$this->tags = model('Tags');
		$this->source = model('Source');
		$this->property = model('Property');
		$this->search = model('Search');
		$this->log = model('Log');
		$this->related = model('Related');

		//定义每次查询的数据条数
		$this->length = 100;

		$this->category = config('category');
		$this->propertyids = config('property');

	}

	/*数据迁移运行*/
	public	function run(){
		$this->runarticle();
	}

	/*数据迁移运行*/
	public	function runarticle(){

		//获取数据总条数
		$total = $this->odb->page("select count(a.historyid) total from reform_history a")[0]['total'];

		//获取数据总页数
		$pages = ceil($total/$this->length); 
		echo "ALL pages $pages</br>"."\r\n";
		$field = "a.historyid,a.title,a.url,a.thumb,a.year,a.month,a.day,a.description,a.elite,a.passed,a.inputtime";
		for ($i=1; $i <= $pages; $i++) { 
			$sql = "select $field from reform_history a order by a.historyid asc";
			$data = $this->odb->page($sql,$i,$this->length);
			// console($i);
			// console($this->ndb->error());
			// var_dump($data);
			// exit;
			foreach ($data as $key => $value) {
				$res = self::insertcontent($value);
				echo $value['id']."Write successful:".$res."\r\n";
				if(!$res){
					var_dump($value);
					write_file("cmstop_content","Write failure:".$value['id'],true);
					exit();
				}
			}
			// exit('stop');
			// 
			echo " $i page complete </br>"."\r\n";
		}
		echo "ALL complete </br>"."\r\n";
	}

	/*写入数据到目标数据库*/
	public function insertcontent($data){
		$option['content']['catid'] = 19 ;
		$option['content']['modelid'] = 3;
		$option['content']['title'] = addslashes(substr($data['title'],0,200));
		$option['content']['thumb'] = $this->datadeal->imagehandle($data['thumb']);
		$option['content']['url'] = $data['url'] ? $data['url'] : null;
		$option['content']['subtitle'] = null;
		$option['content']['thumb'] = null;
		$option['content']['status'] = in_array($data['passed'], array(1)) ? 6 : 0;
		$option['content']['created'] = $data['inputtime'];
		$option['content']['createdby'] = 1;
		$option['content']['published'] = $data['inputtime'];
		$option['content']['publishedby'] = 1;
		$option['content']['modified'] = $data['inputtime'];
		$option['content']['modifiedby'] = 1;
		$option['content']['pv'] = rand(2000,5000);
		$option['content']['allowcomment'] = 0;
		$option['content']['tags'] = null;

		$insertsql = $this->datadeal->getsql($this->_table,$option['content']);
		
		$contentid = $this->ndb->insert($insertsql);
		if($contentid){
			$res = self::insertarticle($contentid,$data);
			return $contentid;
		}else{
			console($this->ndb->error());
			return false;
		}
	}

	/**
	 * [数据写入到cmstop_Link表]
	 */
	function insertarticle($contentid,$data){
		$table = "cmstop_link";
		$option['link']['contentid'] = $contentid;
		$option['link']['description'] = $data['description'] ? addslashes(substr($data['description'],0,255)): null;
		$option['link']['description2'] = $data['description'] ? addslashes($data['description']): null;
		$option['link']['id'] = intval($data['historyid']);
		$option['link']['year'] = $data['year'] ? substr($data['year'],0,255) : null;
		$option['link']['month'] = $data['month'] ? substr($data['month'],0,255) : null;
		$option['link']['day'] = $data['day'] ? substr($data['day'],0,255) : null;
		$option['link']['elite'] = $data['elite'] ? substr($data['elite'],0,255) : null;
		$option['link']['passed'] = $data['passed'] ? substr($data['passed'],0,255) : null;


		$insertsql = $this->datadeal->getsql($table,$option['link']);
		$res = $this->ndb->insert($insertsql);
		if($res){
			self::insertsearch($contentid);
			self::insertlog($contentid,$data);
		}
	}

	/*处理搜索数据*/
	function insertsearch($contentid){

		$idata['contentid'] = $contentid;

		$idata['content'] = null;
		
		$this->search->insert($idata);
	}

	/*处理日志数据*/
	function insertlog($contentid,$data){
		$idata = array(
										'type'=>'Link',
										'newid'=>$contentid,
										'oldid'=>$data['id'],
										'oldurl'=>$data['url'],
										'message'=>'历史上的今天数据迁移新旧站文章id对应',
										);
		
		$this->log->insert($idata);
	}

	/*清理迁移数据，还原数据表*/
	public	function clear(){

		$contentid = 1;
		$this->ndb->delete("delete from cmstop_link where `contentid`>0");
		$this->datadeal->auto_increment('cmstop_link',1);
		write_file("cmstop_link","删除表：cmstop_link的contentid=$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from $this->_table where `contentid`>0");
		$this->datadeal->auto_increment($this->_table);
		write_file("cmstop_content","删除表：$this->_table的contentid=$contentid的用户数据。设置主键自增！");

		$this->tags->delete();
		$this->datadeal->auto_increment('cmstop_tag');
		$this->source->delete();
		$this->datadeal->auto_increment('cmstop_source');

		$this->ndb->delete("delete from cmstop_removal_log");
		$this->datadeal->auto_increment('cmstop_removal_log');
	}

}