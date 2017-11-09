<?php
/**
 * 用户处理方法
 */
class RemovalController extends Controller {

	function __construct() {
		$this->article = model('Article');
		$this->picture = model('Picture');
		$this->video = model('Video');
		$this->special = model('Special');
	}

	public function runarticle(){
		$this->article->run();
	}

	public function cleararticle(){
		$this->article->clear(1);
	}

	public function runpicture(){
		$this->picture->run();
	}

	public function clearpicture(){
		$this->picture->clear();
	}

	public function runvideo(){
		$this->video->run();
	}

	public function clearvideo(){
		$this->video->clear();
	}

	public function runspecial(){
		$this->special->run();
	}

	public function clearspecial(){
		$this->special->clear();
	}
}