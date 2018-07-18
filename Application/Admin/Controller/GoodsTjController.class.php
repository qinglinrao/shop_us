<?php
namespace Admin\Controller;
use Think\Controller;
//商品推荐管理
class GoodsTjController extends CommonController {

	// 推荐列表页
	public function index() {
		$where['goods_stats'] = 1; //已上架
		$where['is_tj'] = 1; //已推荐
		$keyword = I('get.keyword');
		$where['is_tj'] = 1;
		$wherestr['a.is_tj'] = 1;
		if ($keyword) {
			$where['goods_title|id'] = array('like','%' . $keyword . '%');
			$wherestr['a.goods_title|a.id'] = array('like','%' . $keyword . '%');
		}
		$db = M('goods');
		$count 	= $db->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_goods a';
		$join 	= array('LEFT JOIN pt_goods_type b on a.cate_id = b.id');
		$field 	= 'a.*,b.type_name as tname';
		$order 	= 'a.id asc';
		$list 	= M()->table($table)->join($join)->where($wherestr)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$itemImg = M('goods_image')->where('stype=1 and good_id=%d',$v['id'])->order('id asc')->limit(1)->field('image')->select();
			$list[$k]['goods_img'] = $itemImg[0]['image'];
			//普通标签
			if ($v['goods_tag'] != 0){
				$tagList = explode(',', $v['goods_tag']);
				$itemTag = '';
				foreach ($tagList as $vv) {
					$itemTagInfo = M('tag')->find($vv);
					$itemTag .= $itemTagInfo['tag_name'] . ' ';
				}
				$list[$k]['tag_name'] = $itemTag;
			}
			//拼团
			if ($v['goods_istuan'] != 0){
				$itemTuanInfo = M('tourdiy')->find($v['goods_istuan']);
				$list[$k]['tuan_name'] = $itemTuanInfo['pname'];
			}
			//促销
			if ($v['goods_promotion'] != 0){
				$itemTuanInfo = M('promotion')->find($v['goods_promotion']);
				$list[$k]['pro_name'] = $itemTuanInfo['pro_name'];
			}
		}
		$this->assign('page',$page->show());
		$this->assign('list',$list); 
		$this->assign('keyword',$keyword); 
		$this->display();
	}

	// 设置推荐商品页
	public function set_tj() {
		$where['goods_stats'] = 1; //已上架
		$where['is_tj'] = 0; //未推荐
		$list = M('goods')->where($where)->select();
		$this->assign('list',$list); 
		$this->display();
	}

	// 设置推荐商品操作
	public function doset_tj() {
		$id = I('post.goodsid');
		if($id){
			$data['tj_sort'] = I('tj_sort');
			$data['is_tj'] = 1;
			$res = M('goods')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('推荐商品成功',U('GoodsTj/index'));
			}else{
				$this->error('推荐商品失败',U('GoodsTj/index'));
			}
 		}else{
			$this->error('请选择您要推荐的商品');
		}
	}

	// 取消推荐商品操作
	public function noset_tj() {
		$id = I('get.id');
		if($id){
			$data['is_tj'] = 0;
			$data['tj_sort'] = 0;
			$res = M('goods')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('取消推荐商品成功',U('GoodsTj/index'));
			}else{
				$this->error('取消推荐商品失败',U('GoodsTj/index'));
			}
 		}else{
			$this->error('请选择您要取消推荐的商品');
		}
	}
}