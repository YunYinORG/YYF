<?php
/**
 * 基本的Facde接口，对model封装
 * @example
 * 	class UserModel extends Model{}
 *
 * 	UserModel::find(1);//返回id为1的数据
 * 	UserModel::slecet('id,number,name AS username');//列出用户
 *
 * 	UserModel::set('pwd','1234')->save(1);//将id为1的用户密码设置为1234
 * 	UserModel::where('id','=','1')->update(['pwd'=>'1234']);//同上，也可以使用save
 *
 * 	UserModel::set(['pwd'=>'mypwd','name'=>'test'])->add();//添加新用户
 * 	UserModel::add(['pwd'=>'mypwd','name'=>'test']);//同上，也可以使用insert()
 *
 *  UserModel::where('id','>','10')->where('id','<','100')->select();//查找所有id在10到100之间的用户
 *  UserModel::where('id','=','1')->get('name');//获取id为一的用户的name
 *  UserModel::where('id','>','1')->limit(10)->select('id,name');//查询id>1的10个用户的id和name
 *  UserModel::where('id','>','1')->field('id','uid')->field('name','uname')->select();//查询id>1的用户的uid和uname(表示id和name)
 *  UserModel::where('name','LIKE','%future%')->count();//统计name中包含future的字数
 *
 * 也可以实例化操作 $user=new UserModel;
 * $user->find（1）;//
 */
abstract class Model
{
    protected $name   = null; //数据库表
    protected $pk     = 'id'; //主键
    protected $prefix = true; //前缀
    protected $fields = null; //字段过滤
    protected $dbname = null; //使用指定数据库

    private $_orm   = null; //底层orm

    /**
     * 构造函数
     * @method __construct
     * @param  array       $data [传入数据]
     * @access public
     * @author NewFuture
     */
    final public function __construct(array $data = null)
    {
        if (!$name=&$this->name) {
            $name=strtolower(preg_replace('/.(?=[A-Z])/', '$1_', substr(get_called_class(), 0, -5)));
        }
        $this->_orm = new Orm($name, $this->pk, $this->prefix);
        
        if ($this->fields) {
            //字段设置
            $this->_orm->field($this->fields);
        }
        if ($data) {
            //预填充数据
            $this->_orm->set($data);
        }
        if ($this->dbname) {
            //指定数据库
            $this->_orm->setDb($this->dbname);
        }
    }

    /**
     * 获取模型实例
     * @method getOrm
     * @return [type]   [description]
     * @author NewFuture
     */
    public function getOrm()
    {
        return $this->_orm;
    }

    public function toArray()
    {
        return $this->_orm->get();
    }

    /**
     * 数据转成json
     * @method toJson
     * @param constant   JSON_ENCODE type
     * @author NewFuture
     */
    public function toJson($type=256)//256isJSON_UNESCAPED_UNICODE
    {
        return json_encode($this->_orm->get(), $type);
    }

    /**
     * 直接修改字段
     * @method __set
     * @param  [type]  $name  [description]
     * @param  [type]  $value [description]
     * @access public
     * @author NewFuture
     */
    public function __set($name, $value)
    {
        return $this->_orm->set($name, $value);
    }

    /**
     * 直接读取字段
     * @method __get
     * @return [type]  [description]
     * @access public
     * @author NewFuture
     */
    public function __get($name)
    {
        return $this->_orm->get($name, false);
    }

    /**
     * 直接调用model的操作
     * @author NewFuture
     */
    public function __call($method, $params)
    {
        return call_user_func_array(array($this->getOrm(), $method), $params);
    }

    /**
     * 静态调用model的操作
     * @author NewFuture
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array(array((new static)->getOrm(), $method), $params);
    }
}
