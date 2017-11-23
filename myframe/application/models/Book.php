<?php


/**
 * 示例模型
 */
class Book extends Model {
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
	}

	/*数据迁移运行*/
	public	function run(){
		$this->runvideo();
	}

	/*数据迁移运行*/
	public	function runvideo(){

		//获取数据总条数
		$total = $this->odb->page("select count(a.id) total from reform_book a,reform_book_data d where a.id=d.id")[0]['total'];

		//获取数据总页数
		$pages = ceil($total/$this->length); 
		echo "ALL-line $total ALL pages $pages </br>"."\r\n";
		$field = "a.id,a.catid,a.title,a.subtitle,a.thumb,a.keywords,a.copyfrom,a.url,a.inputtime,a.updatetime,a.description,a.isbn,a.author,a.price,a.dtype,a.typeid,a.atype,a.arratype,a.area,a.agency,a.qihao,a.specialid,a.peopleid,a.orgid,d.content";
		for ($i=1; $i <= $pages; $i++) { 
			$sql = "select $field from reform_book a,reform_book_data d where a.id=d.id order  by a.id asc";

			$data = $this->odb->page($sql,$i,$this->length);
  		// var_dump($this->odb->error());
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
		$option['content']['catid'] = 4;//图书栏目
		$option['content']['modelid'] = 11;//综合模型
		$option['content']['title'] = addslashes(substr($data['title'],0,200));
		$option['content']['subtitle'] = addslashes(substr($data['subtitle'],0,120));
		$option['content']['thumb'] = $this->datadeal->imagehandle($data['thumb']);
		$option['content']['status'] = intval($data['typeids']) == 19 ? 0 : 6;
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
			$res = self::insertsynthetical($contentid,$data);
			return $contentid;
		}else{
			console($this->ndb->error());
			return false;
		}
	}
	
	/**
	 * [数据写入到cmstop_synthetical表]
	 */
	function insertsynthetical($contentid,$data){
		// var_dump($data);
		$table = "cmstop_synthetical";
		$option['synthetical']['kindid'] = 1; //数据类型1图书与,2人物,3机构,4事件
		$option['synthetical']['contentid'] = $contentid;
		$option['synthetical']['description'] = $data['description'] ? addslashes(substr($data['description'],0,255)): null;
		$option['synthetical']['description2'] = $data['description'] ? addslashes($data['description']): null;
		$option['synthetical']['content'] = addslashes($this->datadeal->dealcontent($data['content']));
		$option['synthetical']['bookisbn'] = $this->datadeal->imagehandle($data['bookisbn']);
		$option['synthetical']['bookauthor'] = $this->datadeal->imagehandle($data['author']);
		$option['synthetical']['bookinputtime'] = $this->datadeal->imagehandle($data['inputtime']);
		$option['synthetical']['bookpice'] = $this->datadeal->imagehandle($data['price']);
		$option['synthetical']['id'] = intval($data['id']);
		$option['synthetical']['dtype'] = $data['dtype'] ? substr($data['dtype'],0,255) : null;
		$option['synthetical']['typeid'] = $data['typeid'] ? substr($data['typeid'],0,255) : null;
		$option['synthetical']['url'] = $data['url'] ? substr($data['url'],0,255) : null;
		$option['synthetical']['atype'] = $data['atype'] ? substr($data['atype'],0,255) : null;
		$option['synthetical']['arratype'] = $data['arratype'] ? substr($data['arratype'],0,255) : null;
		$option['synthetical']['area'] = $data['area'] ? substr($data['area'],0,255) : null;
		$option['synthetical']['agency'] = $data['agency'] ? substr($data['agency'],0,255) : null;
		$option['synthetical']['qihao'] = $data['qihao'] ? substr($data['qihao'],0,255) : null;
		$option['synthetical']['specialid'] = $data['specialid'] ? substr($data['specialid'],0,255) : null;
		$option['synthetical']['peopleid'] = $data['peopleid'] ? substr($data['peopleid'],0,255) : null;
		$option['synthetical']['orgid'] = $data['orgid'] ? substr($data['orgid'],0,255) : null;
		$insertsql = $this->datadeal->getsql($table,$option['synthetical']);
		$res = $this->ndb->insert($insertsql);
		// var_dump($this->ndb->error());
		if($res){
			$this->property->insertbook($contentid,$data['catid']);
			$this->related->insertrelated($contentid,$data['specialid']);
			$this->property->insertdistrict($contentid,$data['specialid']);

			self::insertsearch($contentid,$data['content']);
			self::inserttags($contentid,$data);
			self::insertlog($contentid,$data);
		}else{
			var_dump($this->ndb->error());
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
										'type'=>'book',
										'newid'=>$contentid,
										'oldid'=>$data['id'],
										'oldurl'=>$data['url'],
										'message'=>'图书数据迁移，newid为新站contentid，oldid为旧站id',
										);
		$this->log->insert($idata);
	}

	/*清理迁移数据，还原数据表*/
	public	function clear(){

		$this->ndb->delete("delete from cmstop_synthetical where `kindid`=1");
		$this->datadeal->auto_increment('cmstop_synthetical');
		write_file("cmstop_synthetical","删除表：cmstop_synthetical的contentid=$contentid的用户数据。设置主键自增！");
		$this->ndb->delete("delete from $this->_table where `catid`=4");
		$this->datadeal->auto_increment($this->_table);
		write_file("cmstop_content","删除表：$this->_table的catid=4的图书数据。设置主键自增！");

		$this->ndb->delete("delete from cmstop_removal_log where `type`='book'");
		$this->datadeal->auto_increment('cmstop_removal_log');

	}

}