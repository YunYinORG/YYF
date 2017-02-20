YYF (Yunyin Yaf Framework)
============================

基于PHP yaf扩展,构建的安全高性能RESTful后端框架。
--------------

YYF致力于提高生产环境下的运行性能和开发环境下的开发效率。
[核心库](library/)高效封装常用库和操作,兼容php5.3及以上,并对php7做最大优化。


## 链接

* [**YYF-BOOK** 开发文档 (更新中)](https://yyf.newfuture.cc/)
* [**稳定版演示**master分支(生产)](https://yyf.yunyin.org/)
* [**最新版演示**develop分支(开发)](http://yyf.yunyin.org/)
* [YYFJS前端JavaScript接口库](https://github.com/YunYinORG/YYFJS)
* [YYF-Debugger浏览器输出查看](http://debugger.newfuture.cc/)

## 安装使用

可以一键配置本地开发环境和部署服务器生成环境。
详细安装方法查看文档 [http://yyf.newfuture.cc/setup/](http://yyf.newfuture.cc/setup/)

快速安装:

1. 下载解压，或者clone仓库 `git clone https://github.com/YunYinORG/YYF.git` ;
2. 运行 `./init.cmd` (windows下双击即可) ;
3. 使用YYF虚拟机开发环境或者配置服务器环境 ;


## 目录结构
>
```
│  .htaccess    Apache开发环境和SAE重定向url
│  .travis.yml  travis-ci测试配置
│  init.cmd     开发环境初始化通用脚本 
│  LICENSE      Apache 2.0 许可证
│  README.MD
│  
├─app  
│  │
│  │  README.MD
│  │  
│  ├─controllers     控制器目录【添加代码的主战场】
│  │      Error.php  默认错误
│  │      Index.php  DEMO控制器
│  │      
│  ├─email           邮件模板目录
│  │      verify.tpl 默认验证邮件模板示例
│  │      
│  ├─models          数据模型目录
│  │      README.md
│  │      
│  └─views           视图目录
│      └─index
│              index.phtml
│              
├─conf      配置目录
│      app.ini            基础配置
│      secret.common.ini  示例私密配置
│      secret.product.ini 生产环境私密配置
│ 
├─library   库目录
│  │  Cache.php    缓存管理类
│  │  Config.php   配置读取类
│  │  Cookie.php   安全Cookie接口
│  │  Db.php       数据库操作封装
│  │  Debug.php    调试类
│  │  Encrypt.php  加密库
│  │  Input.php    输入过滤接口
│  │  Kv.php       key-value存取类
│  │  Logger.php   日志管理类
│  │  Mail.php     邮件发送
│  │  Model.php    基础model
│  │  Orm.php      ORM数据库对象映射
│  │  Random.php   随机字符生成类
│  │  README.md
│  │  Rest.php     基础REST类
│  │  Rsa.php      RSA加密类
│  │  Safe.php     安全统计类
│  │  Session.php  session管理接口
│  │  Validate.php 类型验证类
│  │  Wechat.php   微信登录接口库类
│  │
│  │
│  ├─Bootstrap 启动加载
│  │      dev.php       开发环境启动加载
│  │      product.php   生产环境启动加载
│  │
│  ├─Debug 调试相关库(开发环境)
│  │      Assertion.php 断言处理类
│  │      Header.php    header头输出类
│  │      Listener.php  日志监听类
│  │      Tracer.php    消耗统计类
│  │ 
│  ├─Parse 格式解析
│  │      Filter.php
│  │      Xml.php
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
│  ├─Storage 存储驱动
│  │      File.php      文件缓存类
│  │      
│  └─Test 单元测试库
│         YafTest.php   Yaf框架测试基类
│          
├─public 公共目录【前端资源目录，生产环境根目录】
│      .htaccess    url重写
│      favicon.ico
│      index.php    入口文件
│      robots.txt
│      
├─runtime 默认缓存日志临时文件夹【保证程序具有可读写权限】
│
│
└─tests   单元测试目录
```
>
