<?php
namespace Admin\Controller;
use Think\Controller;

class ConfigController extends Controller {
    function __construct()
    {
        parent::__construct();
        if($_SESSION['admin_name'] != 'admim'){

            print_r('没有权限');exit;
        }
    }
    public function index(){
        $siteinfo_file = include('Config/siteinfo.config.php');
        $siteinfo_file = $siteinfo_file ? $siteinfo_file : array();
        $this -> assign('siteinfo', $siteinfo_file);
        $this->display();
    }

    public function update_config(){
        $siteinfo_file = 'Config/siteinfo.config.php';
        if(file_exists($siteinfo_file)){
            if(IS_GET){
                $comment_isopen = I('get.comment_isopen');
                $verification_code_isopen = I('get.verification_code_isopen');
                $arr = array('comment_isopen'=>$comment_isopen, 'verification_code_isopen'=>$verification_code_isopen);

                $str = '<?php return'.PHP_EOL;
                $str .= var_export($arr, true);
                $str .= ';';

                $result = file_put_contents($siteinfo_file, $str);
                if($result){
                    $this -> success('保存成功');
                }else{
                    $this -> success('保存失败');
                }

            }else{
                $this -> error('非法操作');
            }
        }else{
            $this -> error('配置文件不存在！');
        }
    }


}
