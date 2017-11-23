<?php
/**
 * 文章数据迁移
 */
class ArticleController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Article');
	}

	public function run(){
		echo "Article Run start \r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Article Clear start \r\n";
		$this->selfmodel->clear();
		exit("Article Clear stop \r\n");
	}
}