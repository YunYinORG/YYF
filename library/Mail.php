<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

use \Service\Message;
use \Service\Smtp;
use \Yaf_View_Simple as View;

/**
 * Mail 邮件发送类
 *
 * @author NewFuture
 *
 * @todo 完善和可配置
 */
class Mail
{
    protected $_config;
    private $_smtp;
    private $_view;
    private static $_instance = null;

    private function __construct()
    {
        $this->_config = Config::getSecret('mail');
        $this->_smtp   = new Smtp();
        $server        = $this->_config['server'];
        $this->_smtp->setServer($server['smtp'], $server['port'], $server['secure']);
    }

    /**
     * 发送验证邮件
     *
     * @param string $email [邮箱]
     * @param string $name  [姓名]
     * @param string $link  [验证链接]
     *
     * @return bool 发送结果
     */
    public static function sendVerify($email, $name, $link)
    {
        $instance = self::getInstance();
        $from     = $instance->_config['verify'];
        $to       = array('email' => $email, 'name' => $name ?: $email);
        $url      = $instance->_config['verify']['baseuri'].$link;

        $msg['title'] = 'YYF验证邮件';
        $msg['body']  = $instance->getView()
                                 ->assign('name', $name)
                                 ->assign('url', $url)
                                 ->render('verify.tpl');
        return $instance->send($from, $to, $msg);
    }

    /**
     * 发送邮件
     *
     * @param string $to   [接收方邮箱]
     * @param array  $msg  [发送信息]
     * @param mixed  $from
     *
     * @return bool [发送结果]
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

    /**
     * 获取邮件服务对象
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
        // return self::$_instance ?: (self::$_instance = new self());
    }

    /**
     * 获取模板引擎
     */
    private function getView()
    {
        if (!$this->_view) {
            $this->_view = new View(APP_PATH.'/app/email/');
        }
        return $this->_view;
        // return $this->_view ?: ($this->_view = new View(self::TPL_DIR));
    }
}
