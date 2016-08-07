YYF 2.3
====================
Yunyin Yaf Framework

云印Yaf(yet another framework)框架
------

开发文档[yyf-book(更新中)](http://yyf.newfuture.cc/)

基于PHP的YAF扩展构建的**高效**,**安全**,**简单**,**优雅**的框架

借鉴内流行的ThinkPHP框架和国际流行的Laravel框架的使用习惯

兼顾程序性能和代码优雅

## 目录结构
>
```
│  .htaccess    Apache开发环境重新url
│  config.yaml  SAE配置和URL重定向
|  init.cmd     开发环境初始化脚本 
│  LICENSE
│  README.MD
|  server.cmd   启动php server 调试 
│  
├─app  
│  │  Bootstrap.php     生产环境入口 
│  │  Bootstrap.dev.php 开发环境入口
│  │  README.MD
│  │  
│  ├─controllers     控制器目录【添加代码的主战场】
│  │      Error.php  默认错误
│  │      Index.php  DEMO控制器
│  │      
│  ├─email           邮件模板目录
│  │      verify.tpl 默认验证邮件模板示例
│  │      
│  ├─models          模型目录
│  │      README.md
│  │      
│  ├─plugins         插件目录
│  │      Tracer.php 调试信息统计插件
│  │      
│  └─views           视图目录
│      └─index
│              index.phtml
│              
├─conf      配置目录
│      app.ini       基础配置
│      secret.common.ini  示例私密配置
│      secret.product.ini 生产环境私密配置
│ 
├─library   库目录
│  │  Cache.php    缓存管理类
│  │  Config.php   配置读取类
│  │  Cookie.php   安全Cookie接口
|  |  Db.php       数据库操作封装
│  │  Encrypt.php  加密库
│  │  Head.php     调试header输出库
│  │  Input.php    输入过滤接口
│  │  Kv.php       key-value存取类
│  │  Log.php      日志管理类
│  │  Mail.php     邮件发送
│  │  Model.php    基础model
|  |  Orm.php      ORM数据库对象映射
│  │  Random.php   随机字符生成类
│  │  README.md
│  │  Rest.php     基础REST类
│  │  Rsa.php      RSA加密类
│  │  Safe.php     安全统计类
│  │  Session.php  session管理接口
│  │  Validate.php 类型验证类
│  │  Wechat.php   微信登录接口库类
│  │  
│  ├─Parse 格式解析
│  │      Filter.php
│  │      Xml.php
│  │      
│  │          
│  ├─Service 系统基础服务
│  │      Api.php
│  │      Database.php
│  │      Message.php
│  │      Qiniu.php
│  │      README.MD
│  │      Smtp.php
│  │      Ucpaas.php
│  │      
│  └─Storage 存储服务
│          File.php
│          
├─public 公共目录【前端资源目录，生产环境根目录】
│      .htaccess     url重写
│      favicon.ico
│      index.php    入口文件
│      robots.txt
│      
└─temp 缓存日志临时文件夹【可写权限】
```
>
            

## [核心库](library/)


数据库
-------
安全PDO预处理查询,从最底层防止注入

Model 简单高效的数据库模型 如
```php
$User =new Model('User');
$User->where('id','<',10)->selecet('name AS user');
```
FacadeModel 提供优雅方便的查询接口
```php
UserModel::where('name','LIKE','%future%')->count();//静态化调用
$Info=InfoModel::find(1);//查找id为1的
$Info->set('time',time())->save();
```

[详细使用说明](app/models)

REST控制器
-----
* 对 get,put,post,delete等路由到特定控制器处理
* 绑定ID参数,
* 支持json和xml数据输出

实例
在controllers下建一个 Test.php
```php
class TestController extends Rest
{
	function GET_indexAction(){
		$this->response=['method'=>'get'];
	}//处理GET
	function POST_indexAction(){
		$this->response['data']=$this->_request->getPost();
	}//处理POST
}
```

GET 请求测试
`curl -X GET localhost/test/`  响应 `{'metod':'get'}`

POST 请求测试
`curl -X POST -d "key=value" localhost/test/` 响应 `{"data":{"key":"value"}}` 

数据存取
------
* Cache   缓存管理
* Cookie  安全Cookie管理(数据加密，防js获取)
* Kv      键值对存取
* Session 封装Session接口

```php
#写
Cookie::set('user',['id'=>1,'name'=>'YYF']);
#读
Cache::get('key')
#删
Session::del('key')
#清
Kv::flush()
```

加密
------
* AES 对称加密
* RSA 非对称加密
* phone和email格式保留加密解密

常用扩展
-------
* 支持YAF的所有接口
* Input  输入过滤扩展
* Random 随机数生成库
* Log    日志管理

其他服务
-----
* Mail  简单邮件发送
* Debug PHPConsole调试插件
* Qiniu 七牛文件管理

开发环境
-----

[查看快速配置](https://github.com/YunYinORG/YYF/wiki#%E7%8E%AF%E5%A2%83%E9%85%8D%E7%BD%AE)

为团队开发和服务器配置提高解决方案