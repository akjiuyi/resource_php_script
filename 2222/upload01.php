<?php
//set_time_limit(0);
@set_time_limit(120*60);
header('Access-Control-Allow-Origin:*');

include_once('libaray/getid3/getid3.php');

const FILE_BASE_PATH = "/data/";
const URL_BASE_PATH = "/media/";


const TEMP_PATH = "/data/upload_tmp";   //分片上传临时目录
const CLEANUP_TEMP_DIR = true;           //是否清除零时文件
const MAX_FILE_AGE = 5 * 3600;           //零时文件最大保存时间


const UPLOAD_SALT = 'unique_salt';

verify_param($_POST);

if(isset($_POST['upload_type'])){
    $type = $_POST['upload_type'];   //1、视频封面 2、预览视频 3、短视频  4、长视频  5、用户头像 6、演员头像 7、广告 8、社群 9、配置 10、视频分类 11、视频标签
}else{
    $type = $_POST['type'];          //1、视频封面 2、预览视频 3、短视频  4、长视频  5、用户头像 6、演员头像 7、广告 8、社群 9、配置 10、视频分类 11、视频标签
}

// 允许上传的图片和视频后缀
//mp4,flv,f4v,webm,m4v,mov,3gp,3g2,rm,rmvb,wmv,avi,asf,mpg,mpeg,mpe,ts,div,dv,divx,vob,dat,mkv,lavf,cpk,dirac,ram,qt,fli,flc,mod
//$allowedExts = array("gif", "jpeg", "jpg", "bmp", "png", "mp4","mov","wmv","flv","avi","mkv");
$allowedExts = array("gif", "jpeg", "jpg", "bmp", "png", "mp4","flv","f4v","webm","m4v","mov","3gp","3g2","rm","rmvb","wmv","avi","asf","mpg","mpeg","mpe","ts","div","dv","divx","vob","dat","mkv","lavf","cpk","dirac","ram","qt","fli","flc","mod");
$temp = explode(".", $_FILES["file"]["name"]);

$extension = strtolower(end($temp));     // 获取文件后缀名

$file_size = $_FILES["file"]["size"]/(1024*1024);  //文件大小 M

if($file_size > 4096){
    success([''], "文件大小超过限制", 201);
}

if (!in_array($extension, $allowedExts)) {
    success([''], "非法的文件格式", 201);
}

try{
    if ($_FILES["file"]["error"] > 0) {
        //echo "错误：: " . $_FILES["file"]["error"] . "<br>";
        success([''], "错误：: " . $_FILES["file"]["error"], 201);
    } else {
        /*
        /data/media/image/cover/ 封面图文件夹
        /data/media/image/avatar/actor/演员头像
        /data/media/image/avatar/user/用户头像
        /data/media/video/short_video/ 短视频文件夹
        /data/media/video/long_video/长视频文件夹
        /data/media/image/advert/ 广告
        /data/media/image/social_group/ 社群
        /data/media/image/config/ 配置
        /data/media/image/class/ 视频分类
        /data/media/image/tag/ 视频标签
        */

        /*$playtime = '';
        if($type == 2||$type == 3||$type == 4) {
            $getID3 = new getID3;
            $playtime = get_video_time($getID3, $_FILES["file"]["tmp_name"]);
        }*/

        $getID3 = new getID3;

        $upload_dir = "";
        $url_path = "";
        if($type == 1){
            $upload_dir = FILE_BASE_PATH."media/image/cover/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/cover/".date("Ymd")."/";
        }else if($type == 2){
            success([''], "暂无开放", 201);
        }else if($type == 3){
            $upload_dir = FILE_BASE_PATH."media/video/short_video/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."video/short_video/".date("Ymd")."/";

            //去分片上传
            slice_upload($upload_dir,$url_path,$extension,$getID3);
        }else if($type == 4){
            $upload_dir = FILE_BASE_PATH."media/video/long_video/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."video/long_video/".date("Ymd")."/";

            //去分片上传
            slice_upload($upload_dir,$url_path,$extension,$getID3);
        }else if($type == 5){
            $upload_dir = FILE_BASE_PATH."media/image/avatar/user/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/avatar/user/".date("Ymd")."/";
        }else if($type == 6){
            $upload_dir = FILE_BASE_PATH."media/image/avatar/actor/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/avatar/actor/".date("Ymd")."/";
        }else if($type == 7){
            $upload_dir = FILE_BASE_PATH."media/image/advert/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/advert/".date("Ymd")."/";
        }else if($type == 8){
            $upload_dir = FILE_BASE_PATH."media/image/social_group/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/social_group/".date("Ymd")."/";
        }else if($type == 9){
            $upload_dir = FILE_BASE_PATH."media/image/config/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/config/".date("Ymd")."/";
        }else if($type == 10){
            $upload_dir = FILE_BASE_PATH."media/image/class/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/class/".date("Ymd")."/";
        }else if($type == 11){
            $upload_dir = FILE_BASE_PATH."media/image/tag/".date("Ymd")."/";
            $url_path = URL_BASE_PATH."image/tag/".date("Ymd")."/";
        }

        $upload_file = $upload_dir.md5_file($_FILES["file"]["tmp_name"]).".".$extension;
        $url_file_address = $url_path.md5_file($_FILES["file"]["tmp_name"]).".".$extension;

        $res = "";
        if(!is_dir($upload_dir)){
            $res = exec("sudo mkdir -p ".$upload_dir." && sudo chown nobody:nobody ".$upload_dir);
        }else{
            $res = exec("sudo chown nobody:nobody ".$upload_dir);
        }

        // 如果 upload 目录不存在该文件则将文件上传到 upload 目录下
        $res = move_uploaded_file($_FILES["file"]["tmp_name"], $upload_file);
        if($res == true){
            //echo "文件存储在: ".BASE_PATH.$_FILES["file"]["name"];
            success(['file_path'=>$url_file_address, 'total_duration'=>$playtime], "文件上传成功", 200);
        }else{
            success([''], "上传文件失败", 201);
        }


        //存数据
    }
    //}
}catch(Exception $e){
    success([''], $e->getMessage(), 201);
}




//返回
function success( $data = [], $msg = 'success', $code = 200){
    echo json_encode(['code' => $code,'msg' => $msg,'data' => $data]);die;
    //return json_encode(["hh","ew23"]);
}


//视频时长
function get_video_time($getID3,$video){
    try{
        $file = $getID3->analyze($video);

        if($file&&isset($file['playtime_string'])){
            $playtime_arr = explode(':',$file['playtime_string']);
            $time_count = count($playtime_arr);
            if($time_count == 1){
                $sec = $playtime_arr[0];
                if($playtime_arr[0]<10){
                    $sec = "0{$playtime_arr[0]}";
                }
                $playtime_string = "00:00:{$sec}";
            }elseif ($time_count == 2){
                $minute = $playtime_arr[0];
                if($playtime_arr[0]<10){
                    $minute = "0{$playtime_arr[0]}";
                }

                $sec = $playtime_arr[1];
                if($playtime_arr[1]<10){
                    $sec = "0{$playtime_arr[1]}";
                }

                $playtime_string = "00:$minute:$sec";
            }elseif ($time_count == 3){
                $hour = $playtime_arr[0];
                if($playtime_arr[0]<10){
                    $hour = "0{$playtime_arr[0]}";
                }

                $minute = $playtime_arr[1];
                if($playtime_arr[1]<10){
                    $minute = "0{$playtime_arr[1]}";
                }

                $sec = $playtime_arr[2];
                if($playtime_arr[2]<10){
                    $sec = "0{$playtime_arr[2]}";
                }

                $playtime_string = "$hour:$minute:$sec";
            }else{
                $playtime_string = "00:00:00";
            }
        }else{
            $playtime_string = "00:00:00";
        }

        return $playtime_string;
    }catch(Exception $e){
        //success([''], $e->getMessage(), 201);
        return "00:00:00";
    }
}


//请求验证
function verify_param($post){
    try{
        if(!isset($post['token'])||!$post['token']){
            success([''], "参数错误", 201);
        }

        if(!isset($post['timestamp'])||!$post['timestamp']){
            success([''], "参数错误", 201);
        }

        if(!isset($post['type'])||!$post['type']){
            success([''], "参数错误", 201);
        }


        $verifyToken = md5(UPLOAD_SALT.$post['timestamp']);
        if($verifyToken != $post['token']){
            success([''], "token参数错误", 201);
        }
    }catch(Exception $e){
        success([''], $e->getMessage(), 201);
    }
}


//分片上传
function slice_upload($upload_dir,$url_path,$extension,$getid3){
    try{
        // 创建上传目录
        if(!is_dir($upload_dir)){
            exec("sudo mkdir -p ".$upload_dir." && sudo chown nobody:nobody ".$upload_dir);
        }else{
            exec("sudo chown nobody:nobody ".$upload_dir);
        }

        // 创建临时目录
        if(!is_dir(TEMP_PATH)){
            exec("sudo mkdir -p ".TEMP_PATH." && sudo chown nobody:nobody ".TEMP_PATH);
        }else{
            exec("sudo chown nobody:nobody ".TEMP_PATH);
        }

        //获取文件名
        $fileName = $_FILES["file"]["name"];

        $filePath = TEMP_PATH . DIRECTORY_SEPARATOR . $fileName;
        //$uploadPath = $upload_dir . DIRECTORY_SEPARATOR . $fileName;

        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;


        // Remove old temp files  删除旧文件
        if (CLEANUP_TEMP_DIR) {
            if (!is_dir(TEMP_PATH) || !$dir = opendir(TEMP_PATH)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = TEMP_PATH . DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - MAX_FILE_AGE)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }


        // Open temp file  写入分片临时文件
        /*if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            //success([''], "打开输出文件失败", 201);
        }*/

        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            //exec("sudo mkdir -p ".TEMP_PATH);
            exec("sudo touch {$filePath}_{$chunk}.parttmp && sudo chown nobody:nobody {$filePath}_{$chunk}.parttmp");
            $out = @fopen("{$filePath}_{$chunk}.parttmp", "wb");
            //success([''], "打开输出文件失败", 201);
        }


        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                //die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
                success([''], "上传文件失败", 201);
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                //die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                success([''], "打开输入文件失败", 201);
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                //die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                success([''], "打开输入流失败", 201);
            }
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        //临时分片转分片文件
        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");
        //echo "{$filePath}_{$chunk}.parttmp";
        //$dd = file_exists("{$filePath}_{$chunk}.parttmp");
        //var_dump($dd);die;

        //合并
        $done = true;
        for( $index = 0; $index < $chunks; $index++ ) {
            if ( !file_exists("{$filePath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }

        if ( $done ) {
            $upload_file = $upload_dir.md5_file($_FILES["file"]["tmp_name"]).".".$extension;
            $url_file_address = $url_path.md5_file($_FILES["file"]["tmp_name"]).".".$extension;

            if (!$out = @fopen($upload_file, "wb")) {
                //die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                success([], "输出文件打开失败", 201);
            }

            if ( flock($out, LOCK_EX) ) {
                for( $index = 0; $index < $chunks; $index++ ) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }

                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }

                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }

                flock($out, LOCK_UN);
            }
            @fclose($out);

            //die('{"jsonrpc" : "2.0", "result" : {"code": 200, "data"：{"file_path":"'.$url_file_address.'","total_duration":"'.$playtime.'"}, "message": "文件上传成功"}, "id" : "id"}');
            $playtime = get_video_time($getid3, $upload_file);
            success(['file_path'=>$url_file_address, 'total_duration'=>$playtime], "文件上传成功", 200);
        }else{
            //die('{"jsonrpc" : "2.0", "result" : {"code": 200,"data"："",, "message": "文件上传失败"}, "id" : "id"}');
            success([''], "上传文件失败", 201);
        }

    }catch(Exception $e){
        success([], $e->getMessage(), 201);
    }
}

?>
