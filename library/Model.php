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
class Model
{
	protected $_name  = null; //数据库表
	protected $_pk    = 'id'; //主键
    protected $_pre   = null; //前缀
	protected $_orm   = null; //底层orm
	
    protected static $_instance = null; //接口实体

	/**
	 * 构造函数
	 * @method __construct
	 * @param  array       $data [传入数据]
	 * @access public
	 * @author NewFuture
	 */
	public function __construct($data = array())
	{
		if (null === $this->_name)
		{
			$this->_name = strtolower(strstr(get_called_class(), 'Model', true));
		}
		$this->_orm = new Orm($this->_name, $this->_pk);
		if (!empty($data))
		{
			$this->_orm->set($data);
		}
	}

	/*获取错误信息*/
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * 获取模型实例
	 * @method getModel
	 * @return [type]   [description]
	 * @author NewFuture
	 */
	protected function getModel()
	{
		if (null === $this->_orm)
		{
			$this->_orm = new Model($this->_name, $this->pk);
		}
		return $this->_orm;
	}

	/**
	 * 获取实现的实体
	 * @method getInstance
	 * @return [type]      [description]
	 * @author NewFuture
	 */
	public static function getInstance()
	{
		return static::$_instance?:static::$_instance = new static;
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
		return call_user_func_array(array($this->getModel(), $method), $params);
	}

	/**
	 * 静态调用model的操作
	 * @author NewFuture
	 */
	public static function __callStatic($method, $params)
	{
		// $instance = new static; //::getInstance();
		return call_user_func_array(array(self::getInstance()->getModel(), $method), $params);
	}
}