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


    //读取上架短视频列表
    $result = $conn->query("select id from mzfk_video where video_type=1 and process_state=8 and id>4213");
    $video_list = $result->fetchAll();
    //print_r($data);die;

    //更新每日免费次数
    try {
        foreach ($video_list as $v){
            $result = $conn->query("select id from mzfk_recommend_video where video_id={$v['id']}");
            $recommend = $result->fetch();
            if(!$recommend){
                $now = time();
                //print_r("INSERT INTO mzfk_recommend_video (video_id, is_recommend, create_time) VALUES ('{$v['id']}',1,{$now})");die;
                $conn->exec("INSERT INTO mzfk_recommend_video (video_id, is_recommend, create_time) VALUES ('{$v['id']}',1,{$now})");
            }
        }

    }catch(PDOException $e){
        echo $e->getMessage();
        $conn = null;
        die;
    }

    echo "task2 execute finish";

    $conn = null;
?>
