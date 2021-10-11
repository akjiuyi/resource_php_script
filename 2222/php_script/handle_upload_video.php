<?php
    const BASE_PATH = "/data/";
    //const BASE_PATH = "/Users/mac/test/project16_github/P-station-Api/public";

    /*$servername = "localhost;dbname=gm_project01";
    $username = "root";
    $password = "";*/

    /*$servername = "45.9.110.34;dbname=P-station";
    $username = "root";
    $password = "z+3i5p.@9Q*3ghb}5B8h";*/

    $servername = "103.214.68.147;dbname=P-station";
    $username = "root";
    $password = "tgdgR}P@2dRJJMd=N8sJ";


    function handle_upload_video($conn){
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
                            $cover_pic_file_path = BASE_PATH."media/image/cover/".date("Ymd")."/".md5_file($source_video).".jpg";
                            $cover_pic_file_url = "/media/image/cover/".date("Ymd")."/".md5_file($source_video).".jpg";

                            $cover_pic_file_dir = BASE_PATH."media/image/cover/".date("Ymd");

                            if(!is_dir($cover_pic_file_dir)){
                                $res = exec("sudo mkdir -p ".$cover_pic_file_dir." && sudo chown nobody:nobody ".$cover_pic_file_dir);
                            }else{
                                $res = exec("sudo chown nobody:nobody ".$cover_pic_file_dir);
                            }

                            Video::getVideoCover($source_video,$cover_pic_file_path);
                            $total_duration = Video::getTime($source_video);
                            //print_r($total_duration);die;

                            //存入压片完毕状态  11
                            $data = [];
                            $data['cover_url'] = $cover_pic_file_url;
                            $data['total_duration'] = $total_duration['video_time'];

                            $res = update_video_cover_and_time($conn,$video['id'],$data);
                            if ($res) {
                                echo "handling of video file success\n";
                            } else {
                                echo "handling of video file failed\n";
                            }

                            sleep(1);
                        }
                    }catch (\Exception $e) {
                        //set_video_process_state($conn,$video['id'],12);  //压片失败状态
                        echo $e->getMessage()."\n";
                    }
                }
            }catch (\Exception $e) {
                echo $e->getMessage()."\n";
            }

            sleep(1);
            echo "wait for video file to process\n";
        }
    }


    //更新视频封面和时长
    function update_video_cover_and_time($conn,$id,$data){
        $sql = "UPDATE mzfk_video SET cover_url='{$data['cover_url']}',total_duration='{$data['total_duration']}' WHERE id=$id";
        return $conn->exec($sql);
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
        //$sql = "select id,m3u8_key from mzfk_video where process_state = 7 and m3u8_key <> '' and create_time>1629129600 and id = 10093";
        $sql = "select id,m3u8_key from mzfk_video where video_type = 1 and m3u8_key <> '' and cover_url = ''  and total_duration = '' and create_time>1630080000  limit 0,10";
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


    class Video
    {
        static function getVideoCover($file,$name,$time=1){
            //$str = "ffmpeg -i {$file} -y -f mjpeg -ss 3 -t {$time} {$name}";
            //        ffmpeg -i 1234.mov -vframes 1 11.jpg
            //        ffmpeg -i videoplayback.mp4 -y -f image2  -vframes 1 11.jpg

            $str = "ffmpeg -i {$file} -y -f image2 -vframes 1 $name";
            system($str);
        }

        static function getTime($file){
            $vtime = exec("ffmpeg -i ".$file." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//| cut -b 1-8");
            $ctime = date("Y-m-d H:i:s",filectime($file));

            return ['video_time' => $vtime,'create_time' => $ctime];
        }
    }


    $conn = db_connect($servername, $username, $password);

    handle_upload_video($conn);
?>
