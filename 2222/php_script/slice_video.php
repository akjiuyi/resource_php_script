<?php
    const BASE_PATH = "/data/";
    //const BASE_PATH = "/Users/mac/test/project16_github/P-station-Api/public";

    /*$servername = "localhost;dbname=gm_project01";
    $username = "root";
    $password = "";*/

    $servername = "45.9.110.34;dbname=P-station";
    $username = "root";
    $password = "z+3i5p.@9Q*3ghb}5B8h";


    function handle_video_watermark($conn){
        //$watermark_pic = get_watermark_pic($conn);
        //$watermark_file = BASE_PATH.$watermark_pic;

        while (true) {
            try {
                $origin_videos = get_origin_video($conn);
                foreach ($origin_videos as $video) {
                    try {
                        $source_video = BASE_PATH.$video['m3u8_key'];

                        //判断文件是否存在
                        if(is_file($source_video)){
                            /*//存入压片状态  10
                            set_video_process_state($conn,$video['id'],10);

                            //检查是否水印视频
                            preg_match("/(.+)_watermark\.(mp4|mov|wmv|flv|avi|mkv)$/i", $video['m3u8_key'], $matches);
                            if(isset($matches[1])&&$matches[1]){
                                echo "video file have watermark,continue\n";
                                continue;
                            }

                            //水印
                            preg_match("/(.+)\.(mp4|mov|wmv|flv|avi|mkv)$/i", $video['m3u8_key'], $matches);

                            if(isset($matches[1])&&$matches[1]){
                                $watermark_video_file = BASE_PATH."/".$matches[1]."_watermark.".$matches[2];
                                $watermark_relative_path = $matches[1]."_watermark.".$matches[2];
                            }else{
                                echo "video file match fail,continue\n";
                                continue;
                            }

                            $cmd = "ffmpeg -i $source_video -vf \"movie=$watermark_file, scale=50:50, lut=a=val*0.4[watermark];[in][watermark] overlay=enable='mod(t,60)':x='(main_w/50)*mod(t,60)-w:y=main_h/5'\" $watermark_video_file";
                            system($cmd);*/

                            //匹配mp4文件
                            preg_match("/(.+)\.(mp4)$/i", $video['m3u8_key'], $matches);
                            if(isset($matches[1])&&$matches[1]){
                                $ts_files     = BASE_PATH."$matches[1]/video_%04d.ts";
                                $m3u8_file    = BASE_PATH."$matches[1]/video.m3u8";
                                $m3u8_relative_file = "$matches[1]/video.m3u8";
                            }else{
                                echo "mp4 video file match fail,continue\n";
                                continue;
                            }

                            /*print_r($ts_files);
                            print_r("\n");
                            print_r($m3u8_file);
                            print_r("\n");
                            print_r($m3u8_relative_file);

                            die;*/

                            //存入切片状态  4
                            set_video_process_state($conn,$video['id'],4);

                            //$key_file     = "$storage_dir/key.keyinfo";

                            $cmd = "sudo mkdir -p ".BASE_PATH.$matches[1]." && sudo chown nobody:nobody ".BASE_PATH.$matches[1];
                            system($cmd);

                            //$cmd = "ffmpeg -y -i $source_video -c:v libx264 -c:a copy -f hls -hls_time 60   -hls_segment_filename $ts_files $m3u8_file";
                            //ffmpeg -i input.mp4 -c copy -hls_time 20 out\playlist.m3u8
                            $cmd = "ffmpeg -i $source_video -c copy -hls_list_size 0 -hls_time 60  $m3u8_file";
                            system($cmd);

                            //存入切片完毕状态  5
                            $data = [];
                            $data['state'] = 5;
                            $data['m3u8_key'] = $m3u8_relative_file;

                            $res = update_video_process_data($conn,$video['id'],$data);
                            if ($res) {
                                echo "slicing of video file process success\n";
                            } else {
                                echo "slicing of video file process failed\n";
                            }

                            sleep(5);
                        }
                    }catch (\Exception $e) {
                        //set_video_process_state($conn,$video['id'],103);  //切片失败状态
                        echo $e->getMessage()."\n";
                    }
                }
            }catch (\Exception $e) {
                echo $e->getMessage()."\n";
            }

            sleep(5);
            echo "wait for processing watermark of video file \n";
        }
    }


    //更新视频信息
    function update_video_process_data($conn,$id,$data){
        $sql = "UPDATE mzfk_video SET process_state={$data['state']},m3u8_key='{$data['m3u8_key']}' WHERE id=$id";
        return $conn->exec($sql);
    }


    //更改视频状态
    function set_video_process_state($conn,$id,$state){
        $sql = "UPDATE mzfk_video SET process_state=$state WHERE id=$id";
        return $conn->exec($sql);
    }


    //获取待处理视频
    function get_origin_video($conn){
        $sql = "select id,m3u8_key from mzfk_video where video_type=2 and process_state = 7 and m3u8_key <> '' and create_time>1629993600";
        $result = $conn->query($sql);
        return $result->fetchAll();
    }


    //获取水印图片
    function get_watermark_pic($conn){
        $sql = "select s_value from mzfk_system_config where s_key = 'system.watermark_pic'";
        $result = $conn->query($sql);
        $data = $result->fetch();

        if(isset($data['s_value'])){
            return $data['s_value'];
        }else{
            return "";
        }
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


    $conn = db_connect($servername, $username, $password);

    handle_video_watermark($conn);

?>
