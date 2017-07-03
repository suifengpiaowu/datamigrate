<?php
/**
 * 首页
 */
class MigrateController extends Controller {

	function __construct() {
		echo "<pre>";
		$this->migrate = new migrate;
		$this->odb = new db(config("olddb"));
		$this->ndb = new db(config("newdb"));
		helper("log");
		$this->log = new log(); 
		$this->datatime = date('Y-m-d_',time());
	}

	public function article(){
		//获取参数	
		$ototal = $this->odb->select("select count(id) total from shehuijianshely_user2009")[0][total];
		var_dump($ototal);
		$this->odb->select("");
		// $this->insertLog('123123123123');
	}
	
	private function insertLog($string){
		// write_file(ROOT.DS."tmp".DS."logs".DS.$this->datatime."article.log",$string,true);
		$this->log->set_options(array('log'=>true,'filename'=>$this->datatime.'article.log'));
		$this->log->append($string, log::INFO);
	}
}