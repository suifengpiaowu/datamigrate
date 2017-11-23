<?php
/**
 * 用户处理方法
 */
class UserController extends Controller {

	function __construct() {
		$this->user = new user;
	}

	public function runmember(){
		echo '<html xmlns="http://www.w3.org/1999/xhtml">';
		echo '<div style="border: 1px solid #00f;height: auto;overflow-wrap:break-word;width:100%;word-wrap:break-word;word-break:break-all;" >';
		$this->user->run();
		echo "</div>";
	}

	public function runadminmember(){
		echo '<html xmlns="http://www.w3.org/1999/xhtml">';
		echo '<div style="border: 1px solid #00f;height: auto;overflow-wrap:break-word;width:100%;word-wrap:break-word;word-break:break-all;" >';

		$this->user->runadminmember();
		echo "</div>";
	}

	public function clear(){
		$this->user->clear();
	}

	public function test(){
		insertlog('kaoshi','ceshi');
		console(model('Migrate'));
		$password = md5('abc123');
		echo "<hr/>";
		echo md5($password.'e99a18');
		echo "<hr/>";
		echo md5(md5(substr($password,0,16)).md5(substr($password,16,32)));
	}
}