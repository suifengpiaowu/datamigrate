<?php
/**
 * 图集数据迁移
 */
class PictureController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Picture');
	}

	public function run(){
		echo "Picture Run start \r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Picture Clear start \r\n";
		$this->selfmodel->clear();
		exit("Clear stop \r\n");
	}
}