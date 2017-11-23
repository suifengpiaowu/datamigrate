<?php
/**
 * 文章数据迁移
 */
class LinkController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Link');
	}

	public function run(){
		echo "Link Run start \r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Link Clear start \r\n";
		$this->selfmodel->clear();
		exit("Link Clear stop \r\n");
	}
}