<?php
/**
 * 文章数据迁移
 */
class VideoController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Video');
	}

	public function run(){
		echo "Video Run start \r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Video Clear start \r\n";
		$this->selfmodel->clear();
		exit("Clear stop \r\n");
	}
}