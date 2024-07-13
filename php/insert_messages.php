<?php
session_cache_limiter('private');
/*这里需要注意一下，指定会话页面所使用的缓冲控制方法：
当session_cache_limiter('private')时，用处是让表单history.go(-1)的时候，填写内容不丢失！就避免页面失效的警告！
所以如果不加上这句代码内容将丢失，$_POST['id']不知道是什么*/
session_start();

require("connection.php");
$content=$_POST["content"];
$id=$_POST["id"];

$sql="INSERT INTO user_messages(idtop,bbs) VALUES ('$id','$content')";
mysqli_query($conn,$sql);
mysqli_close($conn);
echo "<script language=javasceipt>alert('添加成功')</script>";
echo '<script language=javascript>window.location.href="留言页面.php"</script>';
?>
