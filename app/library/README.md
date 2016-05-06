YYF核心库
========

* Auth.php : 登录信息验证类 (Auth)
* Cache.php : 缓存管理类(Cache)
* Config.php : 配置读取类(Config)
* Cookie.php : 安全Cookie操作类(Cookie)
* Encrypt.php : 安全加密类(Encrypt)
* Input.php : 输入管理类(Input)
* Kv.php : 键值对存储类(Kv)
* Log.php : 日志管理类(Log)
* Mail.php : 邮件管理类(Mail)
* Model.php : 核心model类(Model)
* Random.php : 随机数生成器(Random)
* Rest.php : REST核心controller类(Rest)
* Rsa.php : Rsa 加解密类(Rsa)
* Session.php : session操作管理(Session)
* Validate.php : 格式验证(Validate)

Auth
------
登录验证接口
```php
Auth::id()      #获取用户id
Auth::number()  #获取学号
Auth::getUser() #获取用户信息
Auth::token(mixed $user|$id) #生成token
```

Cache
------
缓存管理接口
本地缓存采用文件存储
SAE采用memcache
```php
Cache::set($name, $value, $expire = false) #设置缓存
Cache::get($name)   #读取缓存
Cache::del($name)   #删除缓存
Cache::flush()      #清空缓存
```

Config
------
配置读取与管理
```php
Config::get($name) #快速获取配置支持多级操作
Config::getSecret($name,$key=null) #获取私有配置项
```

Cookie
------
安全Cookie管理
```php
Cookie::set($name, $value, $path='/',$expire = false) #设置cookie
Cookie::get($name)   #读取cookie
Cookie::del($name)   #删除cookie
Cookie::flush()      #清空cookie
```

Encrypt
-----
加密库
```php
/*基础编码和加密*/
Encrypt::base64Encode($str) #路径安全的Base64编码
Encrypt::base64Decode($str) #安全base64解码
Encrypt::aesEncode($data, $key, $safe_view = false)#AES加密
Encrypt::aesDecode($data, $key, $safe_view = false)#AES解密
/*格式保留加密方法*/
Encrypt::encryptEmail($email) #加密邮箱(密码从配置中读取)
Encrypt::decryptEmail($email) #解密邮箱
Encrypt::encryptPhone($phone,$salt,$offset) #手机号加密
Encrypt::decryptPhone($phone,$salt,$offset) #手机号解密
```

Input
-----
输入过滤库

* 返回true(输入存在且有效)或者false,
* 输入结果存在$export中
* $filter为参数格式验证或者过滤方法支持：正则表达式，系统函数，php的filter_var常量,自定义的验证过滤函数
```php
Input::post($name, &$export, $filter = null, $default = null)
Input::get($name, &$export, $filter = null, $default = null)
Input::put($name, &$export, $filter = null, $default = null)
Input::I($name, &$export, $filter = null, $default = null)
#其中I包含以上三种方式支持cookie和env，$name未指定方法时读取$_RESUQET
```

Kv
------
键值对存储类
本地缓存暂时采用文件存储
支持字符串
SAE采用memcache
```php
Cache::set($name, $str) #设置
Cache::get($name)       #读取
Cache::del($name)       #删除
Cache::flush()          #清空
```

Log
-------
日志记录类
$tag 日志标签,配置中开启的则记录
```
Log::write($msg, $tag = 'INFO')
```

Mail
---------
邮件发送类

```php
Mail::send($from, $to, $msg) #发送邮件
Mail::sendVerify($email, $name, $link)#发送验证邮件
Mail::sendNotify($email, $name, $body)#发送通知邮件
```


Model
--------
安全数据库操作
未非静态封装(这样比静态化封装的效率要高)
在**FadeModel**则进行静态化处理
```php
$User = new Model('user');#创建model参数表名和主键
$User->find(123);#查找id为123的用户
$User->set('time',time())->save();#保存
$User->Insert(['name'=>'test']);新建用户

$Book = new Model('book');    #创建book
$Book->where('amount','>',10) #选择amount>10
     ->order('amount','DESC') #amount倒序
     ->select('id AS NO,name,amount');  #选出id作为NO,name和account
$Book->where('amount',0)->delete(); #删除
```

Random
-------
快速随机数生成器
```php
Random::n($n = 4)  #生成随机number[0-9]
Random::w($n = 8)  #随机word[0-9|a-Z]
Random::c($n=10)   #生成随机char[a-Z]
Random::code($n=6) #随机验证码验证码,去除0，1等不易辨识字符
```

Rest
-------
REST控制器核心基类

* 自动把GET,POST,PUT,DELETE 映射到 对应的Action 如get detail 映射到GET_detailAction()
* 自动绑定参数id
* 自动输出xml或者json格式数据

`private $response_type='json'` 数据格式json和xml
`protected $response `响应的数据
`protected response(status,info)`快速设置响应方法

Rsa
-------
Rsa 非对称加密库
```
Rsa::pubKey()   #获取公钥
Rsa::encode($s) #加密
Rsa::decode($s) #解密
```


Session
--------
Session操作管理
支持数组
```php
Session::set($name, $data) #设置
Session::get($name)        #读取
Session::del($name)        #删除
Session::flush()           #清空
```