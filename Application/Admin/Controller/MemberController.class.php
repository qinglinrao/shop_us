<?php
namespace Admin\Controller;
use Think\Controller;

/* 用户管理
 * author:baozhijian@2018-03-31 12:23
 * email:oin5566@126.com
 * qq:330416922
 * */
class MemberController extends CommonController {

    function __construct()
    {
        parent::__construct();
        if($_SESSION['admin_name'] != 'admim'){

            print_r('没有权限');exit;
        }
    }

	//用户列表
	function tjrlist(){
		$keyword = I('get.keyword');
		$utype = I('get.utype');
		$db = M('member');
		$where = [];
		if ($utype) {
			$where['utype'] = $utype;
		}
		if ($keyword) {
			$where['username|phone'] = array('like','%' . $keyword . '%');
		}
		$count = $db->where($where)->count();
		$page = show_page($count,10);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('utype',$utype);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}

	function getCity() {
		$id = I('id');
		$city = M('province')->where(['father'=>$id])->select();
		echo '<option>--请选择市--</option>';
		foreach ($city as $v) {
			echo "<option value='" . $v['province_id'] . "'>" . $v['province'] . "</option>";
		}
	}

	function getArea() {
		$id = I('id');
		$county = M('province')->where("father=%d", $id)->select();
		echo '<option>--请选择区--</option>';
		foreach ($county as $v) {
			echo "<option value='" . $v['province_id'] . "'>" . $v['province'] . "</option>";
		}
	}

	function tjr_add(){
	//用户添加页
		$province = M('province')->where("father = 0")->select();
		$this->assign('province',$province);
		$this->display();
	}

	function tjr_doadd(){
	//用户添加操作
		if( I('username') && I('phone') && I('address') && I('province') && I('city') && I('county') ){
			$data['phone'] = I('phone');
			$res = M('member')->where($data)->find();
			if($res){
				$this->error('手机号已注册，不能多次注册');
			}
			$province = M('province')->where(['province_id'=>I('province')])->field('province')->find();
			$city = M('province')->where(['province_id'=>I('city')])->field('province')->find();
			$county = M('province')->where(['province_id'=>I('county')])->field('province')->find();
			$data['username'] = I('username');
			$data['address'] = $province['province'] . ',' . $city['province'] . ',' . $county['province'] . ',' . I('address');
			$data['email'] = I('email');
			$data['create_at'] = NowTime();
			$data['update_at'] = NowTime();
			$data['utype'] = 2;
			$ress = M('member')->add($data);
			if($ress){
				$this->success('信息添加成功',U('Member/tjr_add'));
			}else{
				$this->error('信息添加失败',U('Member/tjr_add'));
			} 

		}else{
			$this->error('信息添加失败',U('Member/tjr_add'));
		}
	}

	function tjr_edit(){
	//用户编辑页
		$id = I('id');
		$list = '';
		if($id){
			$list = M('member')->find($id);
			if(!$list){
				$this->error('数据不存在',U('Member/tjrlist'));
			}
			$item = explode(',',$list['address']);
			$list['province'] = $item[0];
			if($list['province']){
				$province = M('province')->where("father = 0")->select();
				$this->assign('province',$province);
			}
			$list['city'] = $item[1];
			if($list['city']){
				$cityArr = M('province')->where(['province'=>$item[1]])->field('father')->find();
				$city = M('province')->where(['father'=>$cityArr['father']])->select();
				$this->assign('city',$city);
			}
			$list['county'] = $item[2];
			if($list['county']){
				$countyArr = M('province')->where(['province'=>$item[2]])->field('father')->find();
				$county = M('province')->where(['father'=>$countyArr['father']])->select();
				$this->assign('county',$county);
			}
			$list['address'] = $item[3];
		}else{
			$this->error('缺少必要参数',U('Member/tjrlist'));
		}
		$this->assign('list',$list);
		$this->display();

	}

	function tjr_doedit(){
	//代理人编辑操作
		$id = I('get.id');
		if($id){
			if( I('username') && I('phone') && I('address') && I('province') && I('city') && I('county') ){
				$data['phone'] = I('phone');
				$data['id']  = array('neq',I('get.id'));
				$res = M('member')->where($data)->find();
				if($res){
					$this->error('修改失败，该手机号已注册使用中');
				}
				unset($data['id']);
				$province = M('province')->where(['province_id'=>I('province')])->field('province')->find();
				$city = M('province')->where(['province_id'=>I('city')])->field('province')->find();
				$county = M('province')->where(['province_id'=>I('county')])->field('province')->find();
				$data['username'] = I('username');
				$data['address'] = $province['province'] . ',' . $city['province'] . ',' . $county['province'] . ',' . I('address');
				$data['email'] = I('email');
				$data['update_at'] = NowTime();
				$ress = M('member')->where('id=%d',$id)->save($data);
				if($ress){
					$this->success('编辑信息成功',U('Member/tjrlist'));
				}else{
					$this->error('编辑信息失败',U('Member/tjrlist'));
				} 

			}else{
				$this->error('信息填写不完全',U('Member/tjrlist'));
			}				
		}else{
			$this->error('缺少必要参数',U('Member/tjrlist'));
		}
	}

	function tjr_del(){
	//代理人删除操作
		$id = I('get.id');
		if($id){
			$res = M('member')->where('id=%d',$id)->delete();
			if($res){
				$this->success('信息删除成功',U('Member/tjrlist'));
			}else{
				$this->success('信息删除失败，没有找到该信息',U('Member/tjrlist'));
			}
		}else{
			$this->error('缺少必要参数',U('Member/tjrlist'));
		}
	}





}