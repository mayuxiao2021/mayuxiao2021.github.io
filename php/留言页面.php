<?php
session_cache_limiter('private');
session_start();?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" type="text/css" href="css/留言页面.css" />
    <script src="js/上传头像.js"></script>
    <style>
        body {
            background-size: cover;
            background-image: url(image/1.jpg);
            background-repeat: no-repeat;
            background-position: center top;
            margin: 0%;
            background-attachment: fixed;
        }
        .btn{
 background-color:transparent; 
 border:0px;
}
    </style>  
    <script>
    function submit(){
        document.getElementById("search_form").submit();
    }
    </script>
</head>
<body>

    <div class="tx ovf">
        <div class="main"></div>
        <div><img src="image/十字架.jpg" alt="可更换头像" class="btn">
            <div class="upload-b"><span>修改头像</span><input type="file" class="upload1" name="file" id="file"></div>
        </div>
    </div><br><br>
    <h1?>欢迎你：</h1><?php if (!session_id()) session_start();echo $_SESSION["id"]; ?> <br>
    <div class="nav">
    <a href="javascript:submit();">查看留言</a>
    <div style="display:'none'">
    <form method="POST" action="浏览页面.php" id="search_form">       
        <input type="hidden" value=<?php if (!session_id()) session_start();echo $_SESSION["id"]; ?> name="id" id="id">  
    </form>
    </div>
        <a href="登录页面.php">退出登录</a>
    <form action="insert_messages.php" method="POST">
        <textarea name="content" cols="60" rows="9" id="content"></textarea>
        <input type="hidden" value=<?php if (!session_id()) session_start();echo $_SESSION["id"]; ?> name="id" id="id">
        <input type="submit" name="submit">
    </form>
    </div>
</body>

</html>
