<?php
/**
 * 基本的Facde接口，对model封装
 * @example
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