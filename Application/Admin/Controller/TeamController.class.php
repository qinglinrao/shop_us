<?php
namespace Admin\Controller;
use Think\Controller;
//商品推荐管理
class TeamController extends CommonController {

	// 推荐列表页
	public function index() {
		$keyword = I('get.keyword');
		$where['statue'] = 1;
		if ($keyword) {
			$where['pname|id'] = array('like','%' . $keyword . '%');
		}
		$order = 'statue asc,id asc';
		$db = M('tourdiy');
		$count 	= $db->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;	
		$list = $db->where($where)->order($order)->limit($limit)->select();
		$this->assign('page',$page->show());
		$this->assign('list',$list); 
		$this->assign('keyword',$keyword); 
		$this->display();
	}

	// 设置推荐商品页
	public function set_tj() {
		$id = I('get.id');
		if($id){
			$info = M('tourdiy')->find($id);
			$info['time_area'] = implode([$info['start_at'],$info['end_at']],'~');
			$this->assign('info',$info);
		}
		$this->display();
	}

	// 设置拼团操作
	public function doset_tj() {
		$id = I('get.id');
		$data['pname'] = I('pname');
		$data['num'] = I('num');
		$itemTime = explode('~',I('time_area'));
		$data['start_at'] = $itemTime[0];
		$data['end_at'] =$itemTime[1];
		$data['update_at'] = NowTime();
		if($id){
			
			$res = M('tourdiy')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('拼团修改成功',U('Team/index'));
			}else{
				$this->error('拼团修改失败',U('Team/index'));
			}
 		}else{
			$data['create_at'] = NowTime();
			$data['statue'] = 1;
			$res = M('tourdiy')->add($data);
			if($res !== false){
				$this->success('拼团添加成功',U('Team/index'));
			}else{
				$this->error('拼团添加失败',U('Team/index'));
			}
		}
	}

	// 取消推荐商品操作
	public function del() {
		$id = I('get.id');
		if($id){
			$data['statue'] = 2;
			$data['update_at'] = NowTime();
			$res = M('tourdiy')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('弃用拼团成功',U('Team/index'));
			}else{
				$this->error('弃用拼团失败',U('Team/index'));
			}
 		}else{
			$this->error('请选择您要弃用的拼团');
		}
	}
	// 取消推荐商品操作
	public function reset() {
		$id = I('get.id');
		if($id){
			$data['statue'] = 1;
			$data['update_at'] = NowTime();
			$res = M('tourdiy')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('弃用拼团成功',U('Team/index'));
			}else{
				$this->error('弃用拼团失败',U('Team/index'));
			}
 		}else{
			$this->error('请选择您要弃用的拼团');
		}
	}
}
