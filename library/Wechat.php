<?php
use Logger as Log;
/**
 * 微信API接口封装【包括JSSDK，微信认证和登录】
 * 在secret配置中[wechat]配置两个key即可
 * @example
 *生成跳转URl
 * $url = Wechat::getAuthUrl();//生成微信认证跳转url
 * $url = Wechat::getAuthUrl('base');//对于关注微信号用户静默跳转链接
 * $url = Wechat::getAuthUrl('login');//网页登录URl
 *前端微信js配置
 * $config = Wechat::signJs('http://yyf.yunyin.org/Wechat/');//生成JS签名
 * $config = Wechat::loginConfig();//生成微信登录配置，供网页端配合微信登录js使用
 *后端微信验证
 * $userinfo = Wechat::getUserInfo();//跳转后获取用户信息
 * $openid = Wechat::checkCode();//静默认证获取openid
 *设置获取state [主要防止CSRF】
 * 默认根据state的配置自动生成和验证，无需干预
 * 自定义state ： Wechat::state('your state code')->getAuthUrl('baseinfo');
 * 关闭state验证： Wechat::state(FALSE)->checkCode();
 */
class Wechat
{

	private static $_config;          //微信配置
	private static $_state    = null; //自定义state
	private static $_instance = null; //自身引用

	/**
	 * @method signJs
	 * 对JS的URL进行签名
	 * @param  [string]  $url          [需要签名的URL]
	 * @return [array]  [签名后配置的数组,可json序列化返回客户端]
	 * @author NewFuture
	 */
	public static function signJs($url = false)
	{
		$url = $url ?: getenv('HTTP_ORIGIN') ?: getenv('HTTP_REFERER');
		if (!$url)
		{
			throw new Exception('[wechat] JS 签名缺少参数url ');
		}
		if ($jsapiTicket = self::_getJsApiTicket())
		{
			$timestamp = $_SERVER['REQUEST_TIME'];
			$nonceStr  = Random::word(16);
			// 这里参数的顺序要按照 key 值 ASCII 码升序排序
			$signature = 'jsapi_ticket=' . $jsapiTicket . '&noncestr=' . $nonceStr . '&timestamp=' . $timestamp . '&url=' . $url;
			$signature = sha1($signature);
			return array(
				'appId'     => self::_getConfig('appid'),
				'timestamp' => $timestamp,
				'nonceStr'  => $nonceStr,
				'signature' => $signature,
			);
		}
	}

	/**
	 * @method loginConfig
	 * 获取登录配置
	 *  供JS中调用时使用
	 * @param  [type]  $redirect [description]
	 * @param  boolean $state    [description]
	 * @return [type]            [description]
	 */
	public static function loginConfig($redirect = null)
	{
		$config = array(
			'appid'        => self::_getConfig('appid'),
			'scope'        => 'snsapi_login',
			'redirect_uri' => $redirect ?: self::_getConfig('redirect_login'),
		);
		if ($state = self::_createState())
		{
			$config['state'] = $state;
		}
		return $config;
	}

	/**
	 * @method getAuthUrl
	 * [生成微信认证重定向URL]
	 * 	获取授权URL
	 * $scope = base (不弹出授权页面，直接跳转，只能获取用户openid）
	 * $scope = userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地）
	 * $scope = login (网页端登录)
	 * @param  string  $scope [授权信息：默认为userinfo]
	 * 	@param  string  $redirect   [回调验证URL:默认读取配置]
	 * @return [string]       [重定向URL]
	 */
	public static function getAuthUrl($scope = 'userinfo', $redirect = null)
	{
		assert('in_array($scope,array("base","userinfo","login"))', 'Wechat::getAuthUrl方法$scope 参数不在设置范围内');

		$url = ('login' === $scope) ? 'https://open.weixin.qq.com/connect/qrconnect' : 'https://open.weixin.qq.com/connect/oauth2/authorize';

		$redirect = $redirect ? urlencode($redirect) : urlencode(self::_getConfig('redirect_' . $scope));
		$scope    = 'snsapi_' . $scope;
		$state    = self::_createState();
		$state    = $state ? '&state=' . $state : '';
		return $url . '?appid=' . self::_getConfig('appid') . '&redirect_uri=' . $redirect . '&response_type=code&scope=' . $scope . $state . '#wechat_redirect';
	}

	/**
	 * @method checkCode
	 * [验证授权码，返回验证信息]
	 * 一般用于配合snsapi_base 静默授权
	 * @param  string $code [code 默取$_GET参数]
	 * @param  string $key  [制定默认openid]
	 * @return [false 获取失败] [string ,制定的参数如openid] [array 全部返回数据]
	 */
	public static function checkCode($key = 'openid', $code = false)
	{
		if (!($code || Input::get('code', $code)))
		{
			Log::write('[wechat] 微信认证缺少 code 参数');
			return false;
		}
		if (!self::_checkState())
		{
			//自动验证state失败
			Log::write('[wechat] state check fialed','WARN');
			return false;
		}
		$url  = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . self::_getConfig('appid') . '&secret=' . self::_getConfig('secret') . '&code=' . $code . '&grant_type=authorization_code';
		$res  = self::_httpGet($url);
		$data = json_decode($res, true);
		if (!($data && isset($data['access_token'])))
		{
			Log::write('[wechat] access_token failed :' . $res,'CRITICAL');
			return false;
		}
		return $key ? $data[$key] : $data;
	}

	/**
	 * @method state
	 * [设置或者获取state]
	 * 无参数时获取Wechat::state()
	 * 设置state Wechat::state('statestring')
	 * @return [state 或者 wechat]
	 *  state（）无参数时,返回当前的state
	 *  state($setting) 有参数时，返回wechat实体
	 */
	public static function state()
	{
		if (func_num_args() > 0)
		{
			self::$_state = func_get_arg(0);
			if (null === self::$_instance)
			{
				self::$_instance = new self;
			}
			return self::$_instance;
		}
		else
		{
			return self::$_state;
		}
	}

	/**
	 * 获取用户信息
	 * @param  [type] $code [回调code,默认取$_GET参数]
	 * @return [false] 失败
	 *         [array]
	 *   {openid,nickname,headimgurl,sex,language,city,province,country,privilege}
	 */
	public static function getUserInfo($code = false)
	{
		if ($data = self::checkCode(false, $code))
		{
			//get_user_info_url
			$url  = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $data['access_token'] . '&openid=' . $data['openid'];
			$data = json_decode(self::_httpGet($url), true);
			return isset($data['errcode']) ? false : $data;
		}
	}

	private function _construct()
	{
	}

	/**
	 * @method _getConfig
	 * 获取微信配置
	 * @param  配置Key    $key          [description]
	 * @return [type]    [description]
	 * @author NewFuture
	 */
	private static function _getConfig($key)
	{
		if (!self::$_config)
		{
			self::$_config = Config::getSecret('wechat');
		}
		return $key ? self::$_config[$key] : self::$_config;
	}

	/**
	 * 生成唯一的验证state并保存到制定的存储位置
	 * @return [string] [生成的state]
	 */
	private static function _createState()
	{
		$state = self::$_state;
		if ((null === $state) && $type = self::_getConfig('state'))
		{
			//随机生产唯一的state标记
			$state = uniqid(Random::char(5), true);
			//保存state
			$type = ucfirst(strtolower($type));
			$type::set('_yyf_wechat_state', $state);
			self::$_state = $state;
		}
		return $state;
	}

	/**
	 * 验证state是否有效
	 * @return [type] [description]
	 */
	private static function _checkState()
	{
		$state = self::$_state;
		if ((null === $state) && ($Type = self::_getConfig('state')))
		{
			//默认使用配置
			$Type = ucfirst(strtolower($Type));
			if ($state = $Type::get('_yyf_wechat_state'))
			{
				$Type::del('_yyf_wechat_state');
			}
			else
			{
				Log::write('[wechat] 无法获取state数据' . $Type,'WARN');
				return FALSE;
			}
		}
		if ($state)
		{
			if (Input::get('state', $get_state))
			{
				return $get_state === $state; //判断两个state是否一致
			}
		}
		else
		{
			return TRUE; //不用验证
		}
	}

	/**
	 * @method _getJsApiTicket
	 * 获取JS API ticket
	 * @return [string]         [js ticket]
	 * @author NewFuture
	 */
	private static function _getJsApiTicket()
	{
		$ticket = Cache::get('wechat_api_jsticket');
		if (!$ticket)
		{
			$token = Cache::get('wechat_api_accesstoken');
			if (!$token)
			{
				/*获取access_token*/
				$url  = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . self::_getConfig('appid') . '&secret=' . self::_getConfig('secret');
				$res  = self::_httpGet($url);
				$data = json_decode($res, true);
				if ($data && isset($data['access_token']))
				{
					$token = $data['access_token'];
					Cache::set('wechat_api_accesstoken', $token, 6000);
				}
				else
				{
					throw new Exception('[wechat] get JS API access_token Failed: ' . $res, 1);
					return;
				}
			}

			$url  = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=' . $token;
			$res  = self::_httpGet($url);
			$data = json_decode($res, true);
			if ($data && isset($data['ticket']))
			{
				$ticket = $data['ticket'];
				Cache::set('wechat_api_jsticket', $ticket, 6000);
			}
			else
			{
				throw new Exception('[wechat] get JS TICKET Failed:' . $res, 1);
			}
		}
		return $ticket;
	}

	/**
	 * @method _httpGet
	 * https请求
	 * @param  [string] $url [description]
	 * @return [string]      [返回的数据]
	 */
	private static function _httpGet($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_URL, $url);
		$res = curl_exec($curl);
		curl_close($curl);
		return $res;
	}
}