<?php
    require("connection.php");
    header("content-type:text/html;charset=utf-8");

$id=$_POST["id"];
$password=$_POST["password"];
$email=$_POST["email"];
$phone=$_POST["phone"];
$user_password=md5($password);
$select_sql="SELECT * FROM user_datas WHERE idtop ='$id'";
$ret=mysqli_query($conn,$select_sql);
$row=mysqli_fetch_array($ret);//这里是将数据表同样赋值为数组形式，如果赋值为字符串形式就会报错
if(!empty($row["idtop"]) && ($row["idtop"]==$id)){
    mysqli_free_result($ret);
    echo "<script type='text/javascript'>";
	echo "alert('此账号已经有人使用');";
	echo "history.back();";
	echo "</script>";
}else{
    $sql="INSERT INTO user_datas(idtop,userpassword,email,phone) VALUES ('$id','$user_password','$email','$phone')";//当用户名不存在时插入数据
    mysqli_query($conn,$sql);
    echo '<script language=javascript>window.location.href="登录页面.php"</script>';
    }
mysqli_close($conn);
?>
<h1>注册成功</h1><br>
<a href="登录页面.php">返回登录页面</a>