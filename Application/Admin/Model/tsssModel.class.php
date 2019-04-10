<?php
/**
 * Created by PhpStorm.
 * User: huang
 * Date: 2017/7/19
 * Time: 23:20
 */
header("content-type:text/html;charset=utf-8");
$new = new upload();

class upload{
    protected $fileName;
    protected $fileInfo;
    protected $maxSize;
    protected $allowExt;
    protected $allowMime;
    protected $uploadPath;
    protected $imgFlag;
    private $Error;
    private $Ext;


    public function __construct($fileName='myfile', $uploadPath='uploads', $maxSize = '2097152', $allowExt = array('jpg', 'jpeg', 'png', 'git', 'wbmp'), $allowMime =array('
        image/png','image/jpeg','image/gif'),$imgFlag = true){
        $this->fileName = $fileName;
        $this->fileInfo = $_FILES[$this->fileName];
        $this->maxSize = $maxSize;
        $this->allowExt = $allowExt;
        $this->allowMime = $allowMime;
        $this->uploadPath = $uploadPath;
        $this->imgFlag = $imgFlag;
    }
    //产生唯一字符串
    protected function getUniname(){
        return md5(uniqid(microtime(true),true));
    }
    //上传文件
    public function upload(){
        if($this->checkError()&&$this->checkSize()&&$this->checkExt()&&$this->imgTure()&&$this->checkMime()&&$this->checkHTTPpost()){
            $this->checkuploadPath();
            $this->uniName = $this->getUniname();
            $this->destination = $this->uploadPath.'/'.$this->uniName.'.'.$this->Ext;
            if(@move_uploaded_file($this->fileInfo['tmp_name'] , $this->destination)){
                return $this->destination;
            }else{
                $this->Error = '移动文件失败';
                $this->showError();
            }

        }else{
            $this->showError();
        }

    }
    //检测目录是否存在，创建目录
    protected function checkuploadPath(){
        if(!file_exists($this->uploadPath)){
            mkdir($this->uploadPath,0777,true);

        }
    }
    //检测文件大小
    public function checkSize(){
        if($this->fileInfo['size'] > $this->maxSize){
            $this->Error = $this->fileInfo['name'].'文件过大';
            return false;
        }
        return true;
    }



    //检测是否通过HTTP、POST方式上传
    protected function checkHTTPpost(){
        if(!is_uploaded_file($this->fileInfo['tmp_name'])){
            $this->Error = '不是通过HTTP、POST方式上传的';
            return false;
        }
        return true;
    }

    //显示错误
    protected function showError(){
        exit('<span style="color:red">'.$this->Error.'</span>');
    }

    //检测文件拓展名
    public function checkExt(){
        $this->Ext = strtolower(pathinfo($this->fileInfo['name'],PATHINFO_EXTENSION));
        if(!in_array($this->Ext,$this->allowExt)){
            $this->Error = '不允许的拓展名';
            return false;
        }
        return true;
    }

    //是否检测图片类型
    protected function imgTure(){
        if($this->imgFlag){
            if(!@getimagesize($this->fileInfo['tmp_name'])){
                $this->Error = '不是真实图片';
                return false;
            }
            return true;
        }
    }

    //检测文件格式
    public function checkMime(){
        if(!@in_array($this->fileInfo['type'],$this->allowMime)){
            $this->Error = '不允许的图片类型';
            return false;
        }
        return true;
    }


    //检测是否有错
    public function checkError(){
        if(!is_null($this->fileInfo['error'])){
            if($this->fileInfo['error'] > 0){
                switch ($this->fileInfo['error']) {
                    case 1:
                        $this->Error = '上传文件超过了配置文件中upload_max_filesize选项的值';
                        break;
                    case 2:
                        $this->Error = '超过表单MAX_FILE_SIZE限制的大小';
                        break;
                    case 3:
                        $this->Error = '文件上传不完整';
                        break;
                    case 4:
                        $this->Error = '没有选择上传文件';
                        break;
                    case 6:
                        $this->Error = '没有找到临时目录';
                        break;
                    case 7:
                        $this->Error = '文件不可写';
                        break;
                    case 8:
                        $this->Error = '由于PHP的扩展程序中断文件上传';
                        break;

                }
                return false;
            }else{
                return true;
            }
        }else{
            $this->Error = '文件上传出错';
            return false;
        }
    }
}
?>
