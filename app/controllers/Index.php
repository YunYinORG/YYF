<?php
/**
 * YYF默认的demo
 */
class IndexController extends Rest
{

	/*demo*/
	public function indexAction()
	{
		Yaf_Dispatcher::getInstance()->enableView();
		$url=$this->_request->getBaseUri();
		$this->getView()->assign('version', Config::get('version'))->assign('url',$url);
	}

	public function confAction()
	{
		$config=parse_ini_file(APP_PATH.'/conf/app.ini',true);
		// var_dump($config);
		$env=Yaf_Application::app()->environ();
		echo $env;
		$current=$config['common']+$config[$env.':common'];
	
		// var_dump($current);
		foreach($current as $k=>$v){
			if(Config::get($k)===$v)
			{
				echo 1;
			}else{
				var_dump($k);
				var_dump($v);
				var_dump(Config::get($k));
				$o= Yaf_Application::app()->getConfig()->get($k);
				var_dump(is_object($o));
				var_dump($o);
			}
			// echo Config::get($k)===$v?1:0;
		}
		// var_dump(Yaf_Application::app()->getConfig()->get('ssss'));
		// var_dump(Config::get()==$current);
	}

	/**
	 * GET /index/test
	 * @method GET_testAction
	 */
	public function GET_testAction()
	{
		Input::get('data', $data);//获取GET参数
		$this->response = ['data' => $data, 'method' => 'GET'];
	}

	/**
	 * POST /index/test
	 * @method POST_testAction
	 */
	public function POST_testAction()
	{
		Input::post('data', $data);
		$this->response = ['data' => $data, 'method' => 'POST'];
	}

	/**
	 * GET /index/:id
	 * @method GET_infoAction
	 */
	public function GET_infoAction($id=0)
	{
		$this->response = ['id' => $id, 'action' => 'GET_infoAction'];
	}
}
?>
