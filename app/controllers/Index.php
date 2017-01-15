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
    /**
     * Demo 首页
     * 使用phtml 模板渲染
     */
    public function indexAction()
    {
        Yaf_Dispatcher::getInstance()->enableView();
        $url = $this->_request->getBaseUri();
        $this->getView()
            ->assign('version', Config::get('version'))
            ->assign('url', $url);
    }

    /**
     * GET /Index/test?data=''
     * GET请求测试
     *
     * success() 和 fail() 快速返回示例
     */
    public function GET_testAction()
    {
        if (Input::get('data', $data)) {//get参数中含data
            //success快速返回成功信息
            $this->success($data);
        } else {//未输入data参数
            //fail快速返回出错信息
            $this->fail('please send request data with field name "data"');
        }
    }

    /**
     * POST /Index/test
     * POST请求测试
     *
     * response()函数自定义状态
     */
    public function POST_testAction()
    {
        if (Input::post('data', $data)) {
            // response() 指定状态为1 等同于success
            $this->response(1, $data);
        } else {
            //错误码为0，并指定http 状态码为400
            $this->response(0, 'please POST data with field name "data"', 400);
        }
    }

    /**
     * GET /Index/:id
     * 参数id映射示例
     *
     * @param int $id 自动绑定输入参数
     *
     * response自定义返回数据示例
     */
    public function GET_infoAction($id = 0)
    {
        //response 即为返回内容
        $this->response = array(
            'id'     => $id,
            'action' => 'GET_infoAction',
            'params' => $_REQUEST,
        );
    }
}
