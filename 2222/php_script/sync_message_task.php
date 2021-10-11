<?php
    const BASE_PATH = "/data/";
    //const BASE_PATH = "/Users/mac/test/project16_github/P-station-Api/public";

    /*$servername = "103.214.68.147;dbname=P-station";
    $username = "dev_user";
    $password = "QW}2UB97=^%XNPJBQJc!";*/

    $servername = "103.214.68.147;dbname=P-station";
    $username = "dev_user";
    $password = "akB3@yY4Ad3k.PyM4GLy";

    function task_execute($conn){

        //while (true) {
        try {
            //interactiveMsg($conn);
            sysNotice($conn);
        }catch (\Exception $e) {
            echo $e->getMessage()."\n";
        }

        //sleep(1);
        echo "process message complete\n";
        //}
    }


    //互动消息
    function interactiveMsg($conn)
    {
        $actors_count = get_actors_count($conn);
        $actors_count = $actors_count[0]['actor_count'];
        $page_size = 1000;
        $num = ceil($actors_count/$page_size);
        for ($i=0; $i<$num; $i++){
            $start = $i*$page_size;
            //查询演员关注表
            $actor_like = get_actors($conn,$start,$page_size);
            $yestoday_time = date('Y-m-d 00:00:00',strtotime("-1 day"));

            //查询演员和发布作品
            foreach ($actor_like as $v){
                $author = find_actor($conn,$v['actor_id']);
                $author = $author[0]['author']??'';

                $yestoday_timestamp = strtotime($yestoday_time);
                $con = "actor_id = {$v['actor_id']} and create_time>$yestoday_timestamp";
                $videos = get_video_info($conn,$con);

                foreach ($videos as $u){
                    $desc = "您关注的{$author}发布新作品{$u['title']},立即查看";
                    $jump_url = "video_id={$u['id']}";
                    $create_time = time();
                    $data = "(title,type,member_id,content,jump_url,create_time) values ('互动提示',1,{$v['member_id']},'$desc','$jump_url',$create_time)";

                    insert_message($conn,$data);

                    echo "insert a interactive message success\n";
                }
            }
        }
    }


    //系统通知
    function sysNotice($conn)
    {
        $three_days_time = date('Y-m-d 00:00:00',strtotime("+3 day"));
        $four_days_time = date('Y-m-d 00:00:00',strtotime("+4 day"));

        $three_days = date('Y-m-d',strtotime("+3 day"));

        //会员到期
        $member_ids = get_will_expire_members($conn,$four_days_time,$three_days_time);

        foreach ($member_ids as $v){

            $desc = "您的会员将于3天后,{$three_days}后到期,为了不影响使用,请即时续费";
            $jump_url = "";
            $create_time = time();
            $data = "(title,type,member_id,content,jump_url,create_time) values ('会员即将到期',4,{$v['id']},'$desc','$jump_url',$create_time)";

            insert_message($conn,$data);
            //Db::table('mzfk_member_message')->insert($data);

            echo "insert a system message success\n";
        }

        //关注订阅到期
        $likes = get_like_expire_actors($conn,$four_days_time,$three_days_time);

        foreach ($likes as $v){
            $author = find_actor($conn,$v['actor_id']);
            $author = $author[0]['author']??'';

            $desc = "您关注的演员{$author}将于3天后,{$three_days}号到期,为了不影响使用,请即时续费";
            $jump_url = "";
            $create_time = time();
            $data = "(title,type,member_id,content,jump_url,create_time)values('关注订阅即将到期',4,{$v['member_id']},'$desc','$jump_url',$create_time)";

            insert_message($conn,$data);
            //Db::table('mzfk_member_message')->insert($data);

            echo "insert a system message success\n";
        }
    }


    //查询
    function query($conn,$table,$where="",$field="*",$order="",$page=0,$page_size=10){
        $sql = "select $field from $table where 1=1 and $where order by $order limit $page,$page_size";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //获取关注演员总数
    function get_actors_count($conn){
        $sql = "select count(*) as actor_count from mzfk_actor_like";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //获取关注演员
    function get_actors($conn,$start,$page_size){
        $sql = "select member_id,actor_id from mzfk_actor_like limit $start,$page_size";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //查询演员
    function find_actor($conn,$actor_id){
        $sql = "select author from mzfk_mv_actors where id=$actor_id";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //查询视频信息
    function get_video_info($conn,$where){
        $sql = "select id,title from mzfk_video where $where";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }

    //INSERT INTO runoob_tbl (runoob_title, runoob_author, submission_date)VALUES("学习 PHP", "菜鸟教程", NOW());

    //插入消息
    function insert_message($conn,$data){
        $sql = "insert into mzfk_member_message $data";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //获取3天到期会员
    function get_will_expire_members($conn,$four_days_time,$three_days_time){
        $four_days_time = strtotime($four_days_time);
        $three_days_time = strtotime($three_days_time);
        $sql = "select id from mzfk_member where vip_expired < $four_days_time and vip_expired > $three_days_time";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //获取关注到期演员
    function get_like_expire_actors($conn,$four_days_time,$three_days_time){
        $four_days_time = strtotime($four_days_time);
        $three_days_time = strtotime($three_days_time);
        $sql = "select member_id,actor_id from mzfk_actor_like where expire_time < $four_days_time and expire_time > $three_days_time";

        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    function db_connect($servername,$username,$password){
        try {
            $conn = new PDO("mysql:host=$servername", $username, $password);
            echo "连接成功\n";
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();die;
        }

        return $conn;
    }


    function close_db_conn($conn){
        $conn = null;
    }

    $conn = db_connect($servername, $username, $password);
    task_execute($conn);
    close_db_conn($conn);
?>
