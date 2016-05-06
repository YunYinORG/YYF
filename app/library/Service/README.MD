# 第三服务API接口

### 文件上传
Flie.php 七牛文件管理接口
```php
Qiniu::set($file,$new_file) #文件重命名
Qiniu::get($name,$param='') #获取下载链接
Qiniu::del($file)           #删除文件
Qiniu::getToken($name)      #获取上传token
```

### 数据库
Db.php 数据库PDO封装的对象

### 邮件
Smtp.php 邮件链接类
Message.php 邮件消息
