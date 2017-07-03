<?php
/**
 * 示例模型
 */
class Guestbook extends Model {
	function __construct() {
		parent::__construct();

		// 引入数据库模型
		$this->odb = new db(config("olddb"));
		$this->ndb = new db(config("newdb"));

		//引入日志模型
		helper("log");
		$this->log = new log(); 
		$this->filename = date('Y-m-d_',time()).'guestbook.log';

		//定义每次查询的数据条数
		$this->length = 10;
	}

	/*数据迁移运行*/
	public	function run(){
		//获取数据总条数
		$total = $this->odb->page("select count(id) total from sj_book")[0]['total'];
		//获取数据总页数
		$pages = ceil($total/$this->length); 
		
		for ($i=1; $i <= $pages; $i++) { 
			$data = $this->odb->page("select * from sj_book order by id asc",$i,$this->length);
			foreach ($data as $key => $value) {
				echo '<pre/>';
				if(!empty($value['realname'])){
					$userid = $this->ndb->get('select userid from cmstop_member_detail where name like "%'.$value['realname'].'%"')['userid'];
				}

				$value['userid'] = $userid ? $userid : '' ;
				$value['username'] = trim($value['realname']);
				$value['isshow'] = intval($value['gongkai']) == 1 ? 1 : 0;
				$value['reply'] = $value['replay'] ? $value['replay'] : '';
				$value['replyer'] = $value['adminname'] ? $value['adminname'] : '';
				$value['addtime'] = $value['indate'] ? self::enstotime($value['indate']) : '';
				$value['replytime'] = $value['replaydate'] ? self::enstotime($value['replaydate']) : '';
				
				self::insertcmstopguestbook($value);
			}
		}
	}


	/*清理迁移数据，还原数据表*/
	public	function clear(){
		$this->ndb->delete("delete from cmstop_guestbook where `gid`>0");
		self::auto_increment('cmstop_guestbook',1);
		self::log('删除表：cmstop_guestbook的主键gid>0的用户数据。设置主键从1开始自增！');
	}

	/*写入数据到目标数据库*/
	public function insertcmstopguestbook($data){
		$option['guestbook']['typeid'] = 1;//网页
		$option['guestbook']['title'] = substr($data['title'],0,100);
		$option['guestbook']['content'] = $data['message'] ? $data['message'] : '';
		$option['guestbook']['userid'] = $data['userid'];
		$option['guestbook']['username'] = $data['username'] ? $data['username'] : '';
		$option['guestbook']['gender'] = '1';
		$option['guestbook']['email'] = '';
		$option['guestbook']['qq'] = '';
		$option['guestbook']['msn'] = '';
		$option['guestbook']['telephone'] = '';
		$option['guestbook']['mobile'] = '';
		$option['guestbook']['address'] = '';
		$option['guestbook']['homepage'] = '';
		$option['guestbook']['isview'] = 1;
		$option['guestbook']['isshow'] = $data['isshow'];
		$option['guestbook']['ip'] = '127.0.0.1';
		$option['guestbook']['addtime'] = $data['addtime'];
		$option['guestbook']['reply'] = $data['replay'];
		$option['guestbook']['replyer'] = $data['replyer'];
		$option['guestbook']['replytime'] = $data['replytime'];
		$option['guestbook']['department'] = $data['department'];
		$guestbook_sql = self::getsql('cmstop_guestbook',$option['guestbook']);
		$guestbookid = $this->ndb->insert($guestbook_sql);
		
		if($guestbookid){
			self::log('导入cmstop_guestbook成功:'.$guestbookid);
		}else{
			self::log('源ID:'.$data['id'].':导入cmstop_guestbook时失败！');
			self::log('INSERT语句：'.$guestbook_sql);
			var_dump($this->ndb->error());
		}
	}

	private function enstotime($time){
		$array = explode(' ', $time);
		$ymd = explode('/', $array[0]);
		$hmi = explode(':', $array[1]);
		$year = $ymd[0];
		$month = $ymd[1];
		$day = $ymd[2];
		$h = $hmi[0];
		$m = $hmi[1];
		$i = $hmi[2];
		$strtime = "$year-$month-$day $h:$m:$i";
		return strtotime($strtime);
	}


	/*自增重置*/
	private function auto_increment($tablename,$value=false)
	{
		if(!$value){
			$get_primary = $this->ndb->get_primary($tablename);
			$maxid = $this->ndb->get("select max(`$get_primary`) maxid from $tablename");
			$value = $maxid['maxid'] + 1;
		}
		$return = $this->ndb->exec("alter table $tablename auto_increment=$value");
		return $value;
	}

	/*根据传入的数据和表名，获取插入sql语句*/
	private function getsql($tablename,$data){
		foreach ($data as $key => $value) {
			 empty($fields)? $fields = "`$key`" : $fields .= ",`$key`";
			 !isset($values)? $values = "'$value'" : $values .= ",'$value'";
		}
		$sql = "insert into $tablename ($fields) values($values)";
		return $sql;
	}

	/*日志记录*/
	private function log($string){
		echo $string.'<br/>';
		// write_file(ROOT.DS."tmp".DS."logs".DS.$this->datatime."article.log",$string,true);
		$this->log->set_options(array('log'=>true,'filename'=>$this->filename));
		$this->log->append($string, log::INFO);
	}
}