 Model 库
================

核心FacadeModel
-----------------
基本的Facde接口，对model封装

实例
--------------

新建`UserModel`对应`User.php`
定义如下
```php 
 class UserModel extends FacadeModel
 {
 //未定义$pk,默认使用'id'
 //未定义$table,默认使用表名为类名去掉Model
 }
```

静态调用如下
```php 
# find 快速查找
# 返回的是包含查找数据的Model可以继续使用
#find($id = null, $value = null)
$user=UserModel::find(1);	//返回id为1的用户
$user=UserModel::find('phone','13888888888');	//返回phone为1388888的一个用户

# select
#批量查询select($data)
$list=UserModel::slecet('id,name,time'); //列出所有用户的id和name

# where
# 条件查询
UserModel::where('id','=',1)->find();//查找id=1的一个用户
UserModel::where('id','>','1')->where('status','>',0)->select('id,name');//查询id>1的个用户的id和name
# orWhere
#OR 条件查询
UserModel::where('id','=','1')->orWhere('status','>',0)->select('id,name');//查询id>1的个用户的id和name

# insert
# 插入数据
# insert($data=[])
$id = UserModel::insert(['name'=>'测试','pwd'=>'123']);//插入一个新用户
# add
# 保存插入数据
$id = UserModel::set(['pwd'=>'mypwd','name'=>'test'])->add();//添加新用户
$id = UserModel::set('name','测试')->set('pwd',123')->add();//插入一个新用户

# update
# 更新数据
UserModel::where('id',1')->update(['pwd'=>'1234']);//更新数据
# save
UserModel::set('pwd','1234')->save(1);//更新数据

# delete
#删除数据
UserModel::delete(1);//删除id为1的用户

# page($page,$n)翻页操作
# order($field,'')//排序
UserModel::page(2,5)->order('id','DESC')->select();

# 其他操作
# count统计
# sum求和
# avg均值
# min最小值
# max最大值
# inc增加自段值
```
也可以实例化操作 
`$user=new UserModel`

也可以使用Model直接实例化未定义的模型
`$info=new Model('info')`

其使用方法和以上相同