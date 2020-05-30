<?php
    date_default_timezone_set('PRC');
    //腾讯云配置参数
    // var_dump(SYS_OSS);die;
    require(SYS_OSS.'include.php');
    use QCloud\Cos\Api;

    //阿里云配置参数
    use OSS\Core\OssException;
    use OSS\OssClient;
    require(SYS_OSS_ALIYUN.'autoload.php');

class Ajax_uploadController extends Common
{
    public $flowerhua;
    public $Ossconfig;
    public $cosApi;
    public $folder;

    //阿里云配置参数
    const OSS_ALIYUN_ACCESS_ID = 'LTAI6dbUgYSXjIP5';
    const OSS_ALIYUN_ACCESS_KEY = 'IxIV9H9S0D3QIA9YKZZuvFfZFPE3m3';
    const OSS_ALIYUN_ENDPOINT = 'oss-cn-beijing.aliyuncs.com';
    const OSS_ALIYUN_TEST_BUCKET = 'mituans-standard';
    const OSS_ALIYUN_FOLDER = 'upload-dir222';

    //腾讯云配置参数
    public $ossClient;
    const OSS_FOLDER = 'flowerhua';
    const OSS_BUCKET = 'test2';
    public function __construct()
    {
        parent::__construct();
        $this->flowerhua = $this->model('flowerhua');
        //腾讯云配置参数
        $this->Ossconfig = array(
            'app_id'     => '1253432000',
            'secret_id'  => 'AKIDERXYQC4Tq46wpX5Xlx2wjLlz11sdFcru',
            'secret_key' => 'znPuDONAlAOPFtNflfOvGOM8i6II9oH3',
            'region'     => 'gz',   // bucket所属地域：华北 'tj' 华东 'sh' 华南 'gz'
            'timeout'    => 60,
        );
        //腾讯云实例化oss操作类
        $this->cosApi = new Api($this->Ossconfig);

        //阿里云实例化oss操作类
        try {
            $this->ossClient = new OssClient(self::OSS_ALIYUN_ACCESS_ID, self::OSS_ALIYUN_ACCESS_KEY, self::OSS_ALIYUN_ENDPOINT);
        } catch (OssException $e) {
            print $e->getMessage();
        }
    }

    public function ajax_set_file2AliyunAction(){
        // 上传本地文件
        $img = $_FILES['addlogo'];
        $up = $this->upload($img, array('jpeg', 'jpg', 'gif', 'png', 'bmp'),$this->site['FILE_SIZE']);//上传原图图片
        if($up['result']===false){
            //本地上传路径
            $local_src = $up['path'];
            //线上上传路径
            $line_src = self::OSS_ALIYUN_FOLDER.'/'.$local_src;
            try {
                $result = $this->ossClient->uploadFile(self::OSS_ALIYUN_TEST_BUCKET, $line_src , $local_src);
            }
            catch (OssException $e) {
                $result = $e->getMessage();
            }
            if($result['info']['http_code']==200){
                @unlink($local_src);
            }
            $arr = array(
                'result' => $up['result'],
                'access_url' => $result['oss-request-url'],
//                'url' => $result['data']['url'],
                'size' => $img['size']
            );
        }
        unset($up);unset($result);unset($img);
        echo json_encode($arr);
    }


    public function ajax_set_imgAction()
    {
         $img = $_FILES['addlogo'];
        // $img = $this->post('formData');
        $up = $this->upload($img, array('jpeg', 'jpg', 'gif', 'png', 'bmp','pdf'),$this->site['FILE_SIZE']);//上传原图图片
//        echo json_encode($up);die;
//        $path_thumb = "uploadfiles/thumb/" . md5($up['path']) . ".png";
//        img2thumb($up['path'], $path_thumb);//生成缩略图
//        $data['logo'] = $path_thumb;
//        $this->member->set($member_id,$data);
            $arr = array(
                'result' => $up['result'],
                'access_url' => $up['path'],
                'size' => $img['size']
            );
        unset($up);unset($res);unset($img);
        echo json_encode($arr);
    }

     public function ajax_set_imgsAction()
    {
        $img = $_FILES['addlogo'];
        $arr = '';
        foreach ($img as $value) {
            $up = $this->upload($value, array('jpeg', 'jpg', 'gif', 'png', 'bmp','pdf'),$this->site['FILE_SIZE']);//上传原图图片
    //        echo json_encode($up);die;
    //        $path_thumb = "uploadfiles/thumb/" . md5($up['path']) . ".png";
    //        img2thumb($up['path'], $path_thumb);//生成缩略图
    //        $data['logo'] = $path_thumb;
    //        $this->member->set($member_id,$data);
            $arr = array(
                'result' => $up['result'],
                'access_url' => $up['path'],
                'size' => $img['size']
            );
                   
            unset($up);unset($res);unset($value);
            
        }

        echo json_encode($arr);
    }

    /**
     * 文件上传
     * @param  $fields      上传字段 'file'
     * @param  $type        文件类型  array(jpg,gif)
     * @param  $size        文件大小  KB
     * @param  $img         图片配置参数
     * @param  $mark        图片水印
     * @param  $admin       是否来自后台
     * @param  $stype       上传方式  swf或者ke
     * @param  $ofile       原文件
     * @param  $document    后台栏目归档目录
     * @return Array        返回数组
     */
    public function upload($fields, $type, $size = 20, $img = null, $mark = true, $admin = 0, $stype = null, $ofile = null, $document = null)
    {
        $path = 'uploadfiles/';
        $upload = $this->instance('file_upload');
        if (empty($admin) && $this->memberinfo) {
            $uid = $this->memberinfo['id']; //会员附件归类
            if ($uid) {
                $path .= 'member/' . $uid . '/';
                if (isset($this->membergroup[$this->memberinfo['groupid']]['filesize']) && $this->membergroup[$this->memberinfo['groupid']]['filesize']) {
                    $c = count_member_size($this->memberinfo['id']);
                    if ($c > $this->membergroup[$this->memberinfo['groupid']]['filesize'] * 1024 * 1024) {
                        $this->attMsg(lang('att-7', array('1' => $this->membergroup[$this->memberinfo['groupid']]['filesize'], '2' => formatFileSize($c))), $stype);
                    }
                }
            }
            $document = null;
        } elseif ($admin) {
            $uid = (int)get_cookie('member_id');
        } else {
            //$this->attMsg(lang('att-0'), $stype);
            $uid = 0;
            $patp = 'uploadfiles/guest/';
        }
        $upload->set($fields)->set_limit_size(1024 * 1024 * $size)->set_limit_type($type);
        //设置路径和名称
        $ext = $upload->fileext();
        if (stripos($ext, 'php') !== FALSE
            || stripos($ext, 'asp') !== FALSE
            || stripos($ext, 'aspx') !== FALSE
        ) {
            return array('result' => '文件格式被系统禁止');
        }
        if (in_array($ext, array('jpg', 'jpeg', 'bmp', 'png', 'gif'))) {
            $dir = 'image';
            $upload->set_image($img['w'], $img['h'], $img['t']);
        } else {
            $dir = 'file';
        }
        $path .= $dir . '/' . (empty($document) || $document == 'undefined' || !preg_match('/^[a-zA-Z_0-9]+$/', $document) ? '' : $document . '/');
        if ($ofile && is_file($ofile) && strpos($path, dirname(dirname($ofile))) === 0) { //判断原文件
            $path = dirname($ofile) . '/';
            $file = $fname = basename($ofile);

        } else {
            $path .= date('Ym') . '/';
            $data = file_list::get_file_list($path);
            $time = time();
            $random = randomkeys(6);
            $name = count($data) + 1 .$time.$random;
            $name = is_file($path . $name . '.' . $ext) ? $name . str_replace('0.', '_', (double)microtime()) : $name;
            $file = $upload->filename();
            $fname = $name . '.' . $ext;
        }
        $result = $upload->upload($path, $fname);
        //上传成功处理图片
        if (!$result && $dir == 'image') {
            $this->watermark($path . $fname);
        }
        return array('result' => $result, 'path' => $path.$fname, 'file' => $file, 'ext' => $dir == 'image' ? 'png' : $ext);
    }













































}