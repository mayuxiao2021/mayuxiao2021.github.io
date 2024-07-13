<?php
session_start();//！！！这里注意

require("connection.php");

$id=$_POST["id"];
$password=$_POST["password"];
$user_password=md5($password);
$sql="select * from user_datas where idtop = '$id';";

$ret=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($ret);
if(($id==$row["idtop"])&&($user_password==$row["userpassword"])){
//当用户名和密码对上时，连接成功，并使用js语句跳转页面到留言的地方
    $_SESSION["id"]=$id;
    echo '<script language=javascript>window.location.href="留言页面.php"</script>';
}else{
    echo "登录失败";
}
mysqli_close($conn);
?>