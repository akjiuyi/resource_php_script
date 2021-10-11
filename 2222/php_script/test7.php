<?php

    const BASE_PATH = "/data/media/video/short_video/";

    $video_count = 0;

    function scan_dir_to_update_db($path){
        global $video_count;
        if(is_dir($path)){
            $dir =  scandir($path);

            foreach ($dir as $value){
                $sub_path = $path .'/'.$value;
                if($value == '.' || $value == '..'){
                    continue;
                }else if(is_dir($sub_path)){
                    //echo '目录名:'.$value .'\n';
                    scan_dir_to_update_db($sub_path);
                }else{
                    //.$path 可以省略，直接输出文件名
                    //echo ' 最底层文件: '.$path. '/'.$value.' <hr/>';


                    preg_match("/(.+)\.(mp4|mov|wmv|flv|avi|mkv)$/i", $sub_path, $matches);
                    print_r($sub_path);
                    print_r($matches);
                    echo "\n";
                    if(isset($matches[0])){
                        print_r($matches);die;
                        $video_count++;
                    }

                }
            }
        }
    }


    function db_connect($servername,$username,$password){

        try {
            $conn = new PDO("mysql:host=$servername", $username, $password);
            echo "db connect success\n";
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();die;
        }

        return $conn;
    }


    function close_db_conn(&$conn){
        $conn = null;
    }


    //查询文件在数据库存在吗
    function queryFileExist($file){
        global $conn;

        //查询视频表 封面和视频文件
        //$file_url_path = str_replace(BASE_PATH,"",$file);
        echo "match $file\n";
        preg_match("/[A-Za-z0-9_]{20,40}.[A-Za-z0-9]{2,6}$/i", $file, $matches);
        if(isset($matches[0])){
            $file_url_path = $matches[0];
        }else{
            preg_match("/[A-Za-z0-9_]{4,40}\/video.m3u8$/i", $file, $matches);
            if(isset($matches[0])){
                $file_url_path = $matches[0];
            }else{
                //return 1; //未匹配的文件
                preg_match("/[A-Za-z0-9_]{4,40}\/video_\d{3,5}.ts$/i", $file, $matches);
                if(isset($matches[0])){
                    //$file_url_path = $matches[0];
                    return 1;   //ts文件
                }else{
                    return 4;   //其它文件
                }

            }

        }

        /*preg_match("/[A-Za-z0-9_]{4,40}/video_\d{3,5}.ts$/i", $file, $matches);
        $file_url_path = $matches[0];*/

        $sql = "select * from mzfk_video where m3u8_key like '%$file_url_path' or cover_url like '%$file_url_path' or origin_local_url like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();
        if($data){
            return 2;
        }

        //查询用户表 头像文件
        $sql = "select * from mzfk_member where avatar like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        //查询演员表 头像文件
        $sql = "select * from mzfk_mv_actors where avatar like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        //查询广告表 封面的url
        $sql = "select * from mzfk_app_advertisement where cover_url like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }


        //查询社群表 群图标文件
        $sql = "select * from mzfk_contact_groups where icon like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        //查询配置表 分享海报底图文件
        $sql = "select * from mzfk_system_config where s_key='system.share_pic' and s_value like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        //查询标签表 分类标签 普通标签 图标、标签背景图、配图
        $sql = "select * from mzfk_tags where pic like '%$file_url_path' or bg_pic like '%$file_url_path' or match_pic like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        //查询配置表 iso下载二维码 文件
        $sql = "select * from mzfk_system_config where s_key='system.app_download_ios' and s_value like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        //查询配置表 android下载二维码 文件
        $sql = "select * from mzfk_system_config where s_key='system.app_download_android' and s_value like '%$file_url_path'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if($data){
            return 2;
        }

        return 3;
    }

    $path = BASE_PATH;

    $servername = "45.9.110.34;dbname=P-station";
    $username = "root";
    $password = "z+3i5p.@9Q*3ghb}5B8h";

    $conn = db_connect($servername, $username, $password);

    //while (1){
        scan_dir_to_update_db($path);
        //sleep(10);
    //}
        echo $video_count;
    $conn = null;
?>
