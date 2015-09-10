<?php
/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - find()
 * - select()
 * - save()
 * - update()
 * - add()
 * - insert()
 * - delete()
 * - query()
 * - field()
 * - where()
 * - orWhere()
 * - order()
 * - limit()
 * - count()
 * - __call()
 * - __set()
 * - __get()
 * - __unset()
 * - set()
 * - get()
 * - clear()
 * - parseField()
 * - parseData()
 * - buildSelectSql()
 * - buildFromSql()
 * - buildWhereSql()
 * - buildTailSql()
 * Classes list:
 * - Model
 */
class Model implements JsonSerializable, ArrayAccess
{
	/**
	 * 数据库表名
	 * @var string
	 */
	protected $table;
	/**
	 * 主键
	 * @var string
	 */
	protected $pk = 'id';

	protected $has_tables        = array();
	protected $belongs_to_tables = array();
	protected $fields            = array(); //查询字段
	protected $data              = array(); //数据
	protected $where             = '';      //查询条件
	protected $param             = array(); //查询参数
	protected $distinct          = false;   //是否去重
	protected $order             = array(); //排序字段
	protected $limit             = null;

	private static $_db = null; //数据库连接

	public function __construct($table, $pk = 'id')
	{
		$this->table = $table;
		$this->pk    = $pk;
		if (self::$_db == null)
		{
			$config    = Config::get('database');
			$dsn       = $config['driver'] . ':host=' . $config['host'] . ';dbname=' . $config['database'];
			self::$_db = new Service\Db($dsn, $config['username'], $config['password']);
		}
	}

	/**
	 * has
	 * 一对多
	 * @method one
	 * @param  [type] $table [description]
	 * @param  [type] $fk    [description]
	 * @return [type]        [description]
	 * @author NewFuture
	 */
	// public function has($table, $fk = null)
	// {
	// 	if ($fk == null)
	// 	{
	// 		$fk = substr($this->table, 0, 3) . '_id';
	// 	}
	// 	$this->has_one_tables[$fk] = $table;
	// 	return $this;
	// }

	/**
	 * 从属关系
	 * 对于inner join
	 * @method belongs
	 * @param  [string]  $table [表名]
	 * @param  [string]  $pk    [外键]
	 * @return [object]         [description]
	 * @author NewFuture
	 */
	public function belongs($table, $fk = null)
	{
		if ($fk == null)
		{
			$fk = substr($table, 0, 3) . '_id';
		}
		$this->belongs_to_tables[$fk] = $table;
		return $this;
	}

	/**
	 * 单条查询
	 * @method find
	 * @param  [mixed] $id [description]
	 * @return [array]     [结果数组]
	 * @author NewFuture
	 * @example
	 * 	find(1);//查找主键为1的结果
	 * 	find(['name'=>'ha'])//查找name为ha的结果
	 */
	public function find($id = null, $value = null)
	{
		if (null !== $value)
		{
			$this->data[$id] = $value;
		}
		elseif (null != $id)
		{
			if (is_array($id))
			{
				$this->data = array_merge($this->data, $id);
			}
			else
			{
				$this->data[$this->pk] = $id;
			}
		}
		$this->limit = 1;
		$result      = $this->select();
		$this->data  = isset($result[0]) ? $result[0] : $result;
		return $this->data ? $this : null;
	}

	/**
	 * 批量查询
	 * @method select
	 * @param  array  $data  [查询数据条件]
	 * @return [array]       [结果数组]
	 * @author NewFuture
	 */
	public function select($data = array())
	{
		if (is_array($data))
		{
			//数组条件
			$this->data = array_merge($this->data, $data);
		}
		elseif (is_string($data))
		{
			//select筛选字段
			$this->field($data);
		}

		$sql = $this->buildSelectSql();
		$sql .= $this->buildFromSql();
		$sql .= $this->buildWhereSql();
		$sql .= $this->buildTailSql();
		return $this->query($sql, $this->param);
	}

	/**
	 * 保存数据
	 * @method save
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 * @author NewFuture
	 */
	public function save($data = array())
	{
		if (is_numeric($data))
		{
			$this->where($this->pk, '=', $data);
			$data       = $this->data;
			$this->data = array();
		}
		return $this->update($data);
	}

	/**
	 * 更新数据
	 * @method update
	 * @param  array  $data  [要更新的数据]
	 * @return [array]       [结果数组]
	 * @author NewFuture
	 */
	public function update($data = array())
	{
		//字段过滤
		$fields = $this->fields;
		if (!empty($fields))
		{
			$fields = array_flip($fields);
			$data   = array_intersect_key($data, $field);
		}

		if (is_array($data) && !empty($data))
		{
			$this->param   = array_merge($this->param, $data);
			$update_string = '';
			foreach (array_keys($data) as $key)
			{
				$update_string .= self::backQoute($key) . '=:' . $key . ',';
			}
			$update_string = trim($update_string, ',');
		}
		elseif (is_string($data))
		{
			$update_string = $data;
		}
		else
		{
			if (Config::get('isdebug'))
			{
				throw new Exception('未知参数', 1);
			}
			return false;
		}

		$sql = 'UPDATE' . self::backQoute($this->table);
		$sql .= 'SET' . $update_string;
		$sql .= $this->buildWhereSql();

		return $this->execute($sql, $this->param);
	}

	/**
	 * 新增数据
	 * 合并现有的data属性
	 * @method add
	 * @param  array $data [要更新的数据]
	 * @author NewFuture
	 */
	public function add($data = array())
	{
		if (is_array($data))
		{
			$this->data = array_merge($this->data, $data);
		}
		$data = $this->data;
		return $this->insert($data);
	}

	/**
	 * 插入数据库
	 * @method insert
	 * @param  [array] $data 	[要更新的数据]
	 * @return [integer]        [返回插入行id]
	 * @author NewFuture
	 */
	public function insert($data = array())
	{
		//字段过滤
		$fields = $this->fields;
		if (!empty($fields))
		{
			$fields = array_flip($fields);
			$data   = array_intersect_key($data, $field);
		}
		//插入数据
		if (!empty($data))
		{
			// $this->fields = $data;
			$fields       = array_keys($data);
			$quote_fields = $fields;
			//对字段进行转义
			array_walk($quote_fields, function (&$k)
			{
				$k = self::backQoute($k);
			});
			$sql = 'INSERT INTO' . self::backQoute($this->table);
			$sql .= '(' . implode(',', $quote_fields) . ')VALUES(:' . implode(',:', $fields) . ')';
			$this->execute($sql, $data);
			return self::$_db->lastInsertId();
		}
	}

	/**
	 * 删除数据
	 * @method delete
	 * @param  [string] $id [description]
	 * @return [array]     	[description]
	 * @author NewFuture
	 */
	public function delete($id = '')
	{
		if (null != $id)
		{
			if (is_array($id))
			{
				$this->data = array_merge($this->data, $id);
			}
			else
			{
				$this->data[$this->pk] = $id;
			}
		}
		$sql = 'DELETE';
		$sql .= $this->buildFromSql();
		$where = $this->buildWhereSql();
		if (!$where)
		{
			return false;
		}
		else
		{
			$sql .= $where;
			return $this->execute($sql, $this->param);
		}
	}

	/**
	 * 数据读取操作
	 * @method query
	 * @param  [string] $sql  	[sql语句]
	 * @param  [type] $bind 	[description]
	 * @return [array]       	[结果数组]
	 * @author NewFuture
	 */
	public function query($sql, $bind = null)
	{
		$result = self::$_db->query($sql, $bind);
		$this->clear();
		return $result;
	}

	/**
	 * 数据写入修改操作
	 * @method execute
	 * @param  [string] $sql  	[sql语句]
	 * @param  [type] $bind 	[description]
	 * @return [array]       	[结果数组]
	 * @author NewFuture
	 */
	public function execute($sql, $bind = null)
	{
		$result = self::$_db->execute($sql, $bind);
		$this->clear();
		return $result;
	}

	/**
	 * 字段过滤
	 * @method field
	 * field('name','username')
	 * field('name AS username')
	 * field('id,name,pwd')
	 * @param  [string]		$field    	[字段]
	 * @param  [string] 	$alias 		[description]
	 * @return [object]        			[description]
	 * @author NewFuture
	 */
	public function field($field, $alias = null)
	{
		if ($alias && $field)
		{
			$this->fields[trim($field)] = trim($alias);
		}
		else
		{
			/*多字段解析*/
			$fields = explode(',', $field);
			foreach ($fields as $field)
			{
				$field = strtolower(trim($field));
				/*解析是否为name AS alias的形式*/
				if (strpos($field, ' as '))
				{
					list($name, $alias)        = explode(' as ', $field);
					$this->fields[trim($name)] = trim($alias);
					// $this->fields[trim(stristr($field, ' AS ', true))] = trim(stristr($field, ' AS '));
				}
				else
				{
					$this->fields[$field] = $field;
				}
			}
		}
		return $this;
	}

	/**
	 * where 查询
	 * @method where
	 * @param  [mixed] 	$key        [description]
	 * @param  [string] $exp        [description]
	 * @param  [mixed] 	$value      [description]
	 * @param  [string] $conidition [逻辑条件]
	 * @return [object]             [description]
	 * @example
	 * where($data)
	 * where('id',1) 查询id=1
	 * where('id','>',1)
	 * @author NewFuture
	 */
	public function where($key, $exp = null, $value = null, $conidition = 'AND')
	{
		if ($conidition !== 'OR')
		{
			$conidition = 'AND';
		}

		if (null === $exp) //单个参数，where($array)或者where($sql)
		{
			if (is_string($key))
			{
				//直接sql条件
				//TO 安全过滤
				$where = 'AND(' . $key . ')';
			}
			elseif (is_array($key))
			{
				/*数组形式*/
				$where = [];
				foreach ($key as $k => $v)
				{
					$name               = $k . '_w_eq';
					$where[]            = self::backQoute($k) . '=:' . $name;
					$this->param[$name] = $v;
				}
				$where = $conidition . '((' . implode(')AND(', $where) . '))';
			}
			else
			{
				throw new Exception('非法where查询:' . json_encode($key));
			}
		}
		elseif (null === $value) //where($key,$v)等价于where($key,'=',$v);
		{
			$name  = $key . '_w_eq';
			$where = $conidition . '(' . self::backQoute($key) . '=:' . $name . ')';

			$this->param[$name] = $exp;
		}
		else
		{
			$name  = $key . '_w_eq';
			$where = $conidition . '(' . self::backQoute($key) . $exp . ' :' . $name . ')';

			$this->param[$name] = $value;
		}
		$this->where .= $where;
		return $this;
	}

	/**
	 * OR 条件
	 * @method orWhere
	 * @param  [mixed]  $key   	[description]
	 * @param  [string] $exp   	[description]
	 * @param  [string] $value 	[description]
	 * @return [type]         	[description]
	 * @author NewFuture
	 */
	public function orWhere($key, $exp = null, $value = null)
	{
		return $this->where($key, $exp, $value, 'OR');
	}

	/**
	 * 排序条件
	 * @method order
	 * @param  [type]  		$fields     [description]
	 * @param  [boolean] 	$desc 		[是否降序]
	 * @return [array]              	[结果数组]
	 * @author NewFuture
	 */
	public function order($fields, $desc = false)
	{
		$this->order[trim($fields)] = ($desc === true || $desc === 1 || strtoupper($desc) == 'DESC') ? 'DESC' : '';
		return $this;
	}

	/**
	 * 限制位置和数量
	 * @method limit
	 * @param  integer $n      [description]
	 * @param  integer $offset [description]
	 * @return [type]          [description]
	 * @author NewFuture
	 */
	public function limit($n = 20, $offset = 0)
	{
		if ($offset > 0)
		{
			$this->limit = intval($offset) . ',' . intval($n);
		}
		else
		{
			$this->limit = intval($n);
		}
		return $this;
	}

	/**
	 * 翻页
	 * @method page
	 * @param  integer $p [页码]
	 * @param  integer $n [每页个数]
	 * @return [type]     [description]
	 * @author NewFuture
	 */
	public function page($p = 1, $n = 10)
	{
		return $this->limit($n, ($p - 1) * $n);
	}

	/**
	 * 统计
	 * @method count
	 * @param  [type] $field [description]
	 * @return [type]        [description]
	 * @author NewFuture
	 */
	public function count($field = null)
	{
		$exp = $field ? 'COUNT(' . self::backQoute($field) . ')' : 'COUNT(*)';
		$sql = $this->buildSelectSql($exp);
		$sql .= $this->buildFromSql();
		$sql .= $this->buildWhereSql();
		$result = self::$_db->single($sql, $this->param);
		$this->clear();
		return $result;
	}

	/**
	 * 表达式查询
	 * @method inc
	 * @param  [type]  $field [description]
	 * @param  [type]  $exp   [description]
	 * @param  integer $n     [description]
	 * @return [type]         [description]
	 * @author NewFuture
	 */
	public function inc($field, $step = 1)
	{
		$sql = self::backQoute($field) . '=' . self::backQoute($field) . '+:_inc_step';

		$this->param['_inc_step'] = $step;
		return $this->update($sql);
	}

	public function __call($op, $args)
	{
		$op = strtoupper($op);
		if (in_array($op, ['MAX', 'MIN', 'AVG', 'SUM']) && isset($args[0]))
		{
			//数学计算
			$sql = $this->buildSelectSql($op . '(' . self::backQoute($args[0]) . ')');
			$sql .= $this->buildFromSql();
			$sql .= $this->buildWhereSql();
			$result = self::$_db->single($sql, $this->param);
			$this->clear();
			return $result;
		}
		else
		{
			throw new Exception('不支持操作' . $op, 1);
		}
	}

	/**
	 * 设置字段值
	 * @method __set
	 * @param  [type]  $name  [description]
	 * @param  [type]  $value [description]
	 * @access private
	 * @author NewFuture
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * 获取字段值
	 * @method __get
	 * @param  [type]  $name [description]
	 * @return [type]        [description]
	 * @access private
	 * @author NewFuture
	 */
	public function __get($name)
	{
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	/**
	 * 设置数据
	 * @method set
	 * @param  [type] $data  [description]
	 * @param  [type] $value [description]
	 * @author NewFuture
	 */
	public function set($data, $value = null)
	{
		if (is_array($data))
		{
			$this->data = array_merge($this->data, $data);
		}
		else
		{
			$this->data[$data] = $value;
		}
		return $this;
	}

	/**
	 * 读取数据
	 * 如果有直接读取，无数据库读取
	 * @method get
	 * @param  [type] $name [字段名称]
	 * @param  [type] $auto_db [是否自动尝试从数据库获取]
	 * @return [type]       [description]
	 * @author NewFuture
	 */
	public function get($name = null, $auto_db = true)
	{
		if ($name)
		{
			if (isset($this->data[$name]))
			{
				return $this->data[$name];
			}
			elseif ($auto_db)
			{
				//数据库读取
				$sql = $this->buildSelectSql(self::backQoute($name));
				$sql .= $this->buildFromSql();
				$sql .= $this->buildWhereSql();
				$sql .= 'LIMIT 1';
				$data  = $this->data;
				$value = self::$_db->single($sql, $this->param);
				$this->clear();
				if ($value !== null)
				{
					$data[$name] = $value;
				}
				$this->data = $data;
				return $value;
			}
		}
		else
		{
			return empty($this->data) && $auto_db ? $this->find() : $this->data;
		}
	}

	/**
	 * 清空
	 * @method clear
	 * @return [type] [description]
	 * @author NewFuture
	 */
	public function clear()
	{
		$this->fields   = array(); //查询字段
		$this->data     = array(); //数据
		$this->where    = '';      //查询条件
		$this->param    = array(); //查询参数
		$this->distinct = false;   //是否去重
		$this->order    = array(); //排序字段
		$this->limit    = null;
		return $this;
	}

	/**
	 * field分析
	 * @access private
	 * @param mixed $fields
	 * @return string
	 */
	private function parseField()
	{
		$fields = $this->fields;

		/*多表链接时加入表名*/
		$pre = $this->belongs_to_tables ? self::backQoute($this->table) . '.' : '';
		$str = '';
		if (empty($fields))
		{
			$str = $pre . '*';
		}
		elseif ($pre)
		{
			//多表加入表名
			$pre = ',' . $pre;
			foreach ($fields as $field => $alias)
			{
				$str .= $pre . self::backQoute($field) . 'AS' . self::backQoute($alias);
			}
		}
		else
		{
			// 支持 'fieldname'=>'alias' 这样的字段别名定义
			foreach ($fields as $field => $alias)
			{
				$str .= ($field == $alias || is_int($field)) ? ',' . self::backQoute($field)
				: ',' . (self::backQoute($field) . 'AS' . self::backQoute($alias));
			}
		}
		$str = ltrim($str, ',');
		/*belong关系，从属与*/
		foreach ($this->belongs_to_tables as $fk => $table)
		{
			$str .= ',' . self::backQoute($table) . '.' . self::backQoute('name') . 'AS' . self::backQoute($table);
		}
		//TODO其他接表方式
		return $str;
	}

	/**
	 * 数据解析和拼接
	 * @method parseData
	 * @param  string    $pos [description]
	 * @return [type]         [description]
	 * @author NewFuture
	 */
	private function parseData($pos = ',')
	{
		$fieldsvals = array();
		foreach (array_keys($this->data) as $column)
		{
			$fieldsvals[] = self::backQoute($column) . '=:' . $column;
		}
		$this->param = array_merge($this->param, $this->data);
		return implode($pos, $fieldsvals);
	}

	/**
	 * 构建select子句
	 * @method buildSelectSql
	 * @return [type]         [description]
	 * @author NewFuture
	 */
	private function buildSelectSql($exp = null)
	{
		$sql = $this->distinct ? 'SELECT DISTINCT ' : 'SELECT ';
		$sql .= $exp ?: $this->parseField();
		return $sql;
	}

	/**
	 * 构建From子句
	 * @method buildFromSql
	 * @return [type]       [description]
	 * @author NewFuture
	 */
	private function buildFromSql()
	{
		$from = 'FROM' . self::backQoute($this->table) . '';
		//belong关系(属于)1对多，innerjoin
		foreach ($this->belongs_to_tables as $fk => $table)
		{
			$from .= 'INNER JOIN' . self::backQoute($table) .
			'ON' . self::backQoute($this->table) . '.' . self::backQoute($fk) .
			'=' . self::backQoute($table) . '.' . self::backQoute('id');
		}
		return $from;
	}

	/**
	 * 构建where子句
	 * @method buildWhereSql
	 * @return [string]        [''或者WHERE(xxx)]
	 * @author NewFuture
	 */
	private function buildWhereSql()
	{
		$pre     = $this->belongs_to_tables ? self::backQoute($this->table) . '.' : '';
		$datastr = $this->parseData(')AND(' . $pre);
		$where   = null;
		if ($datastr)
		{
			$where = '(' . $pre . $datastr . ')' . $this->where;
		}
		elseif ($this->where)
		{
			//去掉第一个AND或者OR
			$where = strstr($this->where, '(');
		}

		return $where ? ' WHERE ' . $where : '';
	}

	/**
	 * 构建尾部子句
	 * limt order等
	 * @method buildTailSql
	 * @return [type]       [description]
	 * @author NewFuture
	 */
	private function buildTailSql()
	{
		$tail = '';
		if ($this->order)
		{
			/*多表链接时加入表名*/
			$pre   = $this->belongs_to_tables ? ',' . self::backQoute($this->table) . '.' : ',';
			$order = '';
			/*拼接排序字段*/
			foreach ($this->order as $field => $value)
			{
				$order .= $pre . self::backQoute($field) . $value;
			}
			$tail = ' ORDER BY' . ltrim($order, ',');
		}
		if ($this->limit)
		{
			$tail .= ' LIMIT ' . $this->limit;
		}
		return $tail;
	}

	/**
	 * 对字段和表名进行反引字符串
	 * 并字符进行安全检查
	 * 合法字符为[a-zA-Z_]
	 * @method backQoute
	 * @param  [type]    $str [description]
	 * @return [type]         [description]
	 * @author NewFuture
	 */
	public static function backQoute($str)
	{
		if (!ctype_alnum(strtr($str, '_', 'A')))
		{
			//合法字符为字母[a-zA-Z]或者下划线_
			throw new Exception('非法字符' . $str);
			die('数据库操作中断');
		}
		else
		{
			return '`' . $str . '`';
		}
	}

	/**json序列化接口实现**/
	public function jsonSerialize()
	{
		return $this->data;
	}

	/**数组操作接口实现**/
	public function offsetExists($offset)
	{
		return isset($data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->get($offsetSet, false);
	}

	public function offsetSet($offset, $value)
	{
		$this->data[$offsetSet] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offsetSet]);
	}
}
?>