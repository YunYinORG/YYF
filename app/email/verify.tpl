<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>云印服务验证邮件</title>
</head>

<body bgcolor="#f6f6f6" style="-webkit-font-smoothing:antialiased; -webkit-text-size-adjust:none; height:100%; width:100%" height="100%" width="100%">
<table bgcolor="#f6f6f6" style="padding:20px; width:100%" width="100%">
	<tr>
		<td></td>
		<td bgcolor="#FFFFFF" style="border:1px solid #f0f0f0; clear:both; display:block; margin:0 auto; max-width:600px; padding:20px">
			<div style="display:block; margin:0 auto; max-width:600px">
			<table style="width:100%" width="100%">
				<tr>
					<td>
						<h3 style='color:#000; font-family:"Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; font-size:22px; font-weight:200; line-height:1.2; margin:40px 0 10px'>亲爱的<?=$name?></h3>
						<h3 style='color:#000; font-family:"Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; font-size:22px; font-weight:200; line-height:1.2; margin:40px 0 10px'>这是来自云印南天yunyin.org绑定邮箱的验证邮件</h3>
						<h3 style='color:#000; font-family:"Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; font-size:22px; font-weight:200; line-height:1.2; margin:40px 0 10px'>请确定这是您的个人操作,将绑定您的邮箱<?=$email?></h3>
						<h3 style='color:#000; font-family:"Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; font-size:22px; font-weight:200; line-height:1.2; margin:40px 0 10px'>输入验证码<code><?=$code?></code>或者点击一下链接完成验证</h3>
						<table style="width:100%" width="100%">
							<tr>
								<td style="padding:10px 0">
									<p style="font-size:14px; font-weight:normal; margin-bottom:10px"><a href="<?=$url?>" style="background-color:#348eda; border:solid #348eda; border-radius:15px; border-width:10px 20px; color:#FFF; cursor:pointer; display:inline-block; font-weight:bold; line-height:2; margin-right:10px; text-align:center; text-decoration:none" bgcolor="#348eda" align="center">点击以验证邮箱</a></p>
								</td>
							</tr>
						</table>
						<h3 style='color:#000; font-family:"Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; font-size:22px; font-weight:200; line-height:1.2; margin:10px 0 10px'>如果无法正常打开请复制<code><?=$url?></code>到浏览器</h3>
						<br>
						<p style="font-size:14px; font-weight:normal; margin-bottom:10px">关注新浪微博<a href="http://weibo.com/cloudPrint" style="color:#348eda">@云印南天</a></p>
						<p style="font-size:14px; font-weight:normal; margin-bottom:10px">关注项目最新进度<a href="https://github.com/YunYinORG/YunYinService" style="color:#348eda">Github</a></p>
					</td>
				</tr>
			</table>
			</div>
		</td>
		<td></td>
	</tr>
</table>

<table style="clear:both; width:100%" width="100%">
	<tr>
		<td></td>
		<td style="clear:both; display:block; margin:0 auto; max-width:600px">
			<div style="display:block; margin:0 auto; max-width:600px">
				<table style="width:100%" width="100%">
					<tr>
						<td align="center">
							<p style="color:#666; font-size:12px; font-weight:normal; margin-bottom:10px">Copyright©2014-2015云印南天</p>
						</td>
					</tr>
				</table>
			</div>
		</td>
		<td></td>
	</tr>
</table>

</body>
</html>
