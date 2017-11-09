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

	}

	/*数据迁移运行*/
	public	function run(){
		$this->runvideo();
	}

	/*数据迁移运行*/
	public	function runvideo(){

		//获取数据总条数
		$total = $this->odb->page("select count(a.id) total from reform_special a")[0]['total'];

		//获取数据总页数
		$pages = ceil($total/$this->length); 
		echo "ALL pages $pages </br>"."\r\n";
		$field = "a.id,a.title,a.subtitle,a.thumb,a.keywords,a.url,a.inputtime,a.updatetime,a.description,a.content,a.specialid";
		for ($i=1; $i <= $pages; $i++) { 
			$sql = "select $field from reform_special a order by a.id asc";
			$data = $this->odb->page($sql,$i,$this->length);
			// console($i);
			// var_dump($this->ndb->error());
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
			echo " $i page complete </br>"."\r\n";
		}
		echo "ALL complete </br>"."\r\n";
	}

	/*写入数据到目标数据库*/
	public function insertcontent($data){
		$option['content']['catid'] = 79;//专题栏目
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
	 * [数据写入到cmstop_video表]
	 */
	function insertarticle($contentid,$data){
		$table = "cmstop_article";
		$option['article']['contentid'] = $contentid;
		$option['article']['description'] = $data['description'] ? addslashes(substr($data['description'],0,255)): null;
		$option['article']['content'] = addslashes($this->datadeal->dealcontent($data['content']));
		$option['article']['author'] = null;
		$option['article']['id'] = intval($data['id']);
		$option['article']['specialid'] = $data['specialid'] ? substr($data['specialid'],0,255) : null;
		// $option['article']['peopleid'] = $data['peopleid'] ? substr($data['peopleid'],0,255) : null;
		// $option['article']['orgid'] = $data['orgid'] ? substr($data['orgid'],0,255) : null;
		// $option['article']['dtypes'] = $data['dtypes'] ? substr($data['dtypes'],0,255) : null;
		// $option['article']['atype'] = $data['atype'] ? substr($data['atype'],0,255) : null;
		// $option['article']['acid'] = $data['acid'] ? substr($data['acid'],0,255) : null;
		// $option['article']['title_prefix'] = $data['title_prefix'] ? substr($data['title_prefix'],0,255) : null;
		// $option['article']['downfiles'] = $downfiles = self::getdownfiles($data['downfiles']);
		$insertsql = $this->datadeal->getsql($table,$option['article']);
		$res = $this->ndb->insert($insertsql);
		// var_dump($this->ndb->error());
		if($res){
			self::insertsearch($contentid,$data['content']);
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
										'type'=>'special',
										'newid'=>$contentid,
										'oldid'=>$data['id'],
										'oldurl'=>$data['url'],
										'message'=>'视频数据迁移，newid为新站contentid，oldid为旧站id',
										);
		$this->log->insert($idata);
	}

	/*清理迁移数据，还原数据表*/
	public	function clear(){

		$contentid = 26662;
		$this->ndb->delete("delete from cmstop_article where `contentid`>$contentid");
		$this->datadeal->auto_increment('cmstop_article',($contentid+1));
		write_file("cmstop_article","删除表：cmstop_article的contentid=$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from $this->_table where `contentid`>$contentid");
		$this->datadeal->auto_increment($this->_table,($contentid+1));
		write_file("cmstop_content","删除表：$this->_table的contentid=$contentid的用户数据。设置主键自增！");

		$this->tags->delete($contentid);
		$this->property->delete($contentid);

		$this->ndb->delete("delete from cmstop_removal_log where `type`='special'");
		$this->datadeal->auto_increment('cmstop_removal_log');

	}

}