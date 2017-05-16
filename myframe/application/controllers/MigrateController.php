<?php
/**
 * 首页
 */
class MigrateController extends Controller {

	private $modelid = 1; //模型为文章:1
	function __construct() {
		echo "<pre>";
		$this->migrate = new Migrate;

		$this->category = new Category;

		$this->odb = new db(config("olddb"));
		$this->ndb = new db(config("newdb"));
		helper("log");
		$this->log = new log(); 
		$this->datatime = date('Y-m-d_',time());


		$this->offset = 0;
		$this->length = 1;
	}

	public function article(){
		//获取参数	
		$ototal = $this->odb->select("select count(ARTICLEID) total from RELEASELIB")[0][total];
		

		$oldfields = "r.ARTICLEID,r.ABSTRC,r.CONTENT,r.SOURCENAME,r.SOURCEURL,r.INPUTER,r.CREATETIME,r.PUBLISHSTATE";
		$oldfields .= "p.TITLE,p.AUTHOR,r.ABSTRC,r.CONTENT,r.SOURCENAME,r.SOURCEURL,r.INPUTER,r.CREATETIME,r.PUBLISHSTATE";
		$oldfields .= "r.ARTICLEID,r.TITLE,r.AUTHOR,r.ABSTRC,r.CONTENT,r.SOURCENAME,r.SOURCEURL,r.INPUTER,r.CREATETIME,r.PUBLISHSTATE";
		
		do {
			$oldsql = "SELECT * from RELEASELIB r LEFT JOIN PAGELAYOUT p ON p.ARTICLEID=r.ARTICLEID LEFT JOIN TYPESTRUCT t ON p.NODEID=t.NODEID WHERE t.SITEID=2 ORDER BY r.ARTICLEID DESC LIMIT $this->offset,$this->length";
			$dataold = $this->odb->select($oldsql);
			// var_dump($dataold);
			if($dataold){
				foreach ($dataold as $value) {
					$this->insertArticle($value);
				}
			}else{
				exit("数据迁移结束！");
				$this->insertLog("数据迁移结束！");
			}
		} while(false);
		
	}

	// 将旧数据查询到数据插入到新的数据库（单挑数据的处理）

	private function insertArticle($v){
		var_dump($value);

		$data = array(	'catid' => $this->category->getCatid(),  //栏目id
						'modelid' => $this->modelid,             //模型id
						'title' => addslashes($v['TITLE']),      //标题
						'subtitle' => addslashes($v['SUBTITLE']),//副标题
						'thumb'	=>	$v['PICLINKS'],              //缩略图
						$subtitle = addslashes($v['SUBTITLE']);
						// 'tags' = gettags($v['KEYWORD'],$cms_contentid,$redis,$contentid); //标签
						// $sourceid = get_sourceid($v['SOURCENAME'],$v['SOURCEURL'],$redis); //来源id
						// $source_title = addslashes($v['SOURCENAME']);                      //来源标题
					 );
		var_dump($data);


	}
	
	private function insertLog($string){
		// write_file(ROOT.DS."tmp".DS."logs".DS.$this->datatime."article.log",$string,true);
		$this->log->set_options(array('log'=>true,'filename'=>$this->datatime.'article.log'));
		$this->log->append($string, log::INFO);
	}


}