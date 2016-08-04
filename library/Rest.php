 <?php
/**
 * REST 控制器
 */
abstract class Rest extends Yaf_Controller_Abstract
{
	// private $response_type = 'json'; //返回数据格式

	protected $response = false; //自动返回数据

	/**
	 * @method corsHeader
	 * CORS 跨域请求响应头处理
	 * @author NewFuture
	 */
	public static function corsHeader()
	{
		$from = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);

		if ($from)
		{
			$cors    = Config::get('cors');
			$domains = $cors['Access-Control-Allow-Origin'];
			if ($domains !== '*')
			{
				$domain = strtok($domains, ',');
				while ($domain)
				{
					if (strpos($from, rtrim($domain, '/')) === 0)
					{
						$cors['Access-Control-Allow-Origin'] = $domain;
						break;
					}
					$domain = strtok(',');
				}
				if (!$domain)
				{
					/*非请指定的求来源,自动终止响应*/
					header('Forbid-Origin:' . $from);
					// exit;
					return;
				}
			}
			elseif ($cors['Access-Control-Allow-Credentials'] === 'true')
			{
				/*支持多域名和cookie认证,此时修改源*/
				$cors['Access-Control-Allow-Origin'] = $from;
			}
			foreach ($cors as $key => $value)
			{
				header($key . ':' . $value);
			}
		}
	}

	/**
	 * 初始化 REST 路由
	 * 修改操作 和 绑定参数
	 * @method init
	 * @author NewFuture
	 */
	protected function init()
	{
		Yaf_Dispatcher::getInstance()->disableView(); //立即输出响应，并关闭视图模板引擎
		$request = $this->_request;

		/*请求来源，跨站cors响应*/
		if (Config::get('cors.Access-Control-Allow-Origin'))
		{
			self::corsHeader();
		}

		/*请求操作判断*/
		$method = $request->getMethod();
		$type   = $request->getServer('CONTENT_TYPE');
		if ($method === 'OPTIONS')
		{
			/*cors 跨域header应答,只需响应头即可*/
			exit();
		}
		elseif (strpos($type, 'application/json') === 0)
		{
			/*json 数据格式*/
			if ($inputs = file_get_contents('php://input'))
			{
				$input_data = json_decode($inputs, true);
				if ($input_data)
				{
					$GLOBALS['_' . $method] = $input_data;
				}
				else
				{
					parse_str($inputs, $GLOBALS['_' . $method]);
				}
			}
		}
		elseif ($method === 'PUT' && ($inputs = file_get_contents('php://input')))
		{
			/*直接解析*/
			parse_str($inputs, $GLOBALS['_PUT']);
		}

		/*Action路由*/
		$action = $request->getActionName();
		if (is_numeric($action))
		{
			/*数字id绑定参数*/
			$request->setParam(Config::get('application.num_param'), intval($action));
			//$_SERVER['PATH_INFO']不存在使用REQUEST_URI
			$path   = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : strstr($_SERVER['REQUEST_URI'] . '?', '?', true);
			$path   = substr(strstr($path, $action), strlen($action) + 1);
			$action = $path ? strstr($path . '/', '/', true) : Config::get('application.num_action');
		}

		$rest_action = $method . '_' . $action; //对应REST_Action

		/*检查该action操作是否存在，存在则修改为REST接口*/
		if (method_exists($this, $rest_action . 'Action'))
		{
			/*存在对应的操作*/
			$request->setActionName($rest_action);
		}
		elseif (!method_exists($this, $action . 'Action'))
		{
			/*action和REST_action 都不存在*/
			$this->response(-8, array(
				'error'      => '未定义操作',
				'method'     => $method,
				'action'     => $action,
				'controller' => $request->getControllerName(),
				'module'     => $request->getmoduleName(),
			));
			exit;
		}
		elseif ($action !== $request->getActionName())
		{
			/*绑定参数默认控制器*/
			$request->setActionName($action);
		}
	}

	/**
	 * 设置返回信息，立即返回
	 * @method response
	 * @param  [type]   $status [请求结果]
	 * @param  string   $info   [请求信息]
	 * @return [type]           [description]
	 * @author NewFuture
	 */
	protected function response($status, $info = '')
	{
		$this->response = ['status' => $status, 'info' => $info];
		exit;
	}

	/**
	 * 结束时自动输出信息
	 * @method __destruct
	 * @access private
	 * @author NewFuture
	 */
	public function __destruct()
	{
		if ($this->response !== false)
		{
			header('Content-Type: application/json; charset=utf-8');
			echo (json_encode($this->response, JSON_UNESCAPED_UNICODE)); //unicode不转码
		}
	}
}