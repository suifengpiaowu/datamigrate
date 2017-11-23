<?php


/**
 * 示例模型
 */
class Special extends Model {
	function __construct() {
		parent::__construct();
		$this->_table = "cmstop_content";
		$this->datadeal = model('Datadeal');
		$this->tags = model('Tags');
		$this->source = model('Source');
		$this->property = model('Property');
		$this->search = model('Search');
		$this->log = model('Log');
		//定义每次查询的数据条数
		$this->length = 1;

		$this->category = config('category');
		$this->district = config('district');

	}

	/*数据迁移运行*/
	public	function run(){
		$this->runarticle();
	}

	/*数据迁移运行*/
	public	function runarticle(){

		//获取数据总条数
		$total = $this->odb->page("select count(a.id) total from reform_special a where a.typeids>22")[0]['total'];

		//获取数据总页数
		$pages = ceil($total/$this->length); 
		echo "ALL pages $pages </br>"."\r\n";
		$field = "a.id,a.typeids,a.title,a.subtitle,a.thumb,a.keywords,a.url,a.inputtime,a.updatetime,a.description,a.content,a.pics,a.index_template,a.atype,a.arratype,a.specialid,a.peopleid,a.orgid,a.rwzid,a.opinion,a.province";
		for ($i=1; $i <= $pages; $i++) { 
			$sql = "select $field from reform_special a where a.typeids>22 order by a.id asc";
			$data = $this->odb->page($sql,$i,$this->length);
			// console($i);
			// var_dump($this->ndb->error());
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
			echo " $i page complete </br>"."\r\n";
		}
		echo "ALL complete </br>"."\r\n";
	}

	/*写入数据到目标数据库*/
	public function insertcontent($data){
		$option['content']['catid'] = self::getcatid($data['typeids']);//专题栏目
		$option['content']['modelid'] = 1;//文章模型
		$option['content']['title'] = addslashes(substr($data['title'],0,200));
		$option['content']['subtitle'] = addslashes(substr($data['subtitle'],0,120));
		$option['content']['thumb'] = $this->datadeal->imagehandle($data['thumb']);
		$option['content']['status'] = 6;
		$option['content']['created'] = $data['inputtime'];
		$option['content']['createdby'] = 1;
		$option['content']['published'] = $data['inputtime'];
		$option['content']['publishedby'] = 1;
		$option['content']['modified'] = $data['updatetime'];
		$option['content']['modifiedby'] = 1;
		$option['content']['pv'] = rand(2000,5000);
		$option['content']['allowcomment'] = 0;
		$option['content']['tags'] = substr($data['keywords'],0,200);
		$option['content']['sourceid'] = 0;

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
	 *  [根据typeids的值插入到对应的栏目表]
	 */
	function getcatid($typeids)
	{
		$typeids = intval($typeids);
		if(in_array($typeids, array(23))){
			$catid = 17;
		}else if(in_array($typeids, array(31,32,33,34,35,36,37,38,39))){
			$catid = 18;
		}
		return $catid;
	}
	/**
	 * [数据写入到cmstop_video表]
	 */
	function insertarticle($contentid,$data){
		$table = "cmstop_article";
		$option['article']['contentid'] = $contentid;
		$option['article']['description'] = $data['description'] ? addslashes(substr($data['description'],0,255)): null;
		$option['article']['description2'] = $data['description'] ? addslashes($data['description']): null;
		$option['article']['content'] = addslashes($this->datadeal->dealcontent($data['content']));
		$option['article']['author'] = null;
		$option['article']['id'] = intval($data['id']);
		$option['article']['pics'] = $data['pics'] ? substr($data['pics'],0,255) : null;
		$option['article']['url'] = $data['url'] ? substr($data['url'],0,255) : null;
		$option['article']['index_template'] = $data['index_template'] ? substr($data['index_template'],0,255) : null;
		$option['article']['atype'] = $data['atype'] ? substr($data['atype'],0,255) : null;
		$option['article']['arratype'] = $data['arratype'] ? substr($data['arratype'],0,255) : null;
		$option['article']['specialid'] = $data['specialid'] ? substr($data['specialid'],0,255) : null;
		$option['article']['peopleid'] = $data['peopleid'] ? substr($data['peopleid'],0,255) : null;
		$option['article']['orgid'] = $data['orgid'] ? substr($data['orgid'],0,255) : null;
		$option['article']['rwzid'] = $data['rwzid'] ? substr($data['rwzid'],0,255) : null;
		$option['article']['opinion'] = $data['opinion'] ? substr($data['opinion'],0,255) : null;
		$option['article']['province'] = $data['province'] ? substr($data['province'],0,255) : null;
		$insertsql = $this->datadeal->getsql($table,$option['article']);
		$res = $this->ndb->insert($insertsql);
		// var_dump($this->ndb->error());
		if($res){
			self::insertsearch($contentid,$data['content']);
			self::insertproperty($contentid,$data);
			self::inserttags($contentid,$data);
			self::insertlog($contentid,$data);
		}
	}	

	/*处理搜索数据*/
	function insertsearch($contentid,$content){

		$idata['contentid'] = $contentid;
		$idata['content'] = addslashes($content);
		
		$this->search->insert($idata);
	}

	/*处理地区属性关系数据*/
	function insertproperty($contentid,$data){
		$array = explode(',',$data['province']);
		if(!empty($array)){
			foreach ($array as $value) {
				$proid = $this->district[$value];
				if($proid ){
					$idata['contentid'] = $contentid;
					$idata['proid'] = $proid;
					$this->property->insert($idata);
				}else{
					continue;
				}

			}
		}
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


	/*日志记录*/
	function insertlog($contentid,$data){
		$idata = array(
										'type'=>'special',
										'newid'=>$contentid,
										'oldid'=>$data['id'],
										'oldurl'=>$data['url'],
										'message'=>'专题数据迁移，newid为新站contentid，oldid为旧站id',
										);
		$this->log->insert($idata);
	}

	/*清理迁移数据，还原数据表*/
	public	function clear(){
		$this->ndb->delete("delete from cmstop_article where `contentid` in(select contentid from cmstop_article where `catid` in(17,18))");
		$this->datadeal->auto_increment('cmstop_article');
		write_file("cmstop_article","删除表：cmstop_article的contentid=$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from $this->_table where `catid` in(17,18)");
		$this->datadeal->auto_increment($this->_table);
		write_file("cmstop_content","删除表：$this->_table的contentid=$contentid的用户数据。设置主键自增！");

		$this->ndb->delete("delete from cmstop_removal_log where `type`='special'");
		$this->datadeal->auto_increment('cmstop_removal_log');

	}

}