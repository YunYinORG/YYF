<?php
use \Service\Message;
use \Service\Smtp;

/**
 * 邮件发送类
 */
Class Mail
{
	protected $_config;
	const TPL_DIR = APP_PATH . '/app/email/';
	private $_smtp;
	private $_view;
	private static $_instance = null;

	/**
	 * 发送验证邮件
	 * @method sendVerify
	 * @param  [string] $email 	[邮箱]
	 * @param  [string] $name   [姓名]
	 * @param  [type] $link 	[验证链接]
	 * @return [type]        	[发送结果]
	 * @author NewFuture
	 */
	public static function sendVerify($email, $name, $link)
	{
		$instance = self::getInstance();
		$from     = $instance->_config['verify'];
		$to       = ['email' => $email, 'name' => $name ?: $email];
		$url      = $instance->_config['verify']['baseuri'] . $link;

		$msg['title'] = 'YYF验证邮件';
		$msg['body']  = $instance->getView()
		                         ->assign('name', $name)
		                         ->assign('url', $url)
		                         ->render('verify.tpl');
		return $instance->send($from, $to, $msg);
	}

	/**
	 * 发送邮件
	 * @method send
	 * @param  [string] $from 	[发送方邮箱]
	 * @param  [string] $to   	[接收方邮箱]
	 * @param  [array] 	$msg  	[发送信息]
	 * @return [bool]     		[发送结果]
	 * @author NewFuture
	 */
	public function send($from, $to, $msg)
	{
		$Message = new Message();
		$Message->setFrom($from['name'], $from['email'])
		        ->addTo($to['name'], $to['email'])
		        ->setSubject($msg['title'])
		        ->setBody($msg['body']);
		return $this->_smtp
		            ->setAuth($from['email'], $from['pwd'])
		            ->send($Message);
	}

	public static function getInstance()
	{
		return self::$_instance ?: (self::$_instance = new self());
	}

	//获取模板引擎
	private function getView()
	{
		return $this->_view ?: ($this->_view = new Yaf_View_Simple(self::TPL_DIR));
	}

	private function __construct()
	{
		$this->_config = Config::getSecret('mail');
		$this->_smtp   = new Smtp();
		$server        = $this->_config['server'];
		$this->_smtp->setServer($server['smtp'], $server['port'], $server['secure']);
	}
}