YYF
====================
Yunyin Yaf Framework

云印YAF(yet another framework)框架
------
基于PHP的YAF扩展构建的**高效**,**安全**,**简单**,**优雅**的框架

借鉴内流行的ThinkPHP框架和国际流行的Laravel框架的使用习惯

兼顾程序性能和代码优雅

## [核心库](https://github.com/YunYinORG/YYF/tree/master/app/library)


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

(详细使用说明)[https://github.com/YunYinORG/YYF/tree/master/app/models]

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
function GET_indexAction(){$this->response=['method'=>'get'];}//处理GET
function POST_indexAction(){$this->response['data']=$this->_request->getPost();}//处理POST
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