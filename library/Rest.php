<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

use Yaf_Controller_Abstract as Controller_Abstract;

/**
 * REST 控制器
 *
 * @author NewFuture
 */
abstract class Rest extends Controller_Abstract
{
    /**
     * 完成响应数据
     *
     * @var array
     */
    protected $response = false; //自动返回数据

    /**
     * 响应状态码
     *
     * @var int
     */
    protected $code = 200;

    /**
     * 配置信息
     *
     * @access private
     *
     * @var array
     */
    private $_config;

    /**
     * 结束时自动输出信息
     */
    public function __destruct()
    {
        if ($this->response !== false) {
            header('Content-Type: application/json; charset=utf-8', true, $this->code);
            echo json_encode($this->response, $this->_config['json']);
        }
    }

    /**
     * 初始化 REST 路由
     * 修改操作 和 绑定参数
     *
     * @access protected
     */
    protected function init()
    {
        Yaf_Dispatcher::getInstance()->disableView(); //立即输出响应，并关闭视图模板
        $request = $this->_request;

        /*请求来源，跨站cors响应*/
        if ($cors = Config::get('cors')) {
            $this->corsHeader($cors->toArray());
        }

        /*请求操作判断*/
        $method = $request->getMethod();
        $type   = $request->getServer('CONTENT_TYPE');
        if ($method === 'OPTIONS') {
            /*cors 跨域header应答,只需响应头即可*/
            exit;
        } elseif (strpos($type, 'application/json') === 0) {
            /*json 数据格式*/
            if ($inputs = file_get_contents('php://input')) {
                $input_data = json_decode($inputs, true);
                if ($input_data) {
                    $GLOBALS['_'.$method] = $input_data;
                } else {
                    parse_str($inputs, $GLOBALS['_'.$method]);
                }
            }
        } elseif ($method === 'PUT' && ($inputs = file_get_contents('php://input'))) {
            /*直接解析*/
            parse_str($inputs, $GLOBALS['_PUT']);
        }

        /*Action路由*/
        $action        = $request->getActionName();
        $this->_config = Config::get('rest')->toArray();
        if (is_numeric($action)) {
            /*数字id绑定参数*/
            $request->setParam($this->_config['param'], intval($action));
            //提取请求路径
            $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] :
                        strstr($_SERVER['REQUEST_URI'].'?', '?', true);
            $path   = substr(strstr($path, $action), strlen($action) + 1);
            $action = $path ? strstr($path.'/', '/', true) : $this->_config['action'];
        }

        $rest_action = $method.'_'.$action; //对应REST_Action

        /*检查该action操作是否存在，存在则修改为REST接口*/
        if (method_exists($this, $rest_action.'Action')) {
            /*存在对应的操作*/
            $request->setActionName($rest_action);
        } elseif (!method_exists($this, $action.'Action')) {
            /*action和REST_action 都不存在*/
            if (method_exists($this, $this->_config['none'].'Action')) {
                $request->setActionName($this->_config['none']);
            } else {
                $this->response(-8, array(
                        'error'      => '未定义操作',
                        'method'     => $method,
                        'action'     => $action,
                        'controller' => $request->getControllerName(),
                        'module'     => $request->getmoduleName(),
                    ), 404);
                exit;
            }
        } elseif ($action !== $request->getActionName()) {
            /*修改后的$action存在而$rest_action不存在,绑定参数默认控制器*/
            $request->setActionName($action);
        }
    }

    /**
     * 设置返回信息，立即返回
     *
     * @access protected
     *
     * @param int   $status 返回状态
     * @param mixed $data   返回数据
     * @param int   $code   可选参数，设置响应状态吗
     */
    protected function response($status, $data = null, $code = null)
    {
        $this->response = array(
            $this->_config['status'] => $status,
            $this->_config['data']   => $data,
        );
        ($code > 0) && $this->code = $code;
        exit;
    }

    /**
     * 快速返回成功信息(status为1)
     *
     * @access protected
     *
     * @param mixed $data 返回数据内容
     * @param int $code 设置状态码[默认200]
     */
    protected function success($data = null, $code = 200)
    {
        $this->response = array(
            $this->_config['status'] => 1,
            $this->_config['data']   => $data,
        );
        $this->code = $code;
        exit;
    }

    /**
     * 快速返回失败信息(status为0)
     *
     * @access protected
     *
     * @param mixed $data 返回数据内容
     * @param int $code 设置状态码[默认200]
     */
    protected function fail($data = null, $code = 200)
    {
        $this->response = array(
            $this->_config['status'] => 0,
            $this->_config['data']   => $data,
        );
        $this->code = $code;
        exit;
    }

    /**
     * CORS 跨域请求响应头处理
     *
     * @param array $cors CORS配置
     * @access private
     */
    private function corsHeader(array $cors)
    {
        //请求来源站点
        $from = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] :
                    (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);

        if ($from) {
            $domains = $cors['Access-Control-Allow-Origin'];
            if ($domains !== '*') {//非通配
                $domain = strtok($domains, ',');
                while ($domain) {
                    if (strpos($from, rtrim($domain, '/')) === 0) {
                        $cors['Access-Control-Allow-Origin'] = $domain;
                        break;
                    }
                    $domain = strtok(',');
                }
                if (!$domain) {
                    /*非请指定的求来源,自动终止响应*/
                    header('Forbid-Origin: '.$from);
                    return;
                }
            } elseif ($cors['Access-Control-Allow-Credentials'] === 'true') {
                /*支持多域名和cookie认证,此时修改源*/
                $cors['Access-Control-Allow-Origin'] = $from;
            }
            foreach ($cors as $key => $value) {
                header($key.': '.$value);
            }
        }
    }
}
