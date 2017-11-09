<?php
/**
 * 示例模型
 */
 class Datadeal extends Model 
{
	function __construct() {
		parent::__construct();
	}

		/**
	 * 设置数据表的自增开始id值
	 * @AuthorHTL
	 * @DateTime  2017-11-01T09:20:50+0800
	 * @param     [string]                   $tablename [表名]
	 * @param     [number]                   $value     [自增的开始值]
	 * @return    [boolean]                             [结果]
	 */
	public function auto_increment($tablename,$value=false)
	{
		if(!$value){
			$get_primary = $this->ndb->get_primary($tablename);
			$maxid = $this->ndb->get("select max(`$get_primary`) maxid from $tablename");
			$value = $maxid['maxid'] + 1;
		}
		$return = $this->ndb->exec("alter table $tablename auto_increment=$value");
		return $return;
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [string]                   $tablename [表名]
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	public function imagehandle($img){
		$host = "upload.reformdata.org";
		if(preg_match("/www.reformdata.org/",$img)){
			$img = preg_replace("/www.reformdata.org/",$host,$img);
		}
		return $img;
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [string]                   $tablename [表名]
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回处理后的文章内容]
	 */
	public function dealcontent($content){
			$pattern="/<[img|IMG].*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/";
			preg_match_all($pattern,$content,$match);
			foreach ($match[1] as $k => $v) {
				$src = self::imagehandle($v);
				$content = str_replace($v, $src, $content);
			}

		return $content;
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [string]                   $tablename [表名]
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	public function getsql($tablename,$data){
		foreach ($data as $key => $value) {
			empty($fields)? $fields = "`$key`" : $fields .= ",`$key`";
			!isset($values)? $values = "'$value'" : $values .= ",'$value'";
		}
		$sql = "insert into $tablename ($fields) values($values)";
		return $sql;
	}
}