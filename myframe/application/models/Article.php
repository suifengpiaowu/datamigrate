<?php
/**
 * 示例模型
 */
class User extends Model {
	function __construct() {
		parent::__construct();

		// 引入数据库模型
		$this->odb = new db(config("olddb"));
		$this->ndb = new db(config("newdb"));

		//引入日志模型
		helper("log");
		$this->log = new log(); 
		$this->datatime = date('Y-m-d_',time());

		//定义每次查询的数据条数
		$this->length = 1;
	}

	/*数据迁移运行*/
	public	function run(){
		//获取数据总条数
		$total = $this->odb->page("select count(id) total from sj_user")[0]['total'];
		//获取数据总页数
		$pages = ceil($total/$this->length); 
		
		for ($i=1; $i <= $pages; $i++) { 
			$data = $this->odb->page("select * from sj_user order by id asc",$i,$this->length);
			self::dataprocess($data);
		}
	}

	/*数据迁移运行*/
	public	function runadminmember(){
		//获取数据总条数
		$total = $this->odb->page("select count(id) total from sj_admin")[0]['total'];
		//获取数据总页数
		$pages = ceil($total/$this->length); 
		
		for ($i=1; $i <= $pages; $i++) { 
			$data = $this->odb->page("select * from sj_admin order by id asc",$i,$this->length);
			foreach ($data as $key => $value) {
				$value['username'] = $value['adminname'];
				$value['remarks'] = $value['department'];
				$password = md5(trim($value['adminpwd']));
				$value['salt'] = substr($password,0,6);
				$value['password'] = md5($password.$value['salt']);
				$userid = self::insertnewdb($value);
				if($userid){
					$value['password'] = md5(md5(substr($password,0,16)).md5(substr($password,16,32)));
					self::insertcmstopadmin($userid,$value);
				}
			}
		}
	}


	/*清理迁移数据，还原数据表*/
	public	function clear(){
		$this->ndb->delete("delete from cmstop_admin where `userid`>10");
		$this->ndb->delete("delete from cmstop_member where `userid`>10");
		$this->ndb->delete("delete from cmstop_member_detail where `userid`>10");
		self::auto_increment('cmstop_member',11);
		self::auto_increment('cmstop_member_detail',11);
		self::auto_increment('cmstop_admin',11);
		self::log('删除表：cmstop_member的主键userid>10的用户数据。设置主键从11开始自增！');
		self::log('删除表：cmstop_member_detail的主键userid>10的用户数据。设置主键从11开始自增！');
	}

	/*梳理来源库的数据*/
	public	function dataprocess($data){
		foreach ($data as $key => $value) {
			
			$password = md5(trim($value['password']));
			$value['salt'] = substr($password,0,6);
			$value['password'] = md5($password.$value['salt']);
			self::insertnewdb($value);
		}
	}

	/*写入数据到目标数据库*/
	public function insertnewdb($data){
		$option['member']['username'] = substr($data['username'],0,32);
		$option['member']['password'] = substr($data['password'],0,32);
		$option['member']['email'] = $data['email'] ? substr($data['email'],0,100) : $option['member']['username'].'@test.com';
		$option['member']['salt'] = substr($data['salt'],0,6);
		$option['member']['avatar'] = 0;
		$option['member']['regip'] = '127.0.0.1';
		$option['member']['regtime'] = time();
		$option['member']['lastloginip'] = '127.0.0.1';
		$option['member']['lastlogintime'] = time();
		$option['member']['logintimes'] = 0;
		$option['member']['posts'] = 0;
		$option['member']['comments'] = 0;
		$option['member']['pv'] = 0;
		$option['member']['credits'] = 0;
		$option['member']['status'] = 1;
		$option['member']['groupid'] = 6;

		$member_sql = self::getsql('cmstop_member',$option['member']);
		$insertid = $this->ndb->insert($member_sql);
		
		if($insertid){
			$option['member_detail']['userid'] = $insertid;
			$option['member_detail']['name'] = $data['realname'] ? substr($data['realname'],0,30) : '';
			$option['member_detail']['sex'] = 1;
			$option['member_detail']['birthday'] = date('Y-m-d',time());
			$option['member_detail']['telephone'] = $data['tel'] ? substr($data['tel'],0,15) : '';
			$option['member_detail']['mobile'] = '';
			$option['member_detail']['job'] = '';
			$option['member_detail']['address'] = $data['address'] ? substr($data['address'],0,100) : '';
			$option['member_detail']['zipcode'] = $data['postcode'] ? substr($data['postcode'],0,6) : '';
			$option['member_detail']['qq'] = '';
			$option['member_detail']['msn'] = '';
			$option['member_detail']['authstr'] = '';
			$option['member_detail']['remarks'] = $data['card'] ? substr($data['card'],0,255) : '';
			$option['member_detail']['mobileauth'] = 0;

			$member_detail_sql = self::getsql('cmstop_member_detail',$option['member_detail']);
			$insert2 = $this->ndb->insert($member_detail_sql);
			if($insert2){
				self::log('用户源ID:'.$data['id'].':导入成功->cmstop_member,cmstop_member-detail>>>CONTENTID:'.$insertid);
				return $insertid;
			}else{
				$this->ndb->delete("delete from cmstop_member where `userid`=$insertid");
				self::auto_increment('cmstop_member');
				self::log('用户源ID:'.$data['id'].':导入cmstop_member_detial时失败！');
				self::log('INSERT语句：'.$member_detail_sql);
			}
		}else{
			self::log('用户源ID:'.$data['id'].':导入cmstop_member时失败！');
			self::log('INSERT语句：'.$member_sql);
			var_dump($this->ndb->error());
		}
	}


	/*写入数据到目标数据库*/
	public function insertcmstopadmin($userid,$data){
		$option['admin']['userid'] = $userid;
		$option['admin']['roleid'] = 1;
		$option['admin']['departmentid'] = 2;
		$option['admin']['name'] = substr($data['username'],0,20);
		$option['admin']['sex'] = 1;
		$option['admin']['birthday'] = date('Y-m-d',time());
		$option['admin']['email'] = $data['email'] ? substr($data['email'],0,100) : $data['username'].'@test.com';
		$option['admin']['photo'] = '';
		$option['admin']['qq'] = '';
		$option['admin']['msn'] = '';
		$option['admin']['telephone'] = '';
		$option['admin']['mobile'] = '';
		$option['admin']['address'] = '';
		$option['admin']['zipcode'] = '';
		$option['admin']['created'] = time();
		$option['admin']['createdby'] = 1;
		$option['admin']['updated'] = time();
		$option['admin']['updatedby'] = 1;
		$option['admin']['disabled'] = 0;
		$option['admin']['pv'] = 0;
		$option['admin']['posts'] = 0;
		$option['admin']['comments'] = 0;
		$option['admin']['password'] = substr($data['password'],0,32);

		$admin_sql = self::getsql('cmstop_admin',$option['admin']);
		$insertid = $this->ndb->insert($admin_sql);
		
		if($insertid){
			self::log('用户源ID:'.$data['id'].':导入成功->cmstop_admin:'.$insertid);
			return $insertid;
		}else{
			self::log('用户源ID:'.$data['id'].':导入cmstop_admin时失败！');
			self::log('INSERT语句：'.$admin_sql);
			var_dump($this->ndb->error());
		}
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

	/*根据传入的数据和表明，获取插入sql语句*/
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
		$this->log->set_options(array('log'=>true,'filename'=>$this->datatime.'user.log'));
		$this->log->append($string, log::INFO);
	}
}