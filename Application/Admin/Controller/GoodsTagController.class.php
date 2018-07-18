<?php
namespace Admin\Controller;
use Think\Controller;
//商品推荐管理
class GoodsTagController extends CommonController {

	// 推荐列表页
	public function index() {
		$keyword = I('get.keyword');
		$where = array();
		if ($keyword) {
			$where['tag_name|id'] = array('like','%' . $keyword . '%');
		}
		$order = 'statue asc,create_at asc';
		$db = M('tag');
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
			$info = M('tag')->find($id);
			$this->assign('info',$info);
		}
		$this->display();
	}

	// 设置标签操作
	public function doset_tj() {
		$id = I('get.id');
		if($id){
			$data['tag_name'] = I('tag_name');
			$data['update_at'] = NowTime();
			$res = M('tag')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('标签修改成功',U('GoodsTag/index'));
			}else{
				$this->error('标签修改失败',U('GoodsTag/index'));
			}
 		}else{
			$data['tag_name'] = I('tag_name');
			$data['create_at'] = NowTime();
			$data['update_at'] = NowTime();
			$data['statue'] = 1;
			$res = M('tag')->add($data);
			if($res !== false){
				$this->success('标签添加成功',U('GoodsTag/index'));
			}else{
				$this->error('标签添加失败',U('GoodsTag/index'));
			}
		}
	}

	// 取消推荐商品操作
	public function del() {
		$id = I('get.id');
		if($id){
			$data['statue'] = 2;
			$data['update_at'] = NowTime();
			$res = M('tag')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('弃用标签成功',U('GoodsTag/index'));
			}else{
				$this->error('弃用标签失败',U('GoodsTag/index'));
			}
 		}else{
			$this->error('请选择您要弃用的标签');
		}
	}
}
