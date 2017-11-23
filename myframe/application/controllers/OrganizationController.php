<?php
/**
 * 机构数据迁移
 */
class OrganizationController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Organization');
	}

	public function run(){
		echo "Organization Run start\r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Organization Clear start \r\n";
		$this->selfmodel->clear();
		exit("Organization Clear stop\r\n");
	}
}