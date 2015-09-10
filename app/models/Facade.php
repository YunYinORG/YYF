<?php
/**
 * 基本的Facde接口，对model封装
 * @example
 * 	class UserModel extends FacadeModel{}
 *
 * 	UserModel::find(1);//返回id为1的数据
 * 	UserModel::slecet(['school'=>1]);//查找所有school值为1的用户
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
abstract class FacadeModel
{
	protected $table            = null; //数据库表
	protected $pk               = 'id'; //主键
	protected $error            = '';   //错误信息
	protected $_model           = null; //底层model实体
	protected static $_instance = null; //接口实体

	/**
	 * 构造函数
	 * @method __construct
	 * @param  array       $data [description]
	 * @access private
	 * @author NewFuture
	 */
	public function __construct($data = array())
	{
		if (null === $this->table)
		{
			$this->table = strtolower(strstr(get_called_class(), 'Model', true));
		}
		$this->_model = new Model($this->table, $this->pk);
		if (!empty($data))
		{
			$this->_model->set($data);
		}
	}

	/*获取错误信息33*/
	public function getError()
	{
		return $this->error;
	}

	/**
	 * 获取模型实例
	 * @method getModel
	 * @return [type]   [description]
	 * @author NewFuture
	 */
	protected function getModel()
	{
		if (null === $this->_model)
		{
			$this->_model = new Model($this->table, $this->pk);
		}
		return $this->_model;
	}

	/**
	 * 获取实现的实体
	 * @method getInstance
	 * @return [type]      [description]
	 * @author NewFuture
	 */
	public static function getInstance()
	{
		if (null === static::$_instance)
		{
			static::$_instance = new static;
		}
		return static::$_instance;
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
		return $this->_model->set($name, $value);
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
		return $this->_model->get($name, false);
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
		$instance = new static; //::getInstance();
		return call_user_func_array(array($instance->getModel(), $method), $params);
	}
}