<?php
/**
 * 事件数据迁移
 */
class EventController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Event');
	}

	public function run(){
		echo "Event Run start \r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Event Clear start \r\n";
		$this->selfmodel->clear();
		exit("Clear stop \r\n");
	}
}