<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/登陆页面.css"/>
    <title>Document</title>
<style>
    body {
    background-size: cover;
    background-image: url(image/index.jpeg);
    background-repeat: no-repeat;
    background-position: center top;
    margin: 0%;
    background-attachment: fixed;
}
</style>
</head>
<body>
    <div class="post1">
        <form method="POST" action="denglu.php">
            <input type="text" maxlength="11" placeholder="--请输入账号--" name="id" id="id"><br>
            <span class="error" id="iderr" ></span><br>
            <input type=" password " maxlength="16 " placeholder="--请输入密码-- " name="password"><br>
            <span class="error" id="password" name="password" ></span><br>
            <input type="submit" name="submit" value="登录" style="height:30px;width:50px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href=" 注册页面.php ">注册</a>
        </form>
    </div>
</body>

</html>