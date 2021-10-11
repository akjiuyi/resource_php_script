<?php
    $servername = "103.214.68.147;dbname=P-station";
    $username = "dev_user";
    $password = "akB3@yY4Ad3k.PyM4GLy";

    try {
        $conn = new PDO("mysql:host=$servername", $username, $password);
        echo "database connect success";
    }
    catch(PDOException $e)
    {
        echo $e->getMessage();die;
    }

    //获取每天免费次数
    //$result = $conn->query("select s_value from mzfk_system_config where s_key='system.free_time'");
    $result = $conn->query("select s_value from mzfk_system_config where s_key='system.free_time'");
    $data = $result->fetch();

    //更新每日免费次数
    try {
        $conn->exec("UPDATE mzfk_member SET daily_video_times=".$data[0]);
    }
    catch(PDOException $e)
    {
        echo $e->getMessage();
        $conn = null;
        die;
    }


    $conn = null;
?>
