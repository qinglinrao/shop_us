<?php
namespace Admin\Controller;
use Think\Controller;
//促销管理
class PromotionController extends CommonController {

	// 买n送n列表页
	public function index() {
		$keyword = I('get.keyword');
		$type = I('get.type');
		$where['statue'] = 1;
		$where['ptype'] = $type;
		if ($keyword) {
			$where['id|pro_name'] = array('like','%' . $keyword . '%');
		}
		$order = 'statue asc,create_at asc';
		$db = M('promotion');
		$count 	= $db->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;	
		$list = $db->where($where)->order($order)->limit($limit)->select();

		foreach ($list as $k=>$v) {
		    if ($type == 1) {
		        $list[$k]['text'] = '买'.$v['first'].'送'.$v['second'];
            } else {
                $list[$k]['text'] = '买'.$v['first'].'打'.$v['second'].'折';
            }
        }

		if($type == 1){
			$this->listTag = '买n送n列表';
			$this->addTag = '添加买n送n';
		}else{
			$this->listTag = '买n打n折列表';
			$this->addTag = '添加买n打n折';
		}
		$this->type = $type;

		$this->assign('page',$page->show());
		$this->assign('list',$list); 
		$this->assign('keyword',$keyword); 
		$this->display();
	}

	// 设置买n送n页
	public function set_tj() {
		$id = I('get.id');
		$type = I('get.type');
		$tip = '添加';
		if($id){
			$tip = '编辑';
			$info = M('promotion')->find($id);
//			if(NowTime() < $info['start_at']){
//				$info['cstatue'] = '未开始';
//			}elseif(NowTime() > $info['end_at']){
//				$info['cstatue'] = '已结束';
//			}else{
//				$info['cstatue'] = '进行中';
//			}
			$info['time_area'] = $info['start_at'] . '~' . $info['end_at'];
			$this->assign('info',$info);
		}

		if($type == 1){
			$this->listTag = '买n送n列表';
			$this->addTag = $tip . '买n送n';
		}else{
			$this->listTag = '买n打n折列表';
			$this->addTag = $tip . '买n打n折';
		}
		$this->type = $type;
		$colorList = M('color')->where('statue=1')->select();
		$this->color = $colorList;

		$this->display();
	}

	// 设置买n送n操作
	public function doset_tj() {
		$id = I('get.id');
		$type = I('get.type');
		$data['pro_name'] = I('pro_name');
		$data['first'] = I('first');
		$data['second'] = I('second');
		$data['color_val'] = I('color_val');
		$area = I('time_area');
		$times = explode('~',$area);
		$data['start_at'] = $times[0];
		$data['end_at'] = $times[1];
		$data['update_at'] = NowTime();
		$data['ptype'] = $type;
		if($id){
			$res = M('promotion')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('促销规则成功',U('Promotion/index',array('type'=>$type)));
			}else{
				$this->error('促销规则修改失败',U('Promotion/index',array('type'=>$type)));
			}
 		}else{
			$data['create_at'] = NowTime();
			$data['statue'] = 1;
			$res = M('promotion')->add($data);
			if($res !== false){
				$this->success('促销规则添加成功',U('Promotion/index',array('type'=>$type)));
			}else{
				$this->error('促销规则添加失败',U('Promotion/index',array('type'=>$type)));
			}
		}
	}

	// 删除买n送n操作
	public function del() {
		$id = I('get.id');
		$type = I('get.type');
		if($id){
			$data['statue'] = 2;
			$data['update_at'] = NowTime();
			$res = M('promotion')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('删除促销规则成功',U('Promotion/index',array('type'=>$type)));
			}else{
				$this->error('删除促销规则失败',U('Promotion/index',array('type'=>$type)));
			}
 		}else{
			$this->error('请选择您要删除的促销规则');
		}
	}

	//促销模版列表
	public function mpage(){
		$this->display();
	}

	//添加促销关联模版
	public function madd() {
		$this->display();
	}
}
