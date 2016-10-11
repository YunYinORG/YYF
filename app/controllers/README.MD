controller目录
========

## REST操作映射

* GET操作  ==> GET_Action
* POST操作 ==> POST_Action
* PUT操作  ==> PUT_Action
* DELETE操作 ==> DELETE_Action
* 如果没有定义对应前缀的Action回调用默认的不带前缀Action
* 对于数字(id)请求，会映射到一个`info`的Action,并绑定参数id


## `Test.php`示例代码

```php
<?php
/**
app/controller/Test.php
*/

class TestController extends Rest
{
	/**
	 * 处理GET /Test/
	 */
	function GET_indexAction()
	{
		$this->response=['method'=>'get'];
	}

	/**
	 * 处理POST /Test/
	 */
	function POST_indexAction()
	{
		$this->response['data']=$this->_request->getPost();
	}


	/**
	 * 处理 /Test/123
	 */
	function GET_infoAction($id)
	{
		$this->response(1,$id);
	}
}
```