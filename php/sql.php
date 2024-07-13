<?php
require("connection.php");

if(!$conn){
    die("连接失败”：".mysqli_error($conn));
}

$table="user_datas";//用户注册信息
$sql="CREATE TABLE $table(
    id INT(50) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idtop VARCHAR(50) NOT NULL,
    userpassword VARCHAR(50) NOT NULL,
    email VARCHAR(50),
    phone VARCHAR(11), 
    reg_date TIMESTAMP
)";

if(mysqli_query($conn,$sql)){
    echo "Table $table created successfully".PHP_EOL;
}else{
    echo "Table $table created faile";
}

$table="user_messages";//用户留言内容

$sql="CREATE TABLE $table(
    id INT(50) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idtop VARCHAR(50) NOT NULL,
    bbs VARCHAR(200),
    reg_date TIMESTAMP
)";

if(mysqli_query($conn,$sql)){
    echo "Table $table created successfully";
}else{
    echo "Table $table created faile";
}

mysqli_close($conn);
?>