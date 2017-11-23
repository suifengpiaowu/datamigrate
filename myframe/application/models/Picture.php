<?php


/**
 * 示例模型
 */
class Picture extends Model {
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
		$this->length = 1;

		$this->category = config('category');
	}

	/*数据迁移运行*/
	public	function run(){
		$this->runpicture();
	}

	/*数据迁移运行*/
	public	function runpicture(){

		//获取数据总条数
		$total = $this->odb->page("select count(a.id) total from reform_jingtou a,reform_jingtou_data d where a.id=d.id and a.vod=0")[0]['total'];

		//获取数据总页数
		$pages = ceil($total/$this->length); 
		echo "ALL pages $pages </br>"."\r\n";
		$field = "a.id,a.catid,a.title,a.subtitle,a.thumb,a.keywords,a.copyfrom,a.url,a.status,a.inputtime,a.updatetime,a.description,a.author,a.typeid,a.specialid,a.peopleid,a.orgid,a.atype,a.dtype,d.content,d.pictureurls";
		for ($i=1; $i <= $pages; $i++) { 
			$sql = "select $field from reform_jingtou a,reform_jingtou_data d where a.id=d.id and a.vod=0 order by a.id asc";
			$data = $this->odb->page($sql,$i,$this->length);
			// console($i);
			// // var_dump($this->ndb->error());
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
		$option['content']['catid'] = 2;
		$option['content']['modelid'] = 2;
		$option['content']['title'] = addslashes(substr($data['title'],0,200));
		$option['content']['subtitle'] = addslashes(substr($data['subtitle'],0,120));
		$option['content']['thumb'] = $this->datadeal->imagehandle($data['thumb']);
		$option['content']['status'] = intval($data['status']) < 99 ? 0 : 6;
		$option['content']['created'] = $data['inputtime'];
		$option['content']['createdby'] = 1;
		$option['content']['published'] = $data['inputtime'];
		$option['content']['publishedby'] = 1;
		$option['content']['modified'] = $data['updatetime'];
		$option['content']['modifiedby'] = 1;
		$option['content']['pv'] = rand(2000,5000);
		$option['content']['allowcomment'] = 0;
		$option['content']['tags'] = substr($data['keywords'],0,200);
		$option['content']['sourceid'] = self::insertsource($data);

		$insertsql = $this->datadeal->getsql($this->_table,$option['content']);
		
		$contentid = $this->ndb->insert($insertsql);
		if($contentid){
			$res = self::insertpicture($contentid,$data);
			return $contentid;
		}else{
			console($this->ndb->error());
			return false;
		}
	}

	/**
	 * [数据写入到cmstop_picture表]
	 */
	function insertpicture($contentid,$data){
		$table = "cmstop_picture";
		$option['pircure']['contentid'] = $contentid;
		$option['pircure']['description'] = $data['description'] ? addslashes(substr($data['description'],0,255)): null;
		$option['pircure']['description2'] = $data['description'] ? addslashes($data['description']): null;
		$option['pircure']['content'] = addslashes($this->datadeal->dealcontent($data['content']));
		$option['pircure']['editor'] = $data['author'] ? substr($data['author'],0,20) : null;

		$picgroupdata = string2array($data['pictureurls']);
		$total = count($picgroupdata);
		$option['pircure']['total'] = $total ? $total : 0;

		$option['pircure']['id'] = intval($data['id']);
		$option['pircure']['typeid'] = $data['typeid'] ? substr($data['typeid'],0,255) : null;
		$option['pircure']['specialid'] = $data['specialid'] ? substr($data['specialid'],0,255) : null;
		$option['pircure']['peopleid'] = $data['peopleid'] ? substr($data['peopleid'],0,255) : null;
		$option['pircure']['orgid'] = $data['orgid'] ? substr($data['orgid'],0,255) : null;
		$option['pircure']['atype'] = $data['atype'] ? substr($data['atype'],0,255) : null;
		$option['pircure']['dtype'] = $data['dtype'] ? substr($data['dtype'],0,255) : null;
		
		$insertsql = $this->datadeal->getsql($table,$option['pircure']);
		$res = $this->ndb->insert($insertsql);
		// var_dump($this->ndb->error());
		if($res){
			$this->related->insertrelated($contentid,$data['specialid']);
			$this->property->insertdistrict($contentid,$data['specialid']);
			self::insertpicturegroup($contentid,$picgroupdata);
			self::insertsearch($contentid);
			self::inserttags($contentid,$data);
			self::insertlog($contentid,$data);
		}
	}

	function insertpicturegroup($contentid,$picgroupdata){
		$table = "cmstop_picture_group";
		if($picgroupdata){
			foreach ($picgroupdata as $v) {
				$option['pircure_group']['contentid'] = $contentid;
				$option['pircure_group']['aid'] = null;
				$option['pircure_group']['image'] = $this->datadeal->imagehandle($v['url']);
				$option['pircure_group']['note'] = $v['alt'] ? addslashes(substr($v['alt'],0,255)): null ;
				$option['pircure_group']['url'] = null;
				$option['pircure_group']['sort'] = intval($v['listorder']);
				$insertsql = $this->datadeal->getsql($table,$option['pircure_group']);
				$insertid = $this->ndb->insert($insertsql);
				$res[] = $insertid;
			}
		}
	}

	/*处理搜索数据*/
	function insertsearch($contentid){

		$idata['contentid'] = $contentid;
		$idata['content'] = null;
		
		$this->search->insert($idata);
	}

	/*处理来源数据*/
	function insertsource($data){
		
		$name = explode('|', $data['copyfrom'])[0];
		
		$return = $this->source->insert($name);
		return $return;
	}

	/*处理tags关键词数据处理*/
	function inserttags($contentid,$data){
		
		$idata['contentid'] = $contentid;
		$idata['tags'] = $data['keywords'];

		
		$this->tags->insert($idata);
	}


	/*处理tags关键词数据处理*/
	function insertlog($contentid,$data){
		$idata = array(
										'type'=>'picture',
										'newid'=>$contentid,
										'oldid'=>$data['id'],
										'oldurl'=>$data['url'],
										'message'=>'组图数据迁移，newid为新站contentid，oldid为旧站id',
										);
		
		$this->log->insert($idata);
	}

	/*清理迁移数据，还原数据表*/
	public	function clear(){

		$contentid = 0;
		$this->ndb->delete("delete from cmstop_picture where `contentid`>$contentid");
		$this->datadeal->auto_increment('cmstop_picture',($contentid+1));
		write_file("cmstop_picture","删除表：cmstop_article的contentid>$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from cmstop_picture_group where `contentid`>$contentid");
		$this->datadeal->auto_increment('cmstop_picture_group');
		write_file("cmstop_picture_group","删除表：cmstop_article的contentid>$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from $this->_table where `contentid`>$contentid");
		$this->datadeal->auto_increment($this->_table,($contentid+1));
		write_file($this->_table,"删除表：$this->_table的contentid>$contentid的用户数据。设置主键自增！");

		$this->ndb->delete("delete from cmstop_removal_log where `type`='picture'");
		$this->datadeal->auto_increment('cmstop_removal_log');
	}

}