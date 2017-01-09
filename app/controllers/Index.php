<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

/**
 * Demo 示例
 */
class IndexController extends Rest
{
    /*demo*/
    public function indexAction()
    {
        Yaf_Dispatcher::getInstance()->enableView();
        $url = $this->_request->getBaseUri();
        $this->getView()->assign('version', Config::get('version'))->assign('url', $url);
    }

    /**
     * GET /index/test
     *
     * @method GET_testAction
     */
    public function GET_testAction()
    {
        Input::get('data', $data);//获取GET参数
        $this->response = array('data' => $data, 'method' => 'GET');
    }

    /**
     * POST /index/test
     *
     * @method POST_testAction
     */
    public function POST_testAction()
    {
        Input::post('data', $data);
        $this->response = array('data' => $data, 'method' => 'POST');
    }

    /**
     * GET /index/:id
     *
     * @method GET_infoAction
     *
     * @param mixed $id
     */
    public function GET_infoAction($id = 0)
    {
        $this->response = array('id' => $id, 'action' => 'GET_infoAction');
    }
}
