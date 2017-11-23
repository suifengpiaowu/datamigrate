<?php
/**
 * 记录迁移的新旧数据对应关系记录到cmstop数据库中，cmstop_tag
 */
class Related extends Model
{
	function __construct() {
		parent::__construct();
		$this->_table = 'cmstop_related';
		$this->datadeal = model('Datadeal');
	}

	/*处理 专题相关 关系数据*/
	function insertrelated($contentid,$specialid){
		$sql = "SELECT c.`contentid`,c.`title`,c.`thumb`,c.`url`,c.`published` FROM cmstop_content c,cmstop_article a WHERE c.`contentid`=a.`contentid` AND c.`catid` in (17,18) AND a.`id` IN($specialid) ORDER BY c.`published` DESC";
		$data = $this->ndb->select($sql);
		if(!empty($data)){
			foreach ($data as $k => $value) {
				$insertdata['contentid'] = $contentid;
				$insertdata['orign_contentid'] = $value['contentid'];
				$insertdata['title'] = $value['title'];
				$insertdata['thumb'] = $value['thumb'];
				$insertdata['url'] = $value['url'];
				$insertdata['time'] = date('Y-m-d H:i:s',$value['published']);
				$insertdata['sort'] = intval($k+1);

				self::insert($insertdata);
			}
			return true;
		}
	}

	/**
	 * 根据传入的数据和表明，获取插入sql语句
	 * @AuthorHTL
	 * @DateTime  2017-11-01T10:52:21+0800
	 * @param     [array]                   $data       [数据]
	 * @return    [string]                              [返回插入的sql语句]
	 */
	function insert($data){
		$data = array(
										'contentid'=> intval($data['contentid']),
										'orign_contentid'=> $data['orign_contentid'] ? $data['orign_contentid'] : null,
										'title'=> isset($data['title']) ? substr($data['title'],0,255) : null,
										'thumb'=> isset($data['thumb']) ? substr($data['thumb'],0,255) : null,
										'url'=> isset($data['url']) ? substr($data['url'],0,255) : null,
										'time'=> $data['time'] ? $data['time'] : null,
										'sort'=> $data['sort'] ? $data['sort'] : null,
										);
		$insert = $this->datadeal->getsql($this->_table,$data);

		$res = $this->ndb->insert($insert);
		if($res){
			$res = $this->ndb->update("UPDATE cmstop_content SET related=1 WHERE contentid=".$data['contentid']);
			$log = $data['type']."日志记录：".$res.'==>'.serialize($data)."r\n";
			write_file($data['type'],$log,true);
		}
	}

}