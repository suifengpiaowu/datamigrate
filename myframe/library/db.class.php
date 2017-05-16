<?php

class db
{
	static $queries = 0;
	public $options = array(), $master = array(), $slaves = array(), $slave = array(), $slave_key, $sql;
	private $dbh, $dbh_master, $dbh_slave, $error, $errno, $foreign_key_checks = 0;

	public function __construct($master = array(), $slaves = array())
	{
		$this->master = $master;
		$this->options = &$this->master;
		if($slaves) $this->slaves = $slaves;
	}
	
	public function __call($method, $args)
	{
		if(in_array($method, array('errorCode', 'errorInfo', 'getAttribute', 'lastInsertId', 'quote', 'setAttribute'), true))
		{
            if(in_array($method, array('lastInsertId')))
            {
                return $this->dbh_master()->$method();
            }
			if (isset($args[0]))
			{
				return isset($args[1]) ? $this->dbh()->$method($args[0], $args[1]) : $this->dbh()->$method($args[0]);
			}
			else 
			{
				return $this->dbh()->$method();
			}
		}
	}

    public function beginTransaction($foreign_key_checks = false)
    {
        $result = $this->dbh_master()->beginTransaction();
        if ($result && !$foreign_key_checks) {
            $this->foreign_key_checks = intval($this->dbh_master()->query("SELECT @@FOREIGN_KEY_CHECKS")->fetchColumn(0));
            $this->foreign_key_checks && $this->dbh_master()->query("SET @@FOREIGN_KEY_CHECKS=0");
        }
        return $result;
    }

    public function commit()
    {
        $result = $this->dbh_master()->commit();
        if ($this->foreign_key_checks) {
            $this->dbh_master()->query("SET @@FOREIGN_KEY_CHECKS=1");
            $this->foreign_key_checks = 0;
        }
        return $result;
    }

    public function rollBack()
    {
        $result = $this->dbh_master()->rollBack();
        if ($this->foreign_key_checks) {
            $this->dbh_master()->query("SET @@FOREIGN_KEY_CHECKS=1");
            $this->foreign_key_checks = 0;
        }
        return $result;
    }

	public static function get_instance($master = array(), $slaves = array())
	{
		static $instance;
		$key = implode('', $master);
		if (!isset($instance[$key]))
		{			
			$instance[$key] = new db($master, $slaves);

		}
		return $instance[$key];
	}

	public function connect($options = array())
	{
		try 
		{
		    $dbh = new PDO($options['driver'].':host='.$options['host'].';port='.$options['port'].';dbname='.$options['dbname'].';charset='.$options['charset'], $options['username'], $options['password'], array(PDO::ATTR_PERSISTENT => ($options['pconnect'] ? true : false)));
		} 
		catch (PDOException $e) 
		{
			$this->errno = $e->getCode();
		    $this->error = $e->getMessage();
			return false;
		}
		if ($options['driver'] == 'mysql')
		{
			$dbh->exec("SET character_set_connection='".$options['charset']."',character_set_results='".$options['charset']."',character_set_client=binary".($dbh->query("SELECT version()")->fetchColumn(0) > '5.0.1' ? ",sql_mode=''" : ''));
		}
		return $dbh;
	}
	
	private function connect_slave()
	{
		$this->slave_key = array_rand($this->slaves);
		$this->slave = $this->slaves[$this->slave_key];
		$this->dbh_slave = $this->connect($this->slave);
		if (!$this->dbh_slave && count($this->slaves) > 1)
		{
			unset($this->slaves[$this->slave_key]);
			return $this->connect_slave();
		}
		return $this->dbh_slave;
	}

	public function exec($statement)
	{
		return $this->dbh($statement) ? $this->dbh->exec($this->sql) : false;
	}

	public function prepare($statement, $driver_options = array())
	{
		return $this->dbh($statement) ? $this->dbh->prepare($this->sql, $driver_options) : false;
	}

	public function query($statement)
	{
		return $this->dbh($statement) ? $this->dbh->query($this->sql) : false;
	}

	public function get($sql, $data = array(), $fetch_style = PDO::FETCH_ASSOC)
	{
		$db = $this->prepare($sql);
		if(!$db) return false;
		if ($db->execute($data))
		{
			return $db->fetch($fetch_style);
		}
		else
		{
			$this->errno = $db->errorCode();
			$this->error = $db->errorInfo();
			return false;
		}
	}

	public function select($sql, $data = array(), $fetch_style = PDO::FETCH_ASSOC)
	{
		$db = $this->prepare($sql);
		if(!$db) return false;
		if ($db->execute($data))
		{
			return $db->fetchAll($fetch_style);
		}
		else
		{
			$this->errno = $db->errorCode();
			$this->error = $db->errorInfo();
			return false;
		}
	}

	public function insert($sql, $data = array(), $multiple = false)
	{
		$db = $this->prepare($sql);
		if(!$db) return false;
		if (empty($data))
		{
			if ($db->execute())
			{
				$insertid = $this->dbh_master->lastInsertId();
				return $insertid ? $insertid : true;
			}
			else 
			{
				$this->errno = $db->errorCode();
				$this->error = $db->errorInfo();
				return false;
			}
		}
		if($multiple)
		{
			foreach ($data as $r)
			{
				$this->_bindValue($db, $r);
				if (!$db->execute())
				{
					$this->errno = $db->errorCode();
					$this->error = $db->errorInfo();
					return false;
				}
			}
			return true;
		}
		else
		{
			$this->_bindValue($db, $data);
			if ($db->execute())
			{
				$insertid = $this->dbh_master->lastInsertId();
				return $insertid > 0 ? $insertid : true;
			}
			else 
			{
				$this->errno = $db->errorCode();
				$this->error = $db->errorInfo();
				return false;
			}
		}
	}

	public function update($sql, $data = array(), $multiple = false)
	{
		$db = $this->prepare($sql);
		if(!$db) return false;
		if (empty($data))
		{
			if ($db->execute())
			{
				$rowcount = $db->rowCount();
				return $rowcount ? $rowcount : true;
			}
			else 
			{
				$this->errno = $db->errorCode();
				$this->error = $db->errorInfo();
				return false;
			}
		}
		if($multiple)
		{
			foreach ($data as $r)
			{
				$this->_bindValue($db, $r);
				if (!$db->execute())
				{
					$this->errno = $db->errorCode();
					$this->error = $db->errorInfo();
					return false;
				}
			}
			return true;
		}
		else
		{
			$this->_bindValue($db, $data);
			if ($db->execute())
			{
				$rowcount = $db->rowCount();
				return $rowcount ? $rowcount : true;
			}
			else 
			{
				$this->errno = $db->errorCode();
				$this->error = $db->errorInfo();
				return false;
			}
		}
	}

	public function replace($sql, $data = array(), $multiple = false)
	{
		return $this->update($sql, $data, $multiple);
	}

	public function delete($sql, $data = array())
	{
		$db = $this->prepare($sql);
		if(!$db) return false;
		if ($db->execute($data))
		{
			$rowcount = $db->rowCount();
			return $rowcount ? $rowcount : true;
		}
		else
		{
			$this->errno = $db->errorCode();
			$this->error = $db->errorInfo();
			return false;
		}
	}

	public function limit($sql, $limit = 0, $offset = 0, $data = array(), $fetch_style = PDO::FETCH_ASSOC)
	{
		if ($limit > 0) $sql .= $offset > 0 ? " LIMIT $offset, $limit" : " LIMIT $limit";
		return $this->select($sql, $data, $fetch_style);
	}

	public function page($sql, $page = 1, $size = 20, $data = array(), $fetch_style = PDO::FETCH_ASSOC)
	{
		$page = isset($page) ? max(intval($page), 1) : 1;
		$size = max(intval($size), 1);
		$offset = ($page-1)*$size;
		return $this->limit($sql, $size, $offset, $data, $fetch_style);
	}

	public function select_db($dbname)
	{
		return $this->exec("USE $dbname");
	}

	public function list_fields($table, $field = null)
	{
		$sql = "SHOW COLUMNS FROM `$table`";
		if ($field) $sql .= " LIKE '$field'";
		return $this->query($sql);
	}

	public function list_tables($dbname = null)
	{
		$tables = array();
		$sql = $dbname ? "SHOW TABLES FROM `$dbname`" : "SHOW TABLES";
		$result = $this->query($sql);
		foreach ($result as $r)
		{
			$tables[]	= array_pop($r);
		}
		return $tables;
	}

	public function list_dbs()
	{
		$dbs = array();
		$result = $this->query("SHOW DATABASES");
		foreach ($result as $r)
		{
			foreach ($r as $db) $dbs[] = $db;
		}
		return $dbs;
	}

	public function get_primary($table)
	{
		$primary = array();
		$result = $this->query("SHOW COLUMNS FROM `$table`");
		foreach ($result as $r)
		{
			if($r['Key'] == 'PRI') $primary[] = $r['Field'];
		}
		return count($primary) == 1 ? $primary[0] : (empty($primary) ? null : $primary);
	}

	public function get_var($var = null)
	{
		$variables = array();
		$sql = is_null($var) ? '' : " LIKE '$var'";
		$result = $this->query("SHOW VARIABLES $sql");
		foreach ($result as $r)
		{
			if(!is_null($var) && isset($r['Value'])) return $r['Value'];
			$variables[$r['Variable_name']] = $r['Value'];
		}
		return $variables;
	}

	public function version()
	{
		$db = $this->query("SELECT version()");
		return $db ? $db->fetchColumn(0) : false;
	}

	public function prefix()
	{
		return $this->master['prefix'];
	}
	
	public function errno()
	{
		return is_null($this->errno) ? $this->errorCode() : $this->errno;
	}

	public function error()
	{
		if (is_null($this->error))
		{
			return $this->errorInfo();
		}
		else 
		{
			$this->error['sql'] = $this->sql;
			return $this->error;
		}
	}

    private function dbh_master()
    {
        if(is_null($this->dbh_master)) $this->dbh_master = $this->connect($this->master);
        $this->dbh = $this->dbh_master;
        return $this->dbh;
    }

	private function dbh($sql = null)
	{
		if (is_null($sql))
		{
			$this->sql = null;
			if (is_null($this->dbh))
			{
				if(is_null($this->dbh_master)) $this->dbh_master = $this->connect($this->master);
				$this->dbh = $this->dbh_master;
			}
			return $this->dbh;
		}
		
		self::$queries++;
		$this->sql = str_replace('#table_', $this->master['prefix'], trim($sql));
		if($this->slaves && is_null($this->dbh_master) && stripos($this->sql, 'select') === 0)
		{
			if(is_null($this->dbh_slave)) $this->dbh_slave = $this->connect_slave();
            $this->dbh = $this->dbh_slave;
		}
		else
		{
			if(is_null($this->dbh_master)) $this->dbh_master = $this->connect($this->master);
			$this->dbh = $this->dbh_master;
		}
		return $this->dbh;
	}

	private function _bindValue(& $db, $data)
	{
		if (!is_array($data)) return false;
		foreach ($data as $k=>$v)
		{
			$k = is_numeric($k) ? $k+1 : ':'.$k;
			$db->bindValue($k, $v);
		}
		return true;
	}
}
