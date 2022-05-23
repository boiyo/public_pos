<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>系統登入畫面</title>

</head>
<body>
<center>
<form action="login" method="post">
	帳號：<input name="UserName" type="text"><br>
	密碼：<input autocomplete="new-password" name="PassWord" type="password"><br>
	<input type="submit" value="登入">
	<input type="hidden" name="_token" value="{{ csrf_token() }}">
</form>
	@if(isset($msg))
	<p style="color:red;">{{$msg}}</p>
	@endif
</center>
</body>
</html>
