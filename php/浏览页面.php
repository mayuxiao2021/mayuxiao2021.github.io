<?php
session_cache_limiter('private');
session_start();
require("connection.php");

$id=$_POST["id"];
$sql="SELECT * FROM user_messages WHERE idtop = '$id';";
//查找相同用户名的内容，再全部打印出来
$retval=mysqli_query($conn,$sql);

while($res=mysqli_fetch_array($retval)){
        echo "<table><tr><td>{$res['bbs']}</td></tr></table>";
}

mysqli_free_result($retval);
mysqli_close($conn);

?>
<a href=" 留言页面.php ">返回留言</a>
