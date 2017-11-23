<?php
/**
 * 专题数据迁移
 */
class SpecialController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Special');
	}

	public function run(){
		echo "Special Run start\r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Special Clear start \r\n";
		$this->selfmodel->clear();
		exit("Special Clear stop\r\n");
	}
}