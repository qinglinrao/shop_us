<?php
namespace Admin\Controller;
use Think\Controller;

class GoodsTypeController extends CommonController {
//商品管理
	function index(){
	//列表页
		$db = M('goods_type');
		$where['pid'] = 0;
		$where['statue'] = 1;
		$order = 'type_sort DESC,id ASC';
		$count = $db->where($where)->count();
		$page = show_page($count,10);
		$list = $db->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();

		//查询子类别
		$slist = '';
		foreach ($list as $k => $v) {
			$slist = M('goods_type')->where('pid=%d and statue=1',$v['id'])->order($order)->select();
			$list[$k]['slist'] = $slist;
		}
//		dump($list);die;
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}

	function add(){
	//类别信息添加页
		$this->display();
	}

	function doadd(){
	//类别信息添加操作
		if(IS_POST){
			$data['pid']=I('pid');
			$data['type_name'] = I('type_name');
			$data['type_sort'] = I('type_sort');
			$data['create_at'] = date('Y-m-d H:i:s',time());
			$data['update_at'] = date('Y-m-d H:i:s',time());
			$db = M('goods_type');
			$db->create($data);
			$res = $db->add();
			if($res){
				$this->success('信息添加成功',U('GoodsType/index'));
			}else{
				$this->error('信息添加失败',U('GoodsType/add'));
			}
 		}else{
 			$this->error('参数错误',U('GoodsType/add'));
		}
	}

	function getChildType(){
		$id = I('get.id');
		$info = M('goods_type')
				->where(['pid'=>$id,'statue'=>1])
				->order('type_sort DESC,id ASC')
				->field('id,pid,type_name')
				->select();
		if(empty($info)){
			$return = array('code'=>400,'msg'=>'无子分类');
		}else{
			$return = array('code'=>200,'data'=>$info);
		}
		echo json_encode($return);
	}

	function edit(){
	//类别信息编辑页
		$id = I('get.id');
		$info = $tlist = $slist = '';
		if($id){
			$info = M('goods_type')->find($id);
			if(!$info){
				$this->error('信息不存在',U('GoodsType/index'));
			}
			//得到父类信息
			$tlist = M('goods_type')->where('pid=0')->select();
			foreach ($tlist as $k => $v) {
				//得到子类信息
				$slist = M('goods_type')->where('pid='.$v['id'])->select();
				$tlist[$k]['slist'] = $slist;
			}
		}else{
			$this->error('缺少必要参数',U('GoodsType/index'));
		}
		$this->assign('info',$info);
		$this->assign('tlist',$tlist);
		$this->display();
	} 

	function doedit(){
	//类别信息编辑操作
		$id = I('get.id');
		if($id){
			if(IS_POST){
				if(I('pid')){
					$data['pid']=I('pid');
				}
				$data['type_name']=I('type_name');
				$data['type_sort']=I('type_sort');
				$data['update_at']=date('Y-m-d H:i:s',time());
				$db = M('goods_type');
				$db->create($data);
				$res = $db->where('id=%d',$id)->save();
				if($res){
					$this->success('信息编辑成功',U('GoodsType/index'));
				}else{
					$this->error('信息编辑失败');
				}
	 		}else{
	 			$this->error('参数错误',U('GoodsType/index'));
			}
		}else{
			$this->error('缺少必要参数');
		}
	}

	function del(){
	//类别信息删除操作
		$id = I('get.id');
		if($id){
			$pid = I('get.pid');
			$data['statue'] = 2;
			if($pid=='0'){
			//说明是父类 应删除子类
				$cinfo =  M('GoodsType')->where('pid=%d and statue=1',$id)->select($data);
				if(!empty($cinfo)){
					$ress =  M('GoodsType')->where('pid='.$id)->save($data);
					if(!$ress){
						$this->error('子类别信息删除失败',U('GoodsType/index'));
					}
				}
			}
			$res = M('GoodsType')->where('id=%d',$id)->save($data);
			if($res){
					$this->success('信息删除成功',U('GoodsType/index'));
				}else{
					$this->error('信息删除失败',U('GoodsType/index'));
			}
		}else{
			$this->error('缺少必要参数',U('GoodsType/index'));
		}
	}

	function addchild(){
		//类别信息添加页
		$id = I('get.id');
		$pinfo = M('goods_type')->find($id);
		$this->assign('pinfo',$pinfo);
		$this->display();
	}
}
