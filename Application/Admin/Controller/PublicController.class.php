<?php
namespace Admin\Controller;
use Think\Controller;

class PublicController extends Controller {

	// 管理员登录
	public function login() {
		if (!IS_POST) {
			$this->display();
		} else {
			$admin_name = I('post.username');
			$admin_pass = md5(I('post.userpass'));
			$admin_code = I('post.usercode');
			$verify = new \Think\Verify();
			if (!$verify->check($admin_code)) {
				$this->ajaxReturn(array('status'=>'error', 'type'=>'0', 'msg'=>'验证数字不正确'));
			}
			$db = M('admin');
			$result = $db->where("admin_name = '". $admin_name ."'")->find();
			if (!$result) {
				$this->ajaxReturn(array('status'=>'error', 'type'=>'1', 'msg'=>'登录帐号不正确'));
			}
			if ($result['admin_pass'] != $admin_pass) {
				$this->ajaxReturn(array('status'=>'error', 'type'=>'2', 'msg'=>'登录密码不正确'));
			}
			$data['login_num'] = $data['login_num'] + 1;
			$data['login_ip'] = get_client_ip();
			$data['login_time'] = time();
			$db->where("admin_id = ". $result['admin_id'])->save($data);
			session('admin_id', $result['admin_id']);
			session('admin_name', $result['admin_name']);
			unset($data['login_num']);
			$data['login_type'] = 1;
			$data['login_user'] = $result['admin_id'];
			M('login_log')->add($data);
			$this->ajaxReturn(array('status'=>'success', 'msg'=>'管理员登录成功'));
		}
	}

	// 管理员退出
	public function logout() {
		session(null);
		$this->success('退出登录成功', U('public/login'));
	}

	// 验证数字
	public function verify() {
		$Verify = new \Think\Verify();
		$Verify->bg = array(255,255,255);
		$Verify->imageW = 140;
		$Verify->imageH = 38;
		$Verify->fontSize = 18;
		$Verify->length = 4;
		$Verify->codeSet = '023456789';
		// 干扰背景 false / true
		$Verify->useCurve = true;
		// 干扰线 false / true
		$Verify->useNoise = false;
		$Verify->entry();
	}

}