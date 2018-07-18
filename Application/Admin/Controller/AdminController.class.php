<?php
namespace Admin\Controller;
use Think\Controller;

/* 投放（管理员）管理
 * author:baozhijian@2018-03-31 12:23
 * email:oin5566@126.com
 * qq:330416922
 * */
class AdminController extends CommonController {

    function __construct()
    {
        parent::__construct();
        if($_SESSION['admin_name'] != 'admim'){

            print_r('没有权限');exit;
        }
    }

    //投放（管理员）列表
	function admin_list(){
		$keyword = I('get.keyword');
		$db = M('admin');
		$where = array();
		if ($keyword) {
			$where['admin_name'] = array('like','%' . $keyword . '%');
		}
		$count = $db->where($where)->count();
		$page = show_page($count,10);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}

    function admin_add(){
        //管理员添加页
        $province = M('admin')->select();
        $this->assign('province',$province);
        $this->display();
    }

    function admin_doadd(){
        //管理员添加操作
        if( I('admin_name') && I('admin_pass')){
            $data['admin_name'] = I('admin_name');
            $res = M('admin')->where($data)->find();
            if($res){
                $this->error('管理员已存在，不能多次注册');
            }
            $data['admin_alias'] = I('admin_alias');
            $data['admin_introduction'] = I('admin_introduction');
            $data['admin_pass'] = md5(I('admin_pass'));
            # 生成订单识别码
            $data['admin_code'] = uniqid();
            $data['create_at'] = NowTime();
            $data['update_at'] = NowTime();
            $data['login_ip'] = '0';
            $data['login_time'] = NowTime();
            $data['admin_type'] = I('admin_type');
            $ress = M('admin')->add($data);
            if($ress){
                $this->success('管理员添加成功',U('Admin/admin_list'));
            }else{
                $this->error('管理员添加失败',U('Admin/admin_add'));
            }

        }else{
            $this->error('管理员添加失败',U('Admin/admin_add'));
        }
    }

    function admin_edit(){
        //管理员编辑页
        $id = I('admin_id');
        $list = '';
        if($id){
            $list = M('admin')->find($id);
            if(!$list){
                $this->error('数据不存在',U('Admin/admin_list'));
            }
        }else{
            $this->error('缺少必要参数',U('Admin/admin_list'));
        }
        $this->assign('list',$list);
        $this->display();

    }

    function admin_doedit(){
        //管理员编辑操作
        $id = I('get.admin_id');
        if($id){
            if( I('admin_name') && I('admin_pass') && I('old_pass')){
                $list = M('admin')->find($id);
                if($list['admin_pass'] != md5(I('old_pass'))){
                    $this->error('密码验证错误',U('Admin/admin_list'));
                }
                $data['admin_pass'] = md5(I('admin_pass'));
                $data['update_at'] = NowTime();
                $data['admin_alias'] = I('admin_alias');
                $data['admin_name'] = I('admin_name');
                $data['admin_introduction'] = I('admin_introduction');
                $ress = M('admin')->where('admin_id=%d',$id)->save($data);
                if($ress){
                    $this->success('编辑管理员成功',U('admin/admin_list'));
                }else{
                    $this->error('编辑管理员失败',U('admin/admin_list'));
                }

            }else{
                $this->error('信息填写不完全',U('admin/admin_list'));
            }
        }else{
            $this->error('缺少必要参数',U('admin/admin_list'));
        }
    }

	function admin_del(){
	//管理员删除操作
		$id = I('get.admin_id');
		if($id){
			$res = M('admin')->where('admin_id=%d',$id)->delete();
			if($res){
				$this->success('管理员删除成功',U('Admin/admin_list'));
			}else{
				$this->success('管理员删除失败，没有找到该信息',U('Admin/admin_list'));
			}
		}else{
			$this->error('缺少必要参数',U('Admin/admin_list'));
		}
	}





}