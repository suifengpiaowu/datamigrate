<?php
/**
 * 图书数据迁移
 */
class BookController extends Controller {
	public $selfmodel;
	function __construct() {
		$this->selfmodel = model('Book');
	}

	public function run(){
		echo "Book Run start\r\n";
		$this->selfmodel->run();
	}

	public function clear(){
		echo "Book Clear start \r\n";
		$this->selfmodel->clear();
		exit("Book Clear stop\r\n");
	}
}