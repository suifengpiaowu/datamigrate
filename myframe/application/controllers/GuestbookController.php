<?php
/**
 * 用户处理方法
 */
class GuestbookController extends Controller {

	function __construct() {
		$this->guestbook = new guestbook;
	}

	public function run(){
		$this->guestbook->run();
	}

	public function clear(){
		$this->guestbook->clear();
	}
}