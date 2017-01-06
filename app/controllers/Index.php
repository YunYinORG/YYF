<?php
/**
 * YYF默认的demo.
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
     * GET /index/test.
     *
     * @method GET_testAction
     */
    public function GET_testAction()
    {
        Input::get('data', $data); //获取GET参数
        $this->response = ['data' => $data, 'method' => 'GET'];
    }

    /**
     * POST /index/test.
     *
     * @method POST_testAction
     */
    public function POST_testAction()
    {
        Input::post('data', $data);
        $this->response = ['data' => $data, 'method' => 'POST'];
    }

    /**
     * GET /index/:id.
     *
     * @method GET_infoAction
     */
    public function GET_infoAction($id = 0)
    {
        $this->response = ['id' => $id, 'action' => 'GET_infoAction'];
    }
}
