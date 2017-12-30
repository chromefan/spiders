<?php
/**
 * Created by PhpStorm.
 * User: luohuanjun
 * Date: 2017/6/9
 * Time: 下午3:16
 */
namespace App\Libs;



class Http{
    /**
    +----------------------------------------------------------
     * Ajax方式返回数据到客户端
    +----------------------------------------------------------
     * @access protected
    +----------------------------------------------------------
     * @param mixed $data 要返回的数据
     * @param String $info 提示信息
     * @param boolean $status 返回状态
     * @param String $status ajax返回类型 JSON XML
    +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    public static function ajaxReturn($data=array(),$info='',$status=1,$type='JSON') {
        $result  =  array();
        $result['status']  =  $status;
        $result['msg'] =  $info;
        $result['data'] = $data;

        // 返回JSON数据格式到客户端 包含状态信息
        return (response()->json($result));
    }
    /**
    +----------------------------------------------------------
     * 操作错误跳转的快捷方法
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @param string $message 错误信息
    +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    public static function error($message='error',$data=array()) {
        return self::ajaxReturn($data,$message,'-1');
    }

    /**
    +----------------------------------------------------------
     * 操作成功跳转的快捷方法
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @param string $message 提示信息
    +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    public static function success($message='ok',$data=array()) {

        return self::ajaxReturn($data,$message,'1');
    }
    public static function curl_post($url, $data = array(), $header = array()) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if($header)
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/34.0.1847.116 Chrome/34.0.1847.116');
        $result = curl_exec($ch);

        if($no = curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return $error;
        }
        curl_close($ch);
        return $result;
    }
    public static function curl_get($url,$header='',$proxy){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36");
        if($header){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }else{
            curl_setopt($ch, CURLOPT_HEADER,0);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    public static function obj_array($obj){
        $json = json_encode($obj);
        $arr = json_decode($json,true);
        return $arr;
    }

    /**
     * 二维数组排序
     * @param $array
     * @param $field
     * @param bool $desc
     * @return mixed
     */
    public static function sortArrByOneField($array, $field, $desc = false){
        $fieldArr = array();
        foreach ($array as $k => $v) {
            $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
        return $array;
    }
    /**
     *----------------------------------------------
     * 递归创建目录函数
     *----------------------------------------------
     * @param $path 路径，比如 "aa/bb/cc/dd/ee"
     * @param $mode 权限值，php中要用0开头，比如0777,0755
     * @author luohj
     * @return $string  生成的路径
     */

    static function recursiveMkdir($path, $mode = 0777) {
        if (! file_exists ( $path )) {
            self::recursiveMkdir ( dirname ( $path ), $mode );
            @mkdir ( $path, $mode );
        }
        return $path;
    }
    /**
     * 获取zip文件路径
     * @param $id
     * @return string
     */
    public static function getMd5Dir($id){
        $md5_value = md5($id);
        $file_path1 = substr($md5_value,-2,2);
        $file_path2 = substr($md5_value,-4,2);
        $file_path =$file_path1.'/'.$file_path2.'/';
        //$upload_path = $this->upload_dir.'/'.$file_path;
        return $file_path;
    }
    public static function mkMd5Dir($id,$save_path){
        $md5_value = md5($id);
        $file_path1 = substr($md5_value,-2,2);
        $file_path2 = substr($md5_value,-4,2);
        $file_path =$file_path1.'/'.$file_path2.'/';
        $upload_path = $save_path.'/'.$file_path;
        return self::recursiveMkdir($upload_path);
    }
}