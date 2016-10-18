<?php
use \Logger as Logger;

/**
 * 数据库表
 * ORM
 * join 类型 JOIN_TYPE = array('INNER', 'LEFT', 'RIGHT', 'OUTER', 'FULL OUTER');
 * 查询支持的函数 FUNCTIONS = array('ABS','AVG','COUNT','LCASE','LENGTH','MAX','MIN','SIGN','SUM','UCASE',);
 * where 表达式 比较符
 *  'op' => array('=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'LIKE BINARY', 'NOT LIKE BINARY'), //值比较
 *  'BT' => array('BETWEEN', 'NOT BETWEEN'),
 *  'IN' => array('IN', 'NOT IN'),
 *
 * @todo sql缓存
 * @todo where字段 set别名支持(修改数据库时需要)
 * @todo where嵌套构建
 * @todo 计算表达式解析?
 */
class Orm implements \JsonSerializable, \ArrayAccess
{
    private static $_paramid  = 0; //参数ID

    protected $_table = ''; //数据库表名
    protected $_pk    = 'id'; //主键
    protected $_pre   = null; //前缀
    protected $_data  = array(); //数据
    protected $_alias = null; //别名

    protected $_safe  = true; //安全模式
    protected $_debug = false;//调试输出
    protected $_db    = null;//此orm优先使用的数据库
    protected $_clear = true;

    private $_param   = array(); //查询参数
    private $_joins   = array(); //join 表
    private $_groups  = array(); //group by
    private $_unions  = array(); //合并查询
    private $_fields  = array(); //查询字段
    private $_where   = array(); //查询条件
    private $_having  = array(); //having 条件
    private $_order   = array(); //排序字段
    private $_limit   = null; // 分段和偏移
    private $_distinct = false; //是否去重

    /**
     * 构造函数
     *
     * @param string $table 数据库表名
     * @param [string] $pk    [主键，默认id]
     * @param [string] $prefix [数据库表前缀,默认读取配置]
     */
    public function __construct($table, $pk = false, $prefix = true)
    {
        $this->_table = $table;
        $pk && ($this->_pk = $pk);
        if ($this->_pre = (true === $prefix) ? Config::getSecret('database', 'prefix') : strval($prefix)) {
            $this->_alias = $table;//有前缀时，直接使用无前缀作为别名
        }
    }

    /**
     * 批量查询
     *
     * @method select
     *
     * @param  string    [数组条件]
     *
     * @return [array]   [查询结果数组]
     *
     * @author NewFuture
     */
    public function select($fields = '')
    {
        if ($fields) {
            assert('is_string($fields)', '[Orm::select]查询参数应该缺省或者是字段字符串,但是传入的是' . print_r($fields, true));
            $this->field($fields);
        }
        $sql = $this->buildSelect();
        return $this->_data = $this->query($sql);
    }

    /**
     *查询一条记录
     *
     * @method find
     *
     * @param  [mixed] $id [id或者条件数组]
     *
     * @return [array]     [结果数组]
     *
     * @author NewFuture
     *
     * @example
     *     find(1);//查找主键为1的结果
     *     find(['name'=>'ha'])//查找name为ha的结果
     */
    public function find($id = null)
    {
        if (is_array($id)) {
            $this->where($id);
        } elseif ($id) {
            $pkey = $this->_joins ? $this->$_pre.$this->_table.'.'.$this->_pk : $this->_pk;
            $this->where($pkey, $id);//auto add table name if has other tables
        }

        $this->limit(1);
        $sql = $this->buildSelect();

        if ($result = $this->query($sql, false)) {
            if (true === $this->_debug) {
                return $result; //调试输出
            }
            //查询成功
            $this->_data = $result;
            return $this;
        }
        return null;
    }

    /**
     * 读取数据 字段或者全部数据
     * 如果有直接读取，无数据库读取,遵循field别名设置
     *
     * @method get
     *
     * @param  [string] $key [字段名称，无此参数时返回全部数据]
     * @param  [boolean $auto_query [是否自动尝试从数据库获取]
     *
     * @return [mixed]       [读取的结果]
     *
     * @author NewFuture
     */
    public function get($key = '', $auto_query = true)
    {
        $data = &$this->_data;
        if ('' == $key) {
            return (empty($data) && $auto_query) ? $this->find() : $data; //获取全部数据
        }
        if (isset($data[$key])) {
            return $data[$key]; //键值存在
        }
        if ($auto_query) {
            /*自动查询*///判断是否有别名设置
            $field = array_search($key, $this->_fields, true);
            $field = is_string($field) ? $this->parseFunction($field) : Orm::backQoute($key);
            $sql   = $this->limit(1)->buildSelect($field);
            $value = $this->value($sql); //单列查询
            if ($value !== false) {
                $this->_data[$key] = $value;
            }
            return $value;
        }
    }

    /**
     * 单条插入数据库(忽略前置条件)
     * 如果设置了字段field会使用设置的字段过滤(如果设置了alias与其一致)，取二者交集
     *
     * @method insert
     *
     * @param  [array] $data     [要插入的数据，键值对数组(单条)]
     *
     * @return [integer|boolean]        [返回插入数据的id,注，如果改记录中无自增主键将返回TRUE或者FALSE]
     *
     * @author NewFuture
     */
    public function insert(array $data)
    {
        assert('is_array($data)', '[Orm::insert] 插入数据参数应该是非空数组');
        if ($fields = &$this->_fields) {
            /*字段过滤，支持字段别名*/
           $fields = Orm::fieldFilter($fields, $data);
        } else {
            ksort($data);
            $fields = array_keys($data);
        }

        foreach ($data as &$value) {
            $value = $this->bindParam($value);
        }
        $sql = $this->buildInsert($data);
        $result = $this->execute($sql);
        return $this->getDb('_write')->lastInsertId() ?: $result;
    }

    /**
     * 批量插入数据（忽略前置条件)
     * 支持字段过滤,如果某条字段数据不足直接丢弃
     * 如果没有设置字段以第一条数据字段为准,过滤后面的数据
     *
     * @method insertAll
     *
     * @param  array     $data      [数据，二维数组]
     *
     * @return int 插入成功条数
     *
     * @author NewFuture
     */
    public function insertAll(array $data)
    {
        assert('is_array($data[0])', '[Orm::insertAll] 插入数据参数应该是非空二维数组');
        if (!$fields = &$this->_fields) {
            //无过滤条件,取第一组数据字段值为对应值
            $fields = array_keys($data[0]);
            asort($fields);
        }

        /*字段过滤*/
        foreach ($data as $key => &$row) {
            if (Orm::fieldFilter($fields, $row) !== $fields) {
                Logger::write('[Orm]这条数据在批量插入时被过滤掉:'.jso($row, 256), 'INFO');
                unset($data[$key]);//不符合的数据将被过滤掉
                continue;
            }
            foreach ($row as &$value) {
                $value = $this->bindParam($value);
            }
            unset($value);
        }
        unset($row);

        if (empty($data)) {
            //清理后无有效数据
             return false;
        }
        $db = $this->getDb('_write');
        $is_sqlite = 'sqlite' === $db->getAttribute(PDO::ATTR_DRIVER_NAME) && version_compare('3.7.11', $db->getAttribute(PDO::ATTR_SERVER_VERSION), '>');
        $sql = $this->buildInsert($data, $is_sqlite);
        return $this->execute($sql);
    }

    /**
     * 新增数据(保留之前的set)
     * 合并现有的data属性
     *
     * @method add
     *
     * @return $this|FALSE
     *
     * @author NewFuture
     */
    public function add()
    {
        assert(func_num_args() === 0, '[Orm::add]此方法不接受参数');
        $id = $this->insert($this->_data);
        if (true === $this->_debug) {
            return $id;
        }
        if (false === $id) {
            return false;
        }
        if ($id) {
            $this->_data[$this->_pk] = $id; //返回的是id，将id保存到data主键中
        }
        return $this;
    }

    /**
     * 设置数据
     *
     * @method set
     *
     * @param  [mixed] $key  [字段或者数组]
     * @param  [mixed] $value [值]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->_data = $key + ($this->_data);//array_merge($this->_data, $key);
        } else {
            assert(func_num_args() === 2, '[Orm::set] $key为非数组时,需要两个参数(设置value)');
            $this->_data[$key] = $value;
        }
        return $this;
    }

    /**
     * 更新数据[支持字段过滤]
     * 直接跟新忽略之前set的数据
     *
     * @method update
     *
     * @param  array  $data [要更新的数据]
     *
     * @return int         [影响的条数]
     *
     * @author NewFuture
     *
     * @todo 使用函数赋值? 禁止修改全表?
     */
    public function update($data)
    {
        assert('is_array($data)', '[Orm::update]此方法的参数必须是数组');
        if ($fields = &$this->_fields) {
            $fields = Orm::fieldFilter($fields, $data); //字段过滤
        }
        foreach ($data as &$value) {
            $value = $this->bindParam($value);
        }
        unset($value);
        $sql = $this->buildUpdate($data);
        return $this->execute($sql);
    }

    /**
     * 保存数据 用于set的连贯操作
     *
     * @method save
     *
     * @param  [int]  $id [保存到主键,可不设置]
     *
     * @return $this|FALSE   [更新失败返回FALSE否则返回$this]
     *
     * @author NewFuture
     */
    public function save($id = null)
    {
        $data = &$this->_data;
        if ($id) {
            //更新指定的ID
            assert('is_scalar($id)', '[Orm::add] add 参数是主键值或者空，其他条件请使用where');
            $this->where($this->_pk, $id);
        } elseif (empty($this->_where) && isset($data[$this->_pk])) {
            //空条件且设置了主键，使用主键作为更新参数
            $this->where($this->_pk, $data[$this->_pk]);
        }

        if (true === $this->_debug) {
            return $this->update($data);
        }
        return false === $this->update($data) ? false : $this;
    }

    /**
     * 获取字段真名
     *
     * @method getTrueField
     *
     * @param  string  $key [键值]
     *
     * @return 操作结果 真实键名
     *
     * @author NewFuture
     */
    protected function getTrueField($key)
    {
        if ($fields = &$this->_fields) {
            //字段检查
            if ($field = array_search($key, $fields, true)) {
                if (is_string($field)) {
                    $key = $field; //别名
                }
            } elseif (!isset($fields[$key])) {
                return false;//无此字段
            }
        }
        return $key;
    }

    /**
     * 修改并写入数据 对set和save的简化
     *
     * @method put
     *
     * @param  string  $key [保存的键值]
     * @param  scalar  $value [修改后的键值]
     *
     * @return 操作结果 修改成功的条数
     *
     * @author NewFuture
     */
    public function put($key, $value)
    {
        if ($key = $this->getTrueField($key)) {
            $update = array($key => $this->bindParam($value));
            $sql = $this->buildUpdate($update);
            $result = $this->execute($sql);
            if ($result !== false) {
                $this->_data[$key] = $value;//写入数据
            }
            return $result;
        }
    }

    /**
     * 删除数据[危险操作]
     *
     * @method delete
     *
     * @param  [int] $id [删除id]
     *
     * @return [int]     删除的条数
     *
     * @author NewFuture
     *
     * @todo 禁止删表设置?
     */
    public function delete($id = null)
    {
        assert('null===$id||is_numeric($id)', '[Orm::add]删除id为空或者数字');
        $id and $this->where($this->_pk, $id);
        $sql = $this->buildDelete();
        return $this->execute($sql);
    }

    /**
     * 查询结果是否结果是否去重
     *
     * @method distinct
     *
     * @param  [boolean] $is_distinct [是否去重]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function distinct($is_distinct = true)
    {
        $this->_distinct = $is_distinct;
        return $this;
    }

    /**
     * 设置别名
     *
     * @method alias
     *
     * @param  string $alias  设置的别名
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function alias($alias)
    {
        assert('ctype_alnum($alias)', '[Orm::alias]别名应该由字母数字组成');
        $this->_alias = $alias;
        return $this;
    }

    /**
     * where AND 条件
     *
     * @method where
     *
     * @param  mixed     $field        [键值,条件数组,条件SQL]
     * @param  [string] $operator    [比较操作符]
     * @param  [mixed]     $value      [值]
     *
     * @return [object] $this
     *
     * @author NewFuture
     *
     * @example
     * where($data)
     * where('id',1) 查询id=1
     * where('id','>',1)
     */
    public function where()
    {
        return $this->parseWhere(func_get_args(), 'AND');
    }

    /**
     * where 字段比较，和where一样但是值按照字段处理
     *
     * @method whereField
     *
     * @param  mixed     $field        [键值,条件数组,条件SQL]
     * @param  [string] $operator    [比较操作符]
     * @param  [mixed]     $value      [值]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function whereField()
    {
        return $this->parseWhere(func_get_args(), 'AND', false);
    }

    /**
     * where OR 条件，
     *
     * @method orWhere
     *
     * @param  mixed    $field     [字段值或者,条件数组]
     * @param  [string] $operator    [比较操作符]
     * @param  [mixed]  $value   [值]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function orWhere()
    {
        return $this->parseWhere(func_get_args(), 'OR');
    }

    /**
     * where 字段比较 OR 条件,和orWhere一样但是值按照字段处理
     *
     * @method orWhereField
     *
     * @param  mixed     $field        [键值,条件数组,条件SQL]
     * @param  [string] $operator    [比较操作符]
     * @param  [mixed]     $value      [值]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function orWhereField()
    {
        return $this->parseWhere(func_get_args(), 'OR', false);
    }

    /**
     * exists 存在 子查询
     *
     * @method exists
     *
     * @param  Orm    $query     [包含查询的ORM对象]
     * @param  [boolen] $not    [为true时，not exists]
     * @param  [string] $type    ['AND'或者'OR' 默认AND]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function exists(Orm $query, $not = false, $type = 'AND')
    {
        $type = strtoupper($type);
        assert('in_array($type,array("AND","OR"))', '[Orm::exists] 类型只存在 AND 或者 OR 输入的是:' . $type);
        $sql = $not ? 'NOT EXISTS ' : 'EXISTS';
        $sql .= $this->buildSubquery($query);
        $this->_where[] = array($type,$sql);
        return $this;
    }

    /**
     * exists OR 条件
     *
     * @method orExists
     *
     * @param  Orm    $query     [包含查询的ORM对象]
     * @param  [boolen] $not    [为true时，not exists]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function orExists(Orm $query, $not = false)
    {
        return $this->exists($query, $not, 'OR');
    }

    /**
     * having  条件
     *
     * @method where
     *
     * @param  string     $field        [键值,条件数组,条件SQL]
     * @param  [string]  $operator    [比较操作符]
     * @param  [mixed]     $value      [值]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function having($field)
    {
        $this->_having[] = $this->parseCondition(func_get_args(), 'AND');
        return $this;
    }

    /**
     * having OR 条件
     *
     * @method where
     *
     * @param  string     $field        [键值,条件数组,条件SQL]
     * @param  [string]  $operator    [比较操作符]
     * @param  [mixed]     $value      [值]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function orHaving($field)
    {
        $this->_having[] = $this->parseCondition(func_get_args(), 'OR');
        return $this;
    }

    /**
     * 设置字段，设置读写字段过滤和字段别名
     *
     * @method field
     *
     * @param  [mixed]        $field        [字段设置]
     * @param  [string]     $alias         [description]
     *
     * @return [object]     $this
     *
     * @author NewFuture
     *
     * @example
     * field('name','username')
     * field('name AS username')
     * field('id,name,pwd')
     * field(['user.id'=>'uid'])
     */
    public function field($data, $alias = null)
    {
        $args_num = func_num_args();
        $fields = &$this->_fields;
        if (is_array($data)) {
            //数组
            assert('1===$args_num', '[Orm::field]当参数为array时，不接受后面的参数');
            $fields = array_merge($fields, $data);
        } elseif (2 === $args_num) {
            //字段，别名设置
            assert('is_string($alias)&&isset($alias[0])', '[Orm::field]别名必须为非空字符串');
            $fields[$data] = $alias; //设置别名
        } else {
            /*字符串多字段解析*/
            assert('1=== $args_num', '[Orm::field]参数过多'.print_r(func_get_args(), true));
            for ($field = strtok($data, ','); false !== $field; $field = strtok(',')) {
                /*解析是否为name AS alias的形式，关联存储*/
                if ($pos = stripos($field, ' AS ')) {
                    $fields[trim(substr($field, 0, $pos))] = trim(substr($field, $pos + 4));
                } else {
                    $fields[] = trim($field); //无别名索引形式
                }
            }
        }
        return $this;
    }

    /**
     * 排序条件
     *
     * @method order
     *
     * @param  [string]      $fields     [排序字段]
     * @param  [boolean]     $desc         [是否降序]
     *
     * @return [object]     $this
     *
     * @author NewFuture
     */
    public function order($fields, $desc = false)
    {
        assert('is_bool($desc)||in_array(strtoupper($desc),array("DESC","ASC"))', '[Orm::order]第二个参数$desc,请留空或者使用bool型(TRUE降序):'.$desc);
        $this->_order[$fields] = $desc && strtoupper($desc) !== 'ASC' ? 'DESC' : '';
        return $this;
    }

    /**
     * 限制查询条目和起始偏移量
     *
     * @method limit
     *
     * @param  integer $maxsize     [查询条目]
     * @param  [integer] $offset [偏移量,默认不偏移]
     *
     * @return [object] $this
     *
     * @author NewFuture
     */
    public function limit($maxsize, $offset = 0)
    {
        if ($limit = &$this->_limit) {
            /*再次设置，直接修改参数*/
            $this->_param[$limit[0]] = intval($maxsize);
            $this->_param[$limit[1]] = intval($offset);
        } else {
            //第一次设置
             $limit = array(
                        $this->bindParam(intval($maxsize)),
                        $this->bindParam(intval($offset))
                    );
        }
        return $this;
    }

    /**
     * 翻页
     *
     * @method page
     *
     * @param  integer $number [页码]
     * @param  integer $size [每页条目数]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function page($number, $size = 10)
    {
        return $this->limit($size, ($number - 1) * $size);
    }

    /**
     * join(多表连接)
     * 拥有的内容，[参数$table的外键是此表的主键][LEFT JOIN]
     *
     * @method has
     *
     * @param  string $type    [连接方式]
     * @param  string $table [对应表名]
     * @param  amixed $on    [JOIN ON的条件或者$table连接的键]
     * @param  string $related_key    [JOIN 与table关联的键]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function join($type, $table, $on, $related_key = null)
    {
        if (!in_array($type, array('INNER', 'LEFT', 'RIGHT', 'OUTER', 'FULL OUTER'))) {
            throw new Exception('[Orm::join] 第二个参数type不是JOIN支持的关联方式:' . $type, 1);
        }
        $fun_num = func_num_args();
        if (4 === $fun_num) {
            //快速设置JOIN ON条件
           $this->_joins[] = array($type, $table, $on,$related_key);
        } else {
            //高级设置
            assert('3===$fun_num', '[Orm::join] join 只接收3个参数或者4个参数: 但是传入了'.$fun_num);
            assert('isset($on[0])&&is_array($on[0])', '[Orm::join] join三个参数时 第三个参数$on应该是一个二维数组');
            $conditions = array();
            foreach ($on as $key => &$value) {
                if (is_string($key)) {
                    //键值对
                    $conditions = $this->parseCondition(array($key, $value), 'AND', false);
                } else {
                    //复杂逻辑
                    assert('is_array($value)');
                    $logic = isset($value['logic']) && strtoupper($value['logic']) === 'OR' ? 'OR' : 'AND';
                    assert('is_array($value["on"])');
                    $conditions[] = $this->parseCondition($value['on'], $logic, isset($value['value']));
                }
            }
            $this->_joins[] = array($type, $table, $conditions);
        }

        return $this;
    }

    /**
     * has(一对一或者一对多)
     * 拥有的内容，[参数$table的外键是此表的主键][LEFT JOIN]
     *
     * @method has
     *
     * @param  string $table [对应表名]
     * @param  [string] $table_fk    [对应表中的外键，缺省使用$this->_table.'_id']
     * @param  [string] $related_key    [与之关联的主键或者表加主键，默认采用本表主键]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function has($table, $table_fk = null, $related_key = null)
    {
        (null === $table_fk) and ($table_fk = $this->_table . '_id');
        (null === $related_key) and ($related_key = $this->_pk);
        return $this->join('LEFT', $table, $table_fk, $related_key);
    }

    /**
     * 从属关系(一对多或者多对多)
     * 此表的外键 关联参数表的主键是[inner join]
     *
     * @method belongs
     *
     * @param  string  $table [表名]
     * @param  [string]  [$related_key]    [ 此表外键，默认$table.‘_id']
     * @param  [string]  [$primary_key]    [$table 表的关联键]
     *
     * @return [object]   $this
     *
     * @author NewFuture
     */
    public function belongs($table, $related_key = null, $primary_key = 'id')
    {
        (null === $related_key) and ($related_key = $table . '_id');
        return $this->join('INNER', $table, $primary_key, $related_key);
    }

    /**
     * @method group
     * 分组 group by
     *
     * @param  string $field        [description]
     * @param  [string] $exp        [description]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function group($field)
    {
        if (1 === func_num_args()) {
            assert('is_string($field)', '[Orm::group]第一个参数必须是字符串');
            $this->_groups[] = $field;
        } else { //多个参数
            $this->_groups[] = $this->parseCondition(func_get_args());
        }
        return $this;
    }

    /**
     * union 多查询结果合并
     *
     * @method belongs
     *
     * @param  obj  $orm 一个包含查询的orm数据
     * @param  [boolean]  [$is_all=FALSE]    [union all 默认 false]
     *
     * @return [object]   $this
     *
     * @author NewFuture
     */
    public function union(Orm $orm, $is_all = false)
    {
        $is_all = $is_all ? 'UNION ALL ' : ' UNION ';
        $this->_unions[] = $is_all . $this->buildSubquery($orm);
        return $this;
    }

    /**
     * union all 多查询拼接合并
     *
     * @method unionAll
     *
     * @param  obj  $orm 一个包含查询的orm数据
     *
     * @return [object]   $this
     *
     * @author NewFuture
     */
    public function unionAll(Orm $orm)
    {
        return $this->union($orm, true);
    }

    /**
     * 统计【聚合函数】
     *
     * @method count
     *
     * @param  [string] $column_name [默认*]
     * @param  [boolean] $is_distinct [是否对该字段去重]
     *
     * @return int  count统计的数目
     *
     * @author NewFuture
     *
     * @todo CASE when 解析和支持
     */
    public function count($column_name = '*', $is_distinct = false)
    {
        $column_name = $this->qouteField($column_name);
        $is_distinct = $is_distinct ? 'DISTINCT ' : '';
        return $this->aggregate('COUNT(' . $is_distinct . $column_name . ')');
    }

    /**
     * 最小值【聚合函数】
     *
     * @method min
     *
     * @param  string $column_name [字段名称]
     *
     * @return mixed  最小值
     *
     * @author NewFuture
     */
    public function min($column_name)
    {
        return $this->aggregate('MIN(' . $this->qouteField($column_name) . ')');
    }

    /**
     * 最大值【聚合函数】
     *
     * @method max
     *
     * @param  string $column_name [字段名称]
     *
     * @return mixed  最大值
     *
     * @author NewFuture
     */
    public function max($column_name)
    {
        return $this->aggregate('MAX(' . $this->qouteField($column_name) . ')');
    }

    /**
     * 平均值【聚合函数】
     *
     * @method avg
     *
     * @param  string $column_name [字段名称]
     *
     * @return int|string   均值
     *
     * @author NewFuture
     */
    public function avg($column_name)
    {
        return $this->aggregate('AVG(' . $this->qouteField($column_name) . ')');
    }

    /**
     * 求和【聚合函数】
     *
     * @method sum
     *
     * @param  string $column_name [字段名称]
     *
     * @return int|string   均值
     *
     * @author NewFuture
     */
    public function sum($column_name)
    {
        return $this->aggregate('SUM(' . $this->qouteField($column_name) . ')');
    }

    /**
     * 字段自增
     *
     * @method increment
     *
     * @param  string $field 自增字段
     * @param  [int]  $step  [增加步长默认1]
     *
     * @return int      [影响条数]
     *
     * @author NewFuture
     */
    public function increment($key, $step = 1)
    {
        if ($key = $this->getTrueField($key)) {
            $data = array(
                $key => $this->qouteField($key) .'+'. $this->bindParam(intval($step)),
             );
            if ($result = $this->execute($this->buildUpdate($data)) && isset($this->_data[$key])) {
                $this->_data[$key] += $step;
            }
            return $result;
        }
    }


    /**
     * 字段自减
     *
     * @method decrement
     *
     * @param  string $field 自减字段
     * @param  [int]  $step  [自减步长默认1]
     *
     * @return int      [影响条数]
     *
     * @author NewFuture
     */
    public function decrement($field, $step = 1)
    {
        return $this->increment($field, -$step);
    }


    /**
     * 事务封装
     *
     * @method transact
     *
     * @param callable $func，事务回调函数，参数是当前Database，回调返回false或者出现异常回滚，否则提交
     *
     * @return 回调函数的返回值(执行异常自动回滚，返回false)
     *
     * @author NewFuture
     */
    public function transact(callable  $func)
    {
        $db = $this->getDb('_write');//自动调用写数据库
        try {
            $db->beginTransaction();
            $result = $func($this);
            if (false === $result) {
                $db->rollBack();
            } else {
                $db->commit();
            }
            return $result;
        } catch (Exception $e) {
            $db->rollBack();
            Logger::write('[ORM] transact exception: '.$e->getMessage(), 'WARN');
        }
        return false;
    }

    /**
     * 获取数据库链接
     * 如果设置默认database 将直接返回此设置
     *
     * @method getDb
     *
     * @param  string $name 数据库配置名[_read][Write]
     *
     * @return [object] db
     *
     * @author NewFuture
     */
    protected function getDb($name)
    {
        return  $this->_db ?: Db::get($name);
    }

    /**
     * 设定数据库
     *
     * @method setDb
     *
     * @param mixed Databse 对象或者连接配置
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function setDb($db)
    {
        if (is_string($db) || is_array($db)) {
            $this->_db = Db::connect($db);
        } else {
            assert('$db instanceof Service\Database', '[Orm::setDb]传入的对象为 Database实例或者对象');
            $this->_db = $db;//直接设置对象
        }
        return $this;
    }

    /**
     * 安全模式设置
     *
     * @method safe
     *
     * @param  [boolean] $enable [默认开启]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function safe($enable = true)
    {
        $this->_safe = (bool) $enable;
        return $this;
    }


    /**
     * 自动查询完自动清空
     *
     * @method autoClear
     *
     * @param  [boolean] $clear [默认开启]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function autoClear($clear)
    {
        $this->_clear = (bool) $clear;
        return $this;
    }


    /**
     * 开启模式，输出sql而不执行
     *
     * @method debug
     *
     * @param  [boolean] $enable [默认开启]
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function debug($enable = true)
    {
        $this->_debug = (bool) $enable;
        return $this;
    }

    /**
     * 清空
     *
     * @method clear
     *
     * @return $this
     *
     * @author NewFuture
     */
    public function clear($retain = false)
    {
        if ($retain === false) {
            //保留数据和设置
            $this->_data  = array(); //数据
            $this->_safe  = true; //安全模式
            $this->_debug = false;//调试输出
        }

        $this->_param    = array(); //查询参数
        $this->_having   = array();
        $this->_where    = array(); //查询条件
        $this->_fields   = array(); //查询字段
        $this->_joins    = array(); //join 表
        $this->_groups   = array();
        $this->_unions   = array();
        $this->_order    = array(); //排序字段
        $this->_limit    = null;
        $this->_distinct = false; //是否去重
        return $this;
    }


    /**obj实现**/
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->get($name, false);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    /**json序列化接口实现**/
    public function jsonSerialize()
    {
        return $this->_data;
    }

    /**数组操作接口实现**/
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset, false);
    }

    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * 对字段和表名进行反引字符串
     * 并对字符进行安全断言 合法字符为[a-zA-Z_]
     *
     * @method backQoute
     *
     * @param  string    $key [字段名称]
     *
     * @return string
     *
     * @author NewFuture
     */
    public static function backQoute($key)
    {
        assert('ctype_alnum(strtr($key, "_", "A"))', '[Orm::backQoute] 字段中含有非法字符(字母_数字之外的字符):' . $key);
        return '`' . $key . '`';
    }

    /**
     * 构建insert 语句
     *
     * @method buildInsert
     *
     * @param array $data  要插入的数据
     *
     * @return string [sql语句]
     *
     * @author NewFuture
     *
     * @todo sql语句缓存,insertAll 使用事务
     */
    protected function buildInsert(array &$data, $is_sqlite = false)
    {
        reset($data);
        $sql = 'INSERT INTO' . Orm::backQoute($this->_pre.$this->_table). '(';
        $fields = &$this->_fields;
        foreach ($fields as $field => $alias) {
            $sql .= Orm::backQoute(is_int($field) ? $alias : $field) . ',';
        }
        $sql{strlen($sql) - 1} = ')'; //去掉最后的，

        if ($is_sqlite) {
            $sql .= 'SELECT ';
            foreach (current($data) as $key => $value) {
                $sql .= $value.' AS'.Orm::backQoute($key) . ',';
            }
            $sql{strlen($sql) - 1} = PHP_EOL;
            while ($next = next($data)) {
                $sql .= 'UNION ALL SELECT '.implode(',', $next).PHP_EOL;
            }
        } else {
            $sql .= 'VALUES';
            // reset($data);
            if (is_string(key($data))) {
                assert('is_array($data)&&count($fields)===count($data)', '[Orm::buildInsert] $data 应该是数组');
                $sql .= '('.implode(',', $data).'),';
            } else {
                assert('is_array($data[0])&&count($fields)===count($data[0])', '[Orm::buildInsert] $data 应该是一个二维数组,其中每个数组是一条记录');
                foreach ($data as &$row) {
                    $sql .= '('.implode(',', $row).'),';
                }
                unset($row);
            }
            $sql{strlen($sql) - 1} = ' ';
        }
        return $sql;
    }

    /**
     * 构建update 语句
     *
     * @method buildUpdate
     *
     * @param array &$data 跟新的数据(参数已经替换)，
     *
     * @return string [生成的sql语句]
     *
     * @author NewFuture
     *
     * @todo sql语句缓存
     */
    protected function buildUpdate(array &$data)
    {
        $sql = 'UPDATE' . Orm::backQoute($this->_pre . $this->_table) . 'SET ';
        foreach ($data as $key => $v) {
            $sql .= Orm::backQoute($key) . '='.$v.',';
        }
        $sql{strlen($sql) - 1} = ' ';
        $sql .= $this->buildJoin()
                .$this->buildWhere(true)
                .$this->buildTail(false);
        return $sql;
    }

    /**
     * 构建select 语句
     *
     * @method buildSelect
     *
     * @param  string $exp=null，
     *
     * @return string [select xxx]
     *
     * @author NewFuture
     */
    protected function buildSelect($exp = null)
    {
        $sql = $this->_distinct ? 'SELECT DISTINCT ' : 'SELECT ';
        if (null !== $exp) {
            $sql .= $exp;
        } elseif ($fields = &$this->_fields) {
            // 支持 'field'=>'alias' 这样的字段别名定义
            foreach ($fields as $field => $alias) {
                if (is_int($field) || $field === $alias) {
                    //无别名(索引键值)或者别名与字段名相同
                    $sql .= $this->qouteField($alias) . ',';
                } else { //别名和表达式解析
                    $sql .= $this->parseFunction($field) . ' AS' . Orm::backQoute($alias) . ',';
                }
            }
            $sql{strlen($sql) - 1} = ' '; //最后的,换成空格
        } else {
            /*多表链接时加入表名*/
            $table = $this->_alias ?: $this->_pre.$this->_table;
            $sql .= empty($this->_joins) ? '*' : Orm::backQoute($table) . '.*';
        }

        $sql .= $this->buildFrom()
                . $this->buildJoin()
                . $this->buildWhere()
                . $this->buildGroupHaving()
                . $this->buildTail();
        return $sql;
    }

    /**
     * 构建delete 语句
     *
     * @method buildSelect
     *
     * @return string
     *
     * @author NewFuture
     *
     * @todo sql语句缓存
     */
    protected function buildDelete()
    {
        if ($alias = &$this->_alias) {
            $sql = 'DELETE' . Orm::backQoute($alias);
        } else {
            $sql = 'DELETE ';
        }
        $sql .= $this->buildFrom()
                .$this->buildJoin()
                .$this->buildWhere(true)
                .$this->buildTail(false);

        return $sql;
    }

    /**
     * 构建FROM sql分句
     * From
     *
     * @method buildFrom
     *
     * @return string
     *
     * @author NewFuture
     *
     * @todo 是否对多表扩展支持？
     */
    protected function buildFrom()
    {
        $name = $this->_pre . $this->_table;
        $from = 'FROM' . Orm::backQoute($name);
        if ($alias = $this->_alias) {
            $from .= 'AS' . Orm::backQoute($alias);
        }
        return $from;
    }

    /**
     * 构建条件Join sql分句
     *
     * @method buildJoin
     *
     * @return string        [''或者JOIN(xxx)]
     *
     * @author NewFuture
     */
    protected function buildJoin()
    {
        $sql = '';
        $prefix = &$this->_pre;
        $join_table = '';
        $join_alias = '';
        foreach ($this->_joins as &$join) {
            $join_table = $join[1];
            if ($pos = stripos($join_table, ' AS ')) {
                //别名形式
                $join_alias = Orm::backQoute(substr($join_table, $pos + 4));
                $join_table = Orm::backQoute($prefix.substr($join_table, 0, $pos)).'AS'.$join_alias;
            } else {
                $join_table = Orm::backQoute($prefix . $join_table);
                $join_alias = $join_table;
            }
            if (count($join) === 4) {
                //简单条件
                $sql .= $join[0] . ' JOIN' . $join_table. 'ON'
                    . $join_alias .'.'. Orm::backQoute($join[2]) . '=' . $this->qouteField($join[3], true);
            } else {
                //复杂条件
                assert('is_array($join[2])', '[Orm::buildCondition] 复杂条件第三个应该是数组');
                $sql .= $join[0] . ' JOIN' . $join_table. $this->buildCondition($join[2], 'ON');
            }
        }
        return $sql;
    }

    /**
     * 构建条件WhERE sql分句
     *
     * @method buildWhere
     *
     * @return string        [''或者WHERE(xxx)]
     *
     * @author NewFuture
     */
    protected function buildWhere($check_pk = false)
    {
        if ($where = &$this->_where) {
            return $this->buildCondition($where, 'WHERE');
        } elseif ($check_pk && isset($this->_data[$this->_pk])) {
            //空条件且设置了主键，使用主键作为更新参数
            //see parseCondition()
            $where = array(
                array('AND', $this->_pk,'=',$this->bindParam($this->_data[$this->_pk])),
            );
            return $this->buildCondition($where, 'WHERE');
        } else {
            return '';
        }
    }


    /**
     * 构建 GROUP 和 HAVING sql分句
     *
     * @method buildGroupHaving
     *
     * @return string        [''或者GROUP(xxx)]
     *
     * @author NewFuture
     */
    protected function buildGroupHaving()
    {
        $sql = '';
        if ($groups = &$this->_groups) {
            $condition = '';
            foreach ($groups as &$g) {
                if (is_array($g)) {
                    $condition .= ',' . $this->qouteField($g[0]) . $g[1].' '.$g[2];
                } else {
                    $condition .= ',' . $this->qouteField($g);
                }
            }
            unset($g);
            $sql .= substr_replace($condition, 'GROUP BY ', 0, 1);
        }
        if ($having = &$this->_having) {
            $condition = '';
            foreach ($having as &$h) {
                $condition .= $h[0] . '(' . $this->parseFunction($h[1]) . $h[2] .$h[3]. ')';//聚合函数
            }
            unset($h);
            $sql .= 'HAVING' . strstr($condition, '(');
        }
        return $sql;
    }

    /**
     * 构建尾部 sql 分句 limt,order by,等
     *
     * @method buildTail
     *
     * @return string
     *
     * @author NewFuture
     */
    protected function buildTail($offset = true)
    {
        $limit = $this->_limit ?: '';
        $param = &$this->_param;
        if (false === $offset) {
            $db = $this->getDb('_write');
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ('mysql' === $driver) {
                //mysql
                if ($limit) {
                    if (isset($limit[1]) && isset($param[$limit[1]])) {
                        unset($param[$limit[1]]);
                    }
                    $limit = ' LIMIT ' . $limit[0];
                }
            } elseif ('sqlite' === $driver
                && !in_array(array('compile_option' => 'ENABLE_UPDATE_DELETE_LIMIT'), $db->query('PRAGMA compile_options'))) {
                //sqlite and not support ENABLE_UPDATE_DELETE_LIMIT
                // clear limit
                // risk
                $this->_limit = null;
                if ($limit) {
                    if ($param[$limit[0]] > 1) {
                        Logger::error('[ORM] 当前SQLITE不支持update with limit, limit条件('.$param[$limit[0]].')已被自动忽略!(重新编译sqlite加上参数 ENABLE_UPDATE_DELETE_LIMIT)');
                    }
                    unset($param[$limit[0]]);
                    if (isset($limit[1]) && isset($param[$limit[1]])) {
                        if ($param[$limit[1]] > 1) {
                            Logger::error('[ORM] 当前SQLITE不支持update 不支持offset,已自动忽略');
                        }
                        unset($param[$limit[1]]);
                    }
                }
                return '';
            }
        }

        if (is_array($limit)) {
            $limit = isset($limit[1]) && isset($param[$limit[1]]) ?
                " LIMIT ${limit[0]} OFFSET ${limit[1]}" : " LIMIT ${limit[0]}";
        }

        $sql = '';
        if ($order = &$this->_order) {
            $sql = 'ORDER BY';
            foreach ($order as $field => $type) {
                $sql .= $this->qouteField($field) . $type . ',';
            }
            $sql{strlen($sql) - 1} = ' ';
        }
       
        if ($unions = &$this->_unions) {
            $sql .= implode(' ', $unions);
        }
        return $sql.$limit;
    }

    /**
     * 构建子查询
     *
     * @method buildSubquery
     *
     * @param  Orm &$orm  [ORM对象]
     *
     * @return string     [SQL语句]
     *
     * @author NewFuture
     */
    protected function buildSubquery(Orm &$orm)
    {
        $query = $orm->debug(true)->select();
        $orm->debug(false);
        $this->_param += $query['param'];
        return '('.$query['sql'].')';
    }

    /**
     * 聚合函数
     *
     * @method aggregate
     *
     * @param  string $exp      [聚合表达式]
     *
     * @return [int|string]  [返回聚合操作结果]
     *
     * @author NewFuture
     */
    protected function aggregate($exp)
    {
        return $this->value($this->buildSelect($exp));
    }

    /**
     * 数据写入修改操作
     *
     * @method execute
     *
     * @param  string $sql      [sql语句]
     *
     * @return [int]           [影响行数]
     *
     * @author NewFuture
     */
    protected function execute($sql)
    {
        if (true === $this->_debug) {
            return array('sql' => $sql,'param' => $this->_param);
        } elseif ($this->_clear) {
            $result = $this->getDb('_write')->exec($sql, $this->_param);
            $this->clear(true);
            return $result;
        } else {
            return $this->getDb('_write')->exec($sql, $this->_param);
        }
    }

    /**
     * 数据读取操作
     *
     * @method query
     *
     * @param  [string] $sql      [sql语句]
     *
     * @return [array]           [结果数组]
     *
     * @author NewFuture
     */
    protected function query($sql, $fetchAll = true)
    {
        if (true === $this->_debug) {
            return array('sql' => $sql,'param' => $this->_param);
        } elseif ($this->_clear) {
            $result = $this->getDb('_read')->query($sql, $this->_param, $fetchAll);
            $this->clear(true);
            return $result;
        } else {
            return  $this->getDb('_read')->query($sql, $this->_param, $fetchAll);
        }
    }

    /**
     * 数据读取操作,返回一个值
     *
     * @method value
     *
     * @param  string $sql      [sql语句]
     *
     * @return [mixed]           [查询结果]
     *
     * @author NewFuture
     */
    protected function value($sql)
    {
        return true === $this->_debug ?
            array('sql' => $sql,'param' => $this->_param) :
            $this->getDb('_read')->column($sql, $this->_param);
    }

    /**
     * @method bindParam
     *
     * @param  mixed   $value [参数值]
     * @param [string] $key [指定键值,默认自增]
     *
     * @return 返回key
     *
     * @author NewFuture
     */
    protected function bindParam($value)
    {
        $key = ':' . Orm::$_paramid;
        Orm::$_paramid += 1;
        $this->_param[$key] = $value;
        return $key;
    }


    /**
     * 字段过滤 [支持别名方式的字段，别名得数据将被替换成证实字段名]
     *
     * @method fieldFilter
     *
     * @param  array $fields [字段,会被过滤]
     * @param  array &$data [数据,被过滤的数据]
     *
     * @return array 过滤后的字段
     *
     * @author NewFuture
     */
    protected static function fieldFilter(array $fields, array &$data)
    {
        assert('is_array($fields)&&is_array($data)', '[Orm::fieldFilter]过滤字段和数据都应该是数组');
        asort($fields);
        ksort($data);
        $fields = array_intersect($fields, array_keys($data)); //合并后的字段
        $data = array_intersect_key($data, array_flip($fields));
        assert('count($fields)===count($data)', '[Orm::fieldFilter]过滤后字段和数据大小不一致');
        return $fields;
    }

    /**
     * @method qouteField
     * 字段加引号
     *
     * @param  string     $field_str     [字段名称或者$table.$field]
     * @param  [string]   $default_table [默认table，如果没有则加上table,如果射未TRUE使用此表名处理]
     *
     * @return  string     [description]
     *
     * @author NewFuture
     */
    protected function qouteField($field_str, $default_table = null)
    {
        if ((false === $this->_safe) && (false !== strpbrk($field_str, ' (<=>-+,*/'))) {
            return $field_str;//非安全模式特殊字符直接返回
        }
        $field = explode('.', $field_str);
        switch (count($field)) {
            case 1:
                if ($default_table === true) {
                    $qouted_sql = Orm::backQoute($this->_pre.$this->_table).'.';
                } else {
                    $qouted_sql = $default_table ? Orm::backQoute($default_table).'.' : '';
                }
                $qouted_sql .= ('*' === $field[0]) ? '*' : Orm::backQoute($field[0]);
                break;
            case 2:
                $qouted_sql = Orm::backQoute($field[0]) . '.';
                $qouted_sql .= ('*' === $field[1]) ? '*' : Orm::backQoute($field[1]);
                break;
            default:
                if ($this->_safe) {
                    throw new Exception('[无法解析表名.字段]:' . $field_str);
                } else {
                    return $field_str;
                }
        }
        return $qouted_sql;
    }


    /**
     * @method parseFunction
     * 校验聚合函数，并将字段加反引号
     * 关闭安全模式将停止解析
     *
     * @param  string     $str     [字段名称或者$table.$field]
     *
     * @return  string     [description]
     *
     * @author NewFuture
     */
    protected function parseFunction($str)
    {
        if (false === $this->_safe) {
            return $str;//关闭安全模式不解析
        }
        $str = ltrim(rtrim($str, ') '), '( ');
        $fun = strtoupper(strtok($str, '('));//(拆解函数
        if (strlen($fun) === strlen($str)) {
            //不是函数按照字段处理
            return $this->qouteField($str);
        } elseif (in_array($fun, array('ABS', 'AVG', 'COUNT', 'LCASE', 'LENGTH', 'MAX', 'MIN', 'SIGN', 'SUM', 'UCASE', ))) {
            $arg = trim(strtok(')'));//字段
            assert('false===strtok(")")', '[Orm::parseFunction]函数表达式解析异常'.$str);
            return $fun.'('.$this->qouteField($arg).')';
        }
        throw new Exception('无法解析表达式'.$str);
    }

    /**
     * @method parseFunction
     * 校验聚合函数，并将字段加反引号
     * 关闭安全模式将停止解析
     *
     * @param  array   $condition     [格式化的条件数组]
     * @param  string $pre='' $SQl 拼接后的前缀
     *
     * @return  string  [sql语句]
     *
     * @author NewFuture
     */
    protected function buildCondition(&$condition, $pre = '')
    {
        $sql = '';
        foreach ($condition as $w) {
            $sql .= $w[0].'(';
            switch (count($w)) {
                case 2://直接SQL语句;如exis
                    $sql .= $w[1];
                    break;

                case 5://字段与字段关系，对字段编码
                    if (is_array($w[3])) {
                        assert('in_array($w[2],array("BETWEEN","NOT BETWEEN","IN","NOT IN"))',
                            '[Orm::buildCondition]只有IN和between后可接数组参数');
                        foreach ($w[3] as &$f) {
                            $f = $this->qouteField($f);
                        }
                        unset($f);
                    } else {
                        assert('in_array($w[2],array("=","<>",">",">=","<","<=","LIKE","NOT LIKE","LIKE BINARY","NOT LIKE BINARY"))',
                            '[Orm::buildCondition]只有值比较可以使用这些类型');
                        $w[3] = $this->qouteField($w[3]);
                    }
                    //继续处理
                case 4:
                    $operator = &$w[2];
                    $value = &$w[3];
                    if (is_array($value)) {
                        if ('BETWEEN' === $operator || 'NOT BETWEEN' === $operator) { //BETWEEN
                            assert('2===count($value)', '[Orm::buildCondition] between 操作后续 参数必须是两个值');
                            $value = $value[0].' AND '.$value[1];
                        } else {
                            //IN
                            assert('in_array($operator, array("IN","NOT IN"))', '[Orm::buildCondition] 只有IN和BETWEEN相关操作能使用数组'.$w[2]);
                            $value = '('.implode(',', $value).')';
                        }
                    }
                    $sql .= $this->qouteField($w[1]).$operator.' '.$value;
                    break;
                default:
                    throw new Exception('无法处理的条件:'.json_encode($w), 1);
            }
            $sql .= ')';
        }
        return $pre . strstr($sql, '(');
    }

    /**
     * 解析where条件
     *
     * @method parseWhere
     *
     * @param  array $where [where 参数数组]
     * @param  string $addition [附加条件，'AND'或者'OR']
     * @param  [boolean] $bind_value [是否绑定参数]
     *
     * @return array 格式化的三元或者多元元索引数组
     *
     * @author NewFuture
     */
    protected function parseWhere(array $where, $type, $bind_value = true)
    {
        if (is_array($where[0])) {
            assert('1===count($where)', '[Orm::parseWhere]使用where数组型参数,直接收一个参数');
            foreach ($where[0] as $key => $value) {
                //支持键值对和多维数组方式
                $this->_where[] = is_array($value) ?
                     $this->parseCondition($value, $type, $bind_value) :
                     $this->parseCondition(array($key, $value), $type, $bind_value);
            }
        } else {
            assert('isset($where[1])||!$this->_safe', '安全模式where条件至少需要两个参数');
            $this->_where[] = $this->parseCondition($where, $type, $bind_value);
        }
        return $this;
    }

    /**
     * 解析条件 数组
     *
     * @method parseCondition
     *
     * @param  array $condition [条件数组]
     * @param  [string] $addition [附加条件，'AND'或者'OR']
     * @param  [boolean] $bind_value [是否绑定参数]
     *
     * @return array 格式化的三元或者多元元索引数组
     *          array([$addition,],$field,$operator,$value[,NO_BIND_FLAG])
     *
     * @author NewFuture
     */
    protected function parseCondition(array $condition, $addition = null, $bind_value = true)
    {
        assert('is_array($condition)',
            '[Orm::parseCondition]条件解析数据必须是数组');
        assert('is_string($condition[0])',
            '[Orm::parseCondition]条件数组的第一个元素必须是字符串');
        assert('!isset($condition[1])||(is_scalar($condition[1])||is_null($condition[1]))',
            '[Orm::parseCondition]条件数组第二个参数必须是基本类型');
        $result = $addition ? array($addition,$condition[0]) : array($condition[0]);
        switch (count($condition)) {
            case 2: //两个值,相等条件
                if ((null === $condition[1])) {
                    assert('!$bind_value', '[Orm::parseCondition]NULL值时不能设为字段');
                    $result[] = 'IS';
                    $result[] = 'NULL';
                } else {
                    $result[] = '=';
                    $result[] = $bind_value ? $this->bindParam($condition[1]) : $condition[1];
                }
                break;

            case 3: //三个值，三元表达式
                $operator = strtoupper($condition[1]);
                $value = &$condition[2];
                if (null === $value) { //NULL值判断标准化
                    assert('in_array($condition[1],array("=","<>","!=","IS"))',
                     '[Orm::parseCondition]NULL值判读只允许 [等于] 或者[不等于]');
                    assert('!$bind_value', '[Orm::parseCondition]NULL值时不能设为字段');
                    $result[] = 'IS';
                    $result[] = (('=' === $operator) || ('IS' === $operator)) ? 'NULL' : 'NOT NULL';
                } else {
                    //不等号标准化
                    $result[] = '!=' === $operator ? '<>' : $operator;
                    if ($bind_value) {
                        //绑定参数
                        if (is_array($value)) {
                            assert('in_array($operator,array("BETWEEN","NOT BETWEEN","IN","NOT IN"))',
                                '[Orm::parseCondition]只有IN和between后可接数组参数');
                            foreach ($value as &$v) {
                                $v = $this->bindParam($v);
                            }
                            unset($v);
                        } else {
                            assert('in_array($operator,array("=","<>","!=",">",">=","<","<=","LIKE","NOT LIKE","LIKE BINARY","NOT LIKE BINARY"))',
                                '[Orm::parseCondition]只有值比较可以使用这些类型');
                            $value = $this->bindParam($value);
                        }
                    }
                    $result[] = $value;
                }
                break;

            case 4: //4元表达式between
                $operator = strtoupper($condition[1]);
                assert('in_array($operator,array("BETWEEN","NOT BTWEEN"))',
                    '[Orm::parseCondition] 四参数条件只支持[not ]between表达式 :' . $operator);
                $result[] = $operator ;
                $result[] = $bind_value ?
                    array($this->bindParam($condition[2]),$this->bindParam($condition[3])) :
                    array($condition[2],$condition[3]);
                break;

            case 1: //表达式
                if ($this->_safe) {
                    throw new Exception('安全模式，where条件不允许设置一个参数的或者单条sql条件语句[此语句不会解析和封装]');
                    return;
                }
                $result = array(null,$condition[0]);
                break;

            default:
                throw new Exception('where条件参数太多，无法解析.' . json_encode($condition, 256));
                break;
        }
        if (!$bind_value) {
            $result[] = true;
        }
        return $result;
    }

    // /*implements IteratorAggregate 迭代器 php 版本>=5.5*/
    // public function getIterator() {
    //     foreach ($this->_data as $key => $value) {
    //         yield $key=>$value;
    //     }
    // }
}
