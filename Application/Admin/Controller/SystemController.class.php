<?php
namespace Admin\Controller;
use Think\Controller;

class SystemController extends CommonController {

	// 系统设置
	public function system() {
		$db = M('system');
		if (!IS_POST) {
			$result = $db->where('system_id = 1')->find();
			$this->assign('result',$result);
			$this->display();
		} else {
			$db->create();
			$db->system_time = time();
			$db->save();
			$this->success('编辑信息成功');
		}
	}

	// 上传二维码
	public function qrcode() {
		$type = 'images';
		$folder = 'site';
		$item = 'qrcode';
		$name = '';
		$width = 200;
		$height = 200;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	// 管理员
	public function admin() {
		$keyword = I('get.keyword');
		$state = I('get.state');
		$db = M('admin');
		if ($keyword) {
			$where['admin_name'] = array('like',$keyword);
		}
		if ($state) {
			$where['admin_state'] = $state;
		}
		$order = 'admin_id desc';
		$count = $db->where($where)->count();
		$page = show_page($count,10);
		$list = $db->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('keyword',$keyword);
		$this->assign('state',$state);
		$this->assign('count',$count);
		$this->assign('list',$list);
		$this->assign('page',$page->show());
		$this->display();
	}

	public function admin_add() {
		if (!IS_POST) {
			$this->display();
		} else {
			$db = M('admin');
			$rules = array(
				array('admin_name','','登录账号已经存在',0,'unique',1),
			);
			if (!$db->validate($rules)->create()) {
				$this->error($db->getError());
			} else {
				$db->add();
				$this->success('添加信息成功', U('system/admin'));
			}
		}
	}

	public function admin_edit() {
		$admin_id = I('get.admin_id');
		$db = M('admin');
		if (!IS_POST) {
			$info = $db->where('admin_id = '.$admin_id)->find();
			$this->assign('info',$info);
			$this->display();
		} else {
			$pass = I('post.admin_pass');
			$where['admin_id'] = I('post.admin_id');
			if ($pass) {
				$data['admin_pass'] = md5($pass);
			}
			$data['admin_state'] = I('post.admin_state');
			$db->where($where)->save($data);
			$this->success('编辑信息成功', U('system/admin'));
		}
	}

	public function admin_del() {
		$admin_id = I('get.admin_id');
		M('admin')->delete($admin_id);
		$this->success('删除信息成功');
	}

	// 支付接口
	public function payment() {
		$keyword = I('get.keyword');
		$state = I('get.state');
		$db = M('payment');
		if ($keyword) {
			$where['payment_name'] = array('like',$keyword);
		}
		if ($state) {
			$where['payment_state'] = $state;
		}
		$order = 'payment_id';
		$count = $db->where($where)->count();
		$page = show_page($count,10);
		$list = $db->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('keyword',$keyword);
		$this->assign('state',$state);
		$this->assign('count',$count);
		$this->assign('list',$list);
		$this->assign('page',$page->show());
		$this->display();
	}

	public function payment_edit() {
		$payment_id = I('get.payment_id');
		$db = M('payment');
		if (!IS_POST) {
			$info = $db->where('payment_id = '.$payment_id)->find();
			$config = unserialize($info['payment_config']);
			$this->assign('info',$info);
			$this->assign('config',$config);
			$this->display('payment_edit_'.$payment_id);
		} else {
			$post = I('post.');
			unset($post['payment_id']);
			unset($post['payment_state']);
			$data['payment_id'] = I('post.payment_id');
			$data['payment_config'] = serialize($post);
			$data['payment_state'] = I('post.payment_state');
			$data['payment_time'] = time();
			$db->save($data);
			$this->success('编辑信息成功', U('system/payment'));
		}
	}

	// 短信接口
	public function sms() {
		$this->display();
	}

	// 修改密码
	public function password() {
		if (!session('?admin_id')) {
			$this->redirect('login');
		} else {
			if (!IS_POST) {
				$this->display();
			} else {
				$db = M('admin');
				$where['admin_id'] = session('admin_id');
				$where['admin_pass'] = md5(I('post.old_pass'));
				$result = $db->where($where)->find();
				if (!$result) {
					$this->error('原始密码输入错误');
				} else {
					$data['admin_pass'] = md5(I('post.agn_pass'));
					$db->where($where)->save($data);
					$this->success('修改密码成功');
				}
			}
		}
	}

	// 清理缓存
	public function cache() {
		$path = $_GET['path'] ? $_GET['path'] : RUNTIME_PATH;
		if ($this->_delDir($path)) {
			$this->ajaxReturn(array('msg'=>'清理缓存成功'));
		}
	}

}