<?php
/**
 * 人物数据迁移
 */
class PeopleController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('People');
	}

	public function run(){
		echo "People Run start\r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "People Clear start \r\n";
		$this->selfmodel->clear();
		exit("People Clear stop\r\n");
	}
}