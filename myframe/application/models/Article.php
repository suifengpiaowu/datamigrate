<?php


/**
 * 示例模型
 */
class Article extends Model {
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
		$total = $this->odb->page("select count(a.id) total from reform_article a,reform_article_data d where a.id=d.id and a.islink!=1")[0]['total'];

		//获取数据总页数
		$pages = ceil($total/$this->length); 
		echo "ALL pages $pages</br>"."\r\n";
		$field = "a.id,a.catid,a.title,a.subtitle,a.thumb,a.keywords,a.copyfrom,a.url,a.status,a.inputtime,a.updatetime,a.dtypes,a.description,a.author,a.typeid,a.bookid,a.specialid,a.peopleid,a.orgid,a.atype,a.arratype,a.acid,a.title_prefix,a.downfiles,d.content";
		for ($i=1; $i <= $pages; $i++) { 
			$sql = "select $field from reform_article a,reform_article_data d where a.id=d.id and a.islink!=1 order by a.id asc";
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
		$catid = self::getcatid(intval($data['catid']));
		echo ">>>>>>".$data['catid']."====>".$catid."<<<<<<";
		$option['content']['catid'] = $catid ? $catid : 65 ;
		$option['content']['modelid'] = 1;
		$option['content']['title'] = addslashes(substr($data['title'],0,200));
		$option['content']['subtitle'] = addslashes(substr($data['subtitle'],0,120));
		$option['content']['thumb'] = $this->datadeal->imagehandle($data['thumb']);
		$option['content']['status'] = in_array($data['status'], array(0,1)) ? 0 : 6;
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
			$res = self::insertarticle($contentid,$data);
			return $contentid;
		}else{
			console($this->ndb->error());
			return false;
		}
	}

	/**
	 * [数据写入到cmstop_article表]
	 */
	function insertarticle($contentid,$data){
		$table = "cmstop_article";
		$option['article']['contentid'] = $contentid;
		$option['article']['description'] = $data['description'] ? addslashes(substr($data['description'],0,255)): null;
		$option['article']['description2'] = $data['description'] ? addslashes($data['description']): null;
		$option['article']['content'] = addslashes($this->datadeal->dealcontent($data['content']));
		$option['article']['author'] = $data['author'] ? substr($data['author'],0,20) : null;
		$option['article']['id'] = intval($data['id']);
		$option['article']['typeid'] = $data['typeid'] ? substr($data['typeid'],0,255) : null;
		$option['article']['bookid'] = $data['bookid'] ? substr($data['bookid'],0,255) : null;
		$option['article']['specialid'] = $data['specialid'] ? substr($data['specialid'],0,255) : null;
		$option['article']['peopleid'] = $data['peopleid'] ? substr($data['peopleid'],0,255) : null;
		$option['article']['orgid'] = $data['orgid'] ? substr($data['orgid'],0,255) : null;
		$option['article']['dtypes'] = $data['dtypes'] ? substr($data['dtypes'],0,255) : null;
		$option['article']['atype'] = $data['atype'] ? substr($data['atype'],0,255) : null;
		$option['article']['acid'] = $data['acid'] ? substr($data['acid'],0,255) : null;
		$option['article']['title_prefix'] = $data['title_prefix'] ? substr($data['title_prefix'],0,255) : null;
		$option['article']['downfiles'] = $downfiles = self::getdownfiles($data['downfiles']);

		$insertsql = $this->datadeal->getsql($table,$option['article']);
		$res = $this->ndb->insert($insertsql);
		// console($this->ndb->error());
		if($res){
			self::insertsearch($contentid,$data);
			self::insertproperty($contentid,$data);
			$this->related->insertrelated($contentid,$data['specialid']);
			$this->property->insertdistrict($contentid,$data['specialid']);
			self::inserttags($contentid,$data);
			self::insertlog($contentid,$data);
		}else{
			// self::clear($contentid);
		}
	}

	/*处理搜索数据*/
	function insertsearch($contentid,$data){

		$idata['contentid'] = $contentid;
		$idata['content'] = $data['content'];

		
		$this->search->insert($idata);
	}

	/*处理来源数据*/
	function insertsource($data){
		
		$name = explode('|', $data['copyfrom'])[0];
		
		$return = $this->source->insert($name);
		return $return;
	}

	/*处理文章属性数据*/
	function insertproperty($contentid,$data){
		
		
		$idata['contentid'] = $contentid;
		$idata['proid'] = $this->propertyids[$data['dtypes']];
		
		$this->property->insert($idata);
	}

	/*处理tags关键词数据处理*/
	function inserttags($contentid,$data){
		
		$idata['contentid'] = $contentid;
		$idata['tags'] = $data['keywords'];

		
		$this->tags->insert($idata);
	}


	/*处理日志数据*/
	function insertlog($contentid,$data){
		$idata = array(
										'type'=>'article',
										'newid'=>$contentid,
										'oldid'=>$data['id'],
										'oldurl'=>$data['url'],
										'message'=>'文章内容数据迁移新旧站文章id对应',
										);
		
		$this->log->insert($idata);
	}

	/**
	 * [处理新旧栏目的对应关系]
	 */
	function getcatid($catid)
	{
		$category = $this->category;
		return $category[$catid];
	}

	/**
	 * [处理附件的数据格式，替换附件地址]
	 */
	function getdownfiles($downfiles)
	{
		$array = string2array($downfiles);
		if(!empty($array)){
			foreach ($array as $k => $v) {
					$v['fileurl'] = $this->datadeal->imagehandle($v['fileurl']);
					$return[$k] = $v;
			}
		}

		$return = isset($return) ? serialize($return) : '';
		return $return;
	}

	/*清理迁移数据，还原数据表*/
	public	function clear(){

		$contentid = 1;
		$this->ndb->delete("delete from cmstop_article where `contentid`>0");
		$this->datadeal->auto_increment('cmstop_article',1);
		write_file("cmstop_article","删除表：cmstop_article的contentid=$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from $this->_table where `contentid`>0");
		$this->datadeal->auto_increment($this->_table,1);
		write_file("cmstop_content","删除表：$this->_table的contentid=$contentid的用户数据。设置主键自增！");

		$this->tags->delete();
		$this->datadeal->auto_increment('cmstop_tag');
		$this->source->delete();
		$this->datadeal->auto_increment('cmstop_source');

		$this->ndb->delete("delete from cmstop_removal_log");
		$this->datadeal->auto_increment('cmstop_removal_log');
	}

}