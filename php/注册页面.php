<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/注册页面.css" />
    <title>Document</title>
    <script>
    function checkName() {
    var inputNode = document.getElementById("id");
    var spanNode = document.getElementById("iderr");
    var content = inputNode.value;
    var reg = /^[0-9]*$/;//注意这里无双引号，但是这里可把我眼睛给看瞎去
    if (content == "") {
        spanNode.innerHTML = "账号不能为空".fontcolor("red");
        return false;
    }
    if ((content.length) < 5) {
        spanNode.innerHTML = "账号至少为5位".fontcolor("red");
        return false;
    } else {
        spanNode.innerHTML = "正确".fontcolor("green");
        return true;
    }
}

function checkpassword() {
    var password = document.getElementById("password");
    var content = password.value;
    var spanNode = document.getElementById("passworderr");
    var reg = /^[0-9a-zA-Z]*$/;
    if ((password.length) < 5) {
        spanNode.innerHTML = "密码至少为5位".fontcolor("red");
        return false;
    }
    if (!reg.test(content)) {
        spanNode.innerHTML = "只允许字母和数字".fontcolor("red");
        return false;
    }
    if (content != "") {
        spanNode.innerHTML = "已填".fontcolor("green");
        return true;
    } else {
        spanNode.innerHTML = "密码不能为空".fontcolor("red");
        return false;
    }
}

function checkemail() {
    var email = document.getElementById("email");
    var spanNode = document.getElementById("emailerr");
    var content = email.value;
    var reg = /^[a-z0-9]\w+@[a-z0-9]+(\.[a-z]{2,3}){1,2}$/i;
    if (reg.test(content)) {
        spanNode.innerHTML = "正确".fontcolor("green");
        return true;
    } else {
        spanNode.innerHTML = "邮箱格式不正确".fontcolor("red");
        return false;
    }
}

function checkphone() {
    var phone = document.getElementById("phone");
    var spanNode = document.getElementById("phoneerr");
    var content = phone.value;
    var reg = /^[0-9]/;
    if (!(phone.length) == 11) {
        spanNode.innerHTML = "手机号码格式不正确".fontcolor("red");
        return false;
    }
    if (!reg.test(content)) {
        spanNode.innerHTML = "手机号码格式不正确".fontcolor("red");
        return false;
    } else {
        spanNode.innerHTML = "已填".fontcolor("green");
        return true;
    }
}

function checkForm() {
    var add_id = checkName();
    var add_pw = checkpassword();
    var add_email = checkemail();
    var add_phone = checkphone();
    if (add_email && add_id && add_phone && add_pw) {
        return true;
    } else {
        return false;
    }
}
    </script>
    <style>
        body {
            background-size: cover;
            background-image: url(image/350.jpg);
            background-repeat: no-repeat;
            background-position: center top;
            margin: 0%;
            background-attachment: fixed;
        }
    </style>
</head>

<body>
    <div class="post1">
        <form method="POST" action="zhuce.php" onsubmit="return checkForm()">
            账号： <input type="text" maxlength="11" placeholder="--请输入账号--" name="id" id="id"> <span>*</span><br>
            <span id="iderr" class="error"> </span><br> 密码：
            <input type=" password " maxlength="16 " placeholder="--请输入密码-- " name="password" id="password"> <span>*</span><br>
            <span id="passworderr" class="error"></span><br> 邮箱：
            <input type="text" placeholder="--请输入邮箱地址--" name="email" id="email"><br>
            <span id="emailerr" class="error"></span><br> 电话：
            <input type="text" placeholder="--请输入电话号码--" name="phone" id="phone"> <span>*</span><br>
            <span id="phoneerr" class="error"></span><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="确认注册">
        </form>
    </div>
</body>
</html>