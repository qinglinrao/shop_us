<?php
namespace Admin\Controller;
use Think\Controller;
use Org\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
# import("PhpOffice.PhpSpreadsheet.IOFactory");
#vendor('PhpOffice.PhpSpreadsheet.IOFactory');
//订单管理
class OrdersController extends CommonController {
    function __construct()
    {
        parent::__construct();
        /*if($_SESSION['admin_name'] != 'admim'){

            print_r('没有权限');exit;
        }*/
    }

	// 订单列表
	public function index() {
		$db = M('orders');
		$keyword = I('get.keyword') ? I('get.keyword') : '';
		$keyword_num = I('get.keyword_num') ? I('get.keyword_num') : '';
		if ($keyword) {
			$where['g.goods_title'] = array('like','%' . $keyword . '%');
		}

		# 查询商品编号
        if ($keyword_num) {
            $where['g.goods_number'] = array('like','%' . $keyword_num . '%');
        }

		$tuanTime = date('Y-m-d H:i:s',time()-300);
		$statue = I('get.statue') ? I('get.statue') : '';
		if ($statue) {
			/*if($statue == 10){*/
            # 可能之前团购的状态是10，现在改成11，因为货到付款是10了。
			if($statue == 11){
				$where['o.create_at'] = array('gt',$tuanTime);
			}else{
				$where['o.statue'] = $statue;
			}
		}
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
        $admin_id = I('get.admin_id') ? I('get.admin_id') : '';
		if($admin_id){
            $where['o.admin_id'] = $admin_id;
        }
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;		
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.admin_id as admin_belong,o.order_id,o.good_count,o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_title,o.create_at,o.remark,g.admin_id,g.goods_number';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();

		# 查询投放人名称
        $order_ids = array();
        $admin_data = M('admin')->field('admin_id, admin_name')->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
            //合并管理员名称
            foreach($admin_data as $admin_val){
                if($v['admin_id'] == $admin_val['admin_id']){
                    $list[$k]['admin_name'] = $admin_val['admin_name'];
                }
                if($v['admin_belong'] == $admin_val['admin_id']){
                    $list[$k]['admin_belong_name'] = $admin_val['admin_name'];
                }
            }


		}

        $list_new = array();
        foreach($list as $v){
            //获取订单id
            $order_ids[] = $v['id'];
            $list_new[$v['id']] = $v;
        }
		# 查询规格信息。
        if($order_ids){
            $where = array();
            $where['order_id'] = array('in', $order_ids);
            $size_data = M('orders_size')->field('order_id, color, size, weight')->where($where)->select();
            if($size_data){
                foreach ($size_data as $v){
                    # 合并规格信息
                    $list_new[$v['order_id']]['size_data'] = $v['color'] . '/' . $v['size'] . '/' . $v['weight'];
                }
            }
        }
        //print_r($list_new);exit;
        $is_edit = 1;
        if($_SESSION['admin_name'] == 'Wuliu'){
            $is_edit = 0;
        }

        # 推广人员(admin_type=2的)
        $where = array();
        $where['admin_type'] = 2;
        $admin_list = M('admin')->field('admin_id, admin_name')->where($where)->select();

		$this->assign('is_edit',$is_edit);
		$this->assign('time_area',$area);
		$this->assign('statue',$statue);
		$this->assign('keyword',$keyword);
		$this->assign('keyword_num',$keyword_num);
		$this->assign('page',$page->show());
		$this->assign('list',$list_new);
		$this->assign('admin_list',$admin_list);
		$this->assign('admin_list_id',$admin_id);
		$this->display();
	}

	public function edit(){
		$id = I('get.id');
		$r = I('get.r');
		$info = M('orders')->find($id);
		$userInfo = M('member')->where('id=%d',$info['user_id'])->field('username,phone,address,code')->find();
		$goodsInfo = M('goods')->where('id=%d',$info['good_id'])->field('goods_title')->find();
		$info['username'] = $userInfo['username'];
		$info['phone'] = $userInfo['phone'];
		$info['address'] = $userInfo['address'];
		$info['goods_title'] = $goodsInfo['goods_title'];
		$info['code'] = $userInfo['code'];
		$this->r = $r;
		$this->info = $info;
		$this->display();
	}

	public function doedit(){
		$db = M('orders');
		$id = I('get.id');
		$data['pw_info'] = I('pw_info');
		$data['wl_info'] = I('wl_info');
		$data['statue'] = I('statue');
		# 修改订单，添加到货率，实收金额等信息--todo
		$r = I('r');
		$res = $db->where('id=%d',$id)->save($data);
		if($res){
			$this->success('订单修改成功',$r);
		}else{
			$this->error('订单修改失败',$r);
		}
	}
    //根据sku的订单统计
    public function sku_statistics(){
        $keyword = I('get.keyword');
        $where['statue'] = 0;
        $order_ids = array();
        if ($keyword) {
            $where['statue'] = 10;
            # 先查询订单id
            $w['goods_number'] = array('like','%' . $keyword . '%');
            $good_data = M('goods')->field("id, goods_number")->where($w)->select();
            if($good_data){
                foreach ($good_data as $val){
                    $order_ids[] = $val['id'];
                    $where['good_id'] = array('in', $order_ids);
                }
            }else{
                print_r("商品编号不存在！");
            }

        }

        $admin_id = I('get.admin_id');
        if ($admin_id) {
            $where['admin_id'] = $admin_id;

        }

        $area = I('get.time_area');
        if($area){
            $times = explode('~',$area);
            $start_at = $times[0];
            $end_at = $times[1];
            $where['add_time'] = array('between',"{$start_at},{$end_at}");
        }

        if ($keyword) {
            # 先查询总数(一个大坑，使用group再使用count()方法是不准确的。)
            $count = M('orders_size')->field('good_id, sum(num) as count, color, size, weight')->where($where)->group('good_id, color,size, weight')->select();
            $count = count($count);
            $page = show_page($count, 20);
            $limit = $page->firstRow . ',' . $page->listRows;
            $order = 'id desc';
            # 查询规格数据。
            # $size_data = M('orders_size')->where($where)->limit($limit)->order($order)->select();
            $size_data = M('orders_size')->field('good_id, sum(num) as sum,count(id) as count,color, size, weight')->where($where)->limit($limit)->group('good_id, color,size, weight')->select();
            $type = 1;
        }else{
            # 先查询总数(一个大坑，使用group再使用count()方法是不准确的。)
            $count = M('orders_size')->field('good_id, sum(num) as count, color, size, weight')->where($where)->group('good_id')->select();
            $count = count($count);
            $page = show_page($count, 20);
            $limit = $page->firstRow . ',' . $page->listRows;
            $order = 'id desc';
            # 查询规格数据。
            # $size_data = M('orders_size')->where($where)->limit($limit)->order($order)->select();
            $size_data = M('orders_size')->field('good_id, sum(num) as sum, count(id) as count,color, size, weight')->where($where)->limit($limit)->group('good_id')->select();
            $type = 2;
        }
        if($count){
            # 查询商品编号
            $good_ids = array();
            foreach ($size_data as $k=>$v){
                $good_ids[] = $v['good_id'];
            }
            $good_ids = array_unique($good_ids);
            $w2['id'] = array('in',$good_ids);
            $number_data = M('goods')->field("id, goods_number")->where($w2)->select();

            foreach ($number_data as $key=>$val){
                foreach ($size_data as $k=>$v){
                    if($val['id'] == $v['good_id']){
                        $size_data[$k]['goods_number'] = $val['goods_number'];
                    }
                }
            }

            # 查询订单编号
            /*$w3['good_id'] = array('in',$good_ids);
            $order_data = M('orders')->field("id, good_id, order_id")->where($w3)->select();

            foreach ($order_data as $key=>$val){
                foreach ($size_data as $k=>$v){
                    if($val['good_id'] == $v['good_id']){
                        $size_data[$k]['order_id'] = $val['order_id'];
                    }
                }
            }*/
        }
        # 推广人员(admin_type=2的)
        $where = array();
        $where['admin_type'] = 2;
        $admin_list = M('admin')->field('admin_id, admin_name')->where($where)->select();

        $this->assign('type',$type);
        $this->assign('count',$count);
        $this->assign('time_area',$area);
        $this->assign('statue',$where['statue']);
        $this->assign('keyword',$keyword);
        $this->assign('page',$page->show());
        $this->assign('list',$size_data);
        $this->assign('admin_list',$admin_list);
        $this->assign('admin_list_id',$admin_id);
        # 头部栏识别
        $this->assign('mark',1);
        $this->display();
    }

    //根据推广的订单统计
    public function sale_statistics(){
        $keyword = I('get.keyword');
        $where['statue'] = 0;
        $order_ids = array();

        # 推广人员(admin_type=2的)
        $where = array();
        $where['admin_type'] = 2;
        $admin_list = M('admin')->field('admin_id, admin_name')->where($where)->select();


        if ($keyword) {
            $where['statue'] = 10;
            # 先查询订单id
            $w['goods_number'] = array('like','%' . $keyword . '%');
            $good_data = M('goods')->field("id, goods_number")->where($w)->select();
            if($good_data){
                foreach ($good_data as $val){
                    $order_ids[] = $val['id'];
                    $where['good_id'] = array('in', $order_ids);
                }
            }else{
                print_r("商品编号不存在！");
            }

        }

        $admin_id = I('get.admin_id');
        if ($admin_id) {
            $where['admin_id'] = $admin_id;

        }

        $area = I('get.time_area');
        if($area){
            $times = explode('~',$area);
            $start_at = $times[0];
            $end_at = $times[1];
            $where['add_time'] = array('between',"{$start_at},{$end_at}");
        }

        $admin_list_data = array();
        foreach ($admin_list as $key=>$val){
            $where['admin_id'] = $val['admin_id'];
            if ($keyword) {
                # 先查询总数(一个大坑，使用group再使用count()方法是不准确的。)
                $count = M('orders_size')->field('good_id, sum(num) as count, color, size, weight')->where($where)->group('good_id, color,size, weight')->select();
                $count = count($count);
                $page = show_page($count, 20);
                $limit = $page->firstRow . ',' . $page->listRows;
                $order = 'id desc';
                # 查询规格数据。
                # $size_data = M('orders_size')->where($where)->limit($limit)->order($order)->select();
                $size_data = M('orders_size')->field('good_id, sum(num) as sum,count(id) as count,color, size, weight')->where($where)->limit($limit)->group('good_id, color,size, weight')->select();
                $type = 1;
            }else{
                # 先查询总数(一个大坑，使用group再使用count()方法是不准确的。)
                $count = M('orders_size')->field('good_id, sum(num) as count, color, size, weight')->where($where)->group('good_id')->select();
                $count = count($count);
                $page = show_page($count, 20);
                $limit = $page->firstRow . ',' . $page->listRows;
                $order = 'id desc';
                # 查询规格数据。
                # $size_data = M('orders_size')->where($where)->limit($limit)->order($order)->select();
                $size_data = M('orders_size')->field('good_id, sum(num) as sum, count(id) as count,color, size, weight')->where($where)->limit($limit)->group('good_id')->select();
                $type = 2;
            }
            $admin_list_data[$key]['data'] = $size_data;
            $admin_list_data[$key]['count'] = $size_data[0]['count'] ? $size_data[0]['count'] : 0;
            $admin_list_data[$key]['sum'] = $size_data[0]['sum'] ? $size_data[0]['sum'] : 0;
            $admin_list_data[$key]['admin_id'] = $val['admin_id'];
            $admin_list_data[$key]['admin_name'] = $val['admin_name'];
        }
        if($count){
            # 查询商品编号
            $good_ids = array();
            foreach ($size_data as $k=>$v){
                $good_ids[] = $v['good_id'];
            }
            $good_ids = array_unique($good_ids);
            $w2['id'] = array('in',$good_ids);
            $number_data = M('goods')->field("id, goods_number")->where($w2)->select();

            foreach ($number_data as $key=>$val){
                foreach ($size_data as $k=>$v){
                    if($val['id'] == $v['good_id']){
                        $size_data[$k]['goods_number'] = $val['goods_number'];
                    }
                }
            }

            # 查询订单编号
            /*$w3['good_id'] = array('in',$good_ids);
            $order_data = M('orders')->field("id, good_id, order_id")->where($w3)->select();

            foreach ($order_data as $key=>$val){
                foreach ($size_data as $k=>$v){
                    if($val['good_id'] == $v['good_id']){
                        $size_data[$k]['order_id'] = $val['order_id'];
                    }
                }
            }*/
        }
        # 推广人员(admin_type=2的)
        $where = array();
        $where['admin_type'] = 2;
        $admin_list = M('admin')->field('admin_id, admin_name')->where($where)->select();
        $this->assign('type',$type);
        $this->assign('count',$count);
        $this->assign('time_area',$area);
        $this->assign('statue',$where['statue']);
        $this->assign('keyword',$keyword);
        $this->assign('page',$page->show());
        $this->assign('list',$size_data);
        $this->assign('admin_list',$admin_list);
        $this->assign('admin_list_data',$admin_list_data);
        $this->assign('admin_list_id',$admin_id);
        # 头部栏识别
        $this->assign('mark',2);
        $this->display();
    }

	//最近订单
	public function last(){
		$keyword = I('get.keyword');
		$lastMin = date( 'Y-m-d H:i:s', strtotime('-10 day',time()) );
		$where['o.create_at'] = array('gt',$lastMin);
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$statue = I('get.statue') ? I('get.statue') : 0;
		if ($statue) {
			$where['o.statue'] = $statue;
		}
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_title,o.remark,g.admin_id';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();

		# 查询投放人名称
        $admin_data = M('admin')->field('admin_id, admin_name')->select();

		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];

            //合并管理员名称
            foreach($admin_data as $admin_val){
                if($v['admin_id'] == $admin_val['admin_id']){
                    $list[$k]['admin_name'] = $admin_val['admin_name'];
                }
            }
		}

		$this->assign('time_area',$area);
		$this->assign('statue',$statue);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	//待付款订单
	public function nopay(){
		$keyword = I('get.keyword');
		$where['statue'] = 1;
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_name';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
		}
		$this->assign('time_area',$area);
		$this->assign('statue',$where['statue']);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
   	//待发货订单
	public function nosend(){
		$keyword = I('get.keyword');
		$where['statue'] = 2;
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_name';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
		}
		$this->assign('time_area',$area);
		$this->assign('statue',$where['statue']);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	//待退货退款订单
	public function norefund(){
		$keyword = I('get.keyword');
		$where['statue'] = 7;
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_name';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
		}
		$this->assign('time_area',$area);
		$this->assign('statue',$where['statue']);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	//最近收入
	public function income(){
		$lastMin = date( 'Y-m-d H:i:s', strtotime('-7 day',time()) );
		$where['o.statue'] = 9;
		$where['o.statue'] = array('in','2,3,5,6,9');
		$where['o.create_at'] = array('gt',$lastMin);
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.statue,o.create_at,g.goods_trprice,g.goods_twprice,g.goods_price,g.goods_istuan,g.goods_country';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->order($order)->select();
//		echo M()->getLastSql();var_dump($list);die;
		$time = $data = array();
		$j = 0;
		for($i=7;$i>=0;$i--){
			$time[$j] = date( 'Y-m-d H:i:s', strtotime('-'.$i.' day',time()) );
			$j++;
		}
		$cn = array(0,0,0,0,0,0,0);
		$uk = array(0,0,0,0,0,0,0);
		foreach($list as $k=>$v){
			for($w=0;$w<7;$w++){
				if($v['create_at'] > $time[$w] && $v['create_at'] < $time[$w+1]){
					if($v['goods_country'] == 'CN'){
						if($v['goods_istuan']){
							$cn[$w] += $v['goods_twprice'] + $v['goods_price'];
						}else{
							$cn[$w] += $v['goods_trprice'] + $v['goods_price'];
						}
					}
					if($v['goods_country'] == 'UK'){
						if($v['goods_istuan']){
							$uk[$w] += $v['goods_twprice'] + $v['goods_price'];
						}else{
							$uk[$w] += $v['goods_trprice'] + $v['goods_price'];
						}
					}
				}
			}
		}
		array_pop($time);
		foreach($time as $kkk=>$vvv){
			$key[] = substr($vvv,5,5);
		}
		$key = json_encode($key);
		$cn = json_encode($cn);
		$uk = json_encode($uk);
		$this->assign('key',$key);
		$this->assign('cn',$cn);
		$this->assign('uk',$uk);
		$this->assign('list',$list);
		$this->display();
	}
	//最近收入
	public function income_bak(){
		$keyword = I('get.keyword');
		$lastMin = date( 'Y-m-d H:i:s', strtotime('-7 day',time()) );
		$where['o.statue'] = 9;
		$where['o.create_at'] = array('gt',$lastMin);
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_name';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
		}
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	//订单趋势
	public function trend(){
		$where['o.statue'] = 9;
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
		$table 	= 'pt_orders o';
		$order 	= 'o.create_at asc,o.id asc';
		$list 	= M()->table($table)->where($where)->order($order)->select();
		$data = array();
		foreach($list as $k=>$v){
			$item = substr($v['create_at'],0,10);
			if($data[$item]){
				$data[$item] += 1;
			}else{
				$data[$item] = 1;
			}
		}
		$key = json_encode(array_keys($data));
		$val = json_encode(array_values($data));
		$this->key = $key;
		$this->val = $val;
		$this->assign('time_area',$area);
		$this->assign('list',$list);
		$this->display();
	}
	//订单趋势
	public function trend_bak(){
		$keyword = I('get.keyword');
		$lastMin = date( 'Y-m-d H:i:s', strtotime('-30 day',time()) );
		$where['o.statue'] = 9;
		$where['o.create_at'] = array('gt',$lastMin);
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_name';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
		}
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	//订单来源
    public function source(){
		$keyword = I('get.keyword');
		$lastMin = date( 'Y-m-d H:i:s', strtotime('-30 day',time()) );
		$where['o.statue'] = 9;
		$where['o.create_at'] = array('gt',$lastMin);
		if ($keyword) {
			$where['g.goods_name'] = array('like','%' . $keyword . '%');
		}
		$area = I('get.time_area');
		if($area){
			$times = explode('~',$area);
			$start_at = $times[0];
			$end_at = $times[1];
			$where['o.create_at'] = array('between',"{$start_at},{$end_at}");
		}
		$count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;
		$table 	= 'pt_orders o';
		$join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
		$field 	= 'o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_name';
		$order 	= 'o.id desc';
		$list 	= M()->table($table)->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$userInfo = M('member')->field('phone,username,address')->find($v['user_id']);
			$list[$k]['phone'] = $userInfo['phone'];
			$list[$k]['username'] = $userInfo['username'];
			$list[$k]['address'] = $userInfo['address'];
		}
		$this->assign('time_area',$area);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}




	public function del(){
	//编辑订单状态
		$id = I('get.id');
		$user_id = I('get.user_id');
		$r = I('r');

		# 查询订单数据。
        $model = new \Think\Model();
        // 启动事务
        $res = 0;
        $model->startTrans();
        try{
            $res = M('orders')->delete($id);
            $res = M('member')->delete($user_id);
            $res = M('orders_size')->where('order_id=%d and user_id=%d',array($id, $user_id))->delete();
            // 提交事务
            $model->commit();
            $res = 1;
        } catch (\Exception $e) {
            // 回滚事务
            $model->rollback();
        }

		if($res){
			$this->success('订单删除成功',$r);
		}else{
			$this->error('订单删除失败',$r);
		}

	}

    # 导出excel
    public function export(){

        $keyword = I('get.keyword');
        $where['statue'] = 0;
        $order_ids = array();
        if ($keyword) {
            $where['statue'] = 10;
            # 先查询订单id
            $w['goods_number'] = array('like','%' . $keyword . '%');
            $good_data = M('goods')->field("id, goods_number")->where($w)->select();
            if($good_data){
                foreach ($good_data as $val){
                    $order_ids[] = $val['id'];
                    $where['good_id'] = array('in', $order_ids);
                }
            }else{
                print_r("商品编号不存在！");
            }

        }

        $area = I('get.time_area');
        if($area){
            $times = explode('~',$area);
            $start_at = $times[0];
            $end_at = $times[1];
            $where['add_time'] = array('between',"{$start_at},{$end_at}");
        }
        $admin_id = I('get.admin_id');
        if ($admin_id) {
            $where['admin_id'] = $admin_id;

        }
        # 先查询总数(一个大坑，使用group再使用count()方法是不准确的。)
        $count = M('orders_size')->field('good_id, sum(num) as count, color, size, weight')->where($where)->group('good_id, color,size, weight')->select();
        $count = count($count);
        $order = 'id desc';
        # 查询规格数据。
        # $size_data = M('orders_size')->where($where)->limit($limit)->order($order)->select();
        $size_data = M('orders_size')->field('good_id, sum(num) as sum,count(id) as count,color, size, weight')->where($where)->group('good_id, color,size, weight')->select();
        $type = 1;

        if($count){
            # 查询订单编号
            $good_ids = array();
            foreach ($size_data as $k=>$v){
                $good_ids[] = $v['good_id'];
            }
            $good_ids = array_unique($good_ids);
            $w2['id'] = array('in',$good_ids);
            $number_data = M('goods')->field("id, goods_number, goods_purchase_url")->where($w2)->select();

            foreach ($number_data as $key=>$val){
                foreach ($size_data as $k=>$v){
                    if($val['id'] == $v['good_id']){
                        $size_data[$k]['goods_number'] = $val['goods_number'];
                        $size_data[$k]['goods_purchase_url'] = $val['goods_purchase_url'];
                    }
                }
            }
        }

        /*$this->assign('type',$type);
        $this->assign('count',$count);
        $this->assign('time_area',$area);
        $this->assign('statue',$where['statue']);
        $this->assign('keyword',$keyword);
        $this->assign('page',$page->show());
        $this->assign('list',$size_data);*/

        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);

        $writer = new \Org\Excel\xlsxwriter();

        $filename = "采购".date("Ymd",time())."-".rand(100,999).".xlsx";
        header('Content-disposition: attachment; filename="'.$writer::sanitize_filename($filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        /*$rows = array(
            array('2003','1','-50.5','2010-01-01 23:00:00','2012-12-31 23:00:00'),
            array('2003','=B1', '23.5','2010-01-01 00:00:00','2012-12-31 00:00:00'),
        );*/
        $rows = array();
        $rows[] = array("SKU编号", "采购个数", "颜色", "尺寸", "采购日期", "采购链接");
        foreach ($size_data as $key=>$val){
            $val['goods_purchase_url'] = htmlspecialchars_decode(html_entity_decode($val['goods_purchase_url']));
            # 去掉html标签
            $val['goods_purchase_url'] = strip_tags($val['goods_purchase_url']);
            $rows[] = array($val['goods_number'],$val['count'],$val['color'],$val['size'],$area,$val['goods_purchase_url']);
        }
        $writer->setAuthor('Some Author');
        foreach($rows as $row)
            $writer->writeSheetRow('Sheet1', $row);
            $writer->writeToStdOut();
        exit(0);
    }

    # 物流1
    public function export_logistics(){
        $db = M('orders');
        $keyword = I('get.keyword') ? I('get.keyword') : '';
        $keyword_num = I('get.keyword_num') ? I('get.keyword_num') : '';
        if ($keyword) {
            $where['g.goods_title'] = array('like','%' . $keyword . '%');
        }

        # 查询商品编号
        if ($keyword_num) {
            $where['g.goods_number'] = array('like','%' . $keyword_num . '%');
        }

        $tuanTime = date('Y-m-d H:i:s',time()-300);
        $statue = I('get.statue') ? I('get.statue') : '';
        if ($statue) {
            /*if($statue == 10){*/
            # 可能之前团购的状态是10，现在改成11，因为货到付款是10了。
            if($statue == 11){
                $where['o.create_at'] = array('gt',$tuanTime);
            }else{
                $where['o.statue'] = $statue;
            }
        }
        $area = I('get.time_area');
        if($area){
            $times = explode('~',$area);
            $start_at = $times[0];
            $end_at = $times[1];
            $where['o.create_at'] = array('between',"{$start_at},{$end_at}");
        }
        $admin_id = I('get.admin_id');
        if($admin_id){
            $where['o.admin_id'] = $admin_id;
        }

        $count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
        $table 	= 'pt_orders o';
        $join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
        $field 	= 'o.good_id,o.money, o.order_id,o.good_count,o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_title,o.create_at,o.remark,g.admin_id,g.goods_number';
        $order 	= 'o.id desc';
        $list 	= M()->table($table)->join($join)->where($where)->field($field)->order($order)->select();

        # 查询投放人名称
        $order_ids = array();
        $good_ids = array();
        $admin_data = M('admin')->field('admin_id, admin_name')->select();
        foreach($list as $k=>$v){
            $userInfo = M('member')->field('phone,username,address,code')->find($v['user_id']);
            $list[$k]['phone'] = $userInfo['phone'];
            $list[$k]['username'] = $userInfo['username'];
            $list[$k]['address'] = $userInfo['address'];
            $list[$k]['code'] = $userInfo['code'];
            //合并管理员名称
            foreach($admin_data as $admin_val){
                if($v['admin_id'] == $admin_val['admin_id']){
                    $list[$k]['admin_name'] = $admin_val['admin_name'];
                }
            }


        }

        $list_new = array();
        foreach($list as $v){
            //获取订单id
            $order_ids[] = $v['id'];
            $good_ids[] = $v['good_id'];
            $list_new[$v['id']] = $v;
        }
        # 查询规格信息。
        $where = array();
        $where['order_id'] = array('in', $order_ids);
        $size_data = M('orders_size')->field('order_id, color, size, weight')->where($where)->select();
        if($size_data){
            foreach ($size_data as $v){
                # 合并规格信息
                $list_new[$v['order_id']]['size_data'] = $v['color'] . ' ' . $v['size'] . ' ' . $v['weight'];
            }
        }


        # 查询采购额外信息。
        $where = array();
        $where['good_id'] = array('in', $good_ids);
        $property_data = M('goods_property')->where($where)->select();
        if($property_data){
            foreach ($property_data as $val){
                foreach ($list_new as $v){
                    if($val['good_id'] == $v['good_id']){
                        # 合并规格信息
                        $list_new[$v['id']]['declared_pcs'] = $val['declared_pcs'];
                        $list_new[$v['id']]['declared_value'] = $val['declared_value'];
                        $list_new[$v['id']]['description_english'] = $val['description_english'];
                        $list_new[$v['id']]['description_chinese'] = $val['description_chinese'];
                        $list_new[$v['id']]['is_sensitive'] = $val['is_sensitive'];
                        $list_new[$v['id']]['category'] = $val['category'];
                    }

                }
            }
        }

        # 导出操作

        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);

        $writer = new \Org\Excel\xlsxwriter();

        $filename = "物流DPE上传表格".date("Ymd",time())."-".rand(100,999).".xlsx";
        header('Content-disposition: attachment; filename="'.$writer::sanitize_filename($filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        /*$rows = array(
            array('2003','1','-50.5','2010-01-01 23:00:00','2012-12-31 23:00:00'),
            array('2003','=B1', '23.5','2010-01-01 00:00:00','2012-12-31 00:00:00'),
        );*/
        $rows = array();
        # 设置颜色#ffff00
        $rowstyle = array();
        $rows[] = array("S/No", "Consignee", "Cnee Add1", "Cnee Add2", "Cnee Country(马来西亚MY，新加坡SG)", "Cnee Postcode",
    "Cnee ISO Code","Cnee Contact","Cnee Tel","Pcs", "Weight (gm)", "Description of Goods1 - English", "Size of Good1 - English",
            "Description of Goods1 - Chinese", "Size of Good1 - Chinese", "Declared Currency","Declared Pcs", "Declared Value",
            "Shipper - English(留空时为网站客户的对应信息）","Shipper - Chinese(留空时为网站客户的对应信息）", "Shipper Add1(留空时为网站客户的对应信息）",
            "Shipper Add2(留空时为网站客户的对应信息）", "Shipper City(留空时为网站客户的对应信息）", "Shipper State(留空时为网站客户的对应信息）",
            "Shipper Postcode(留空时为网站客户的对应信息）","Shipper ISO Code(留空时为网站客户的对应信息）",  "Shipper Contact(留空时为网站客户的对应信息）", "Shipper Email(留空时为网站客户的对应信息）",
            "渠道(输入规则：PW for POSLAJU西马;PE for POSLAJU东马;S for SKYNET;A for ABX;G for GDEX;DL for 新加坡dragonlink;NV for 新加坡NINJIA;NM for 西马NINJIA;NM6 for 西马敏感NINJIA;CS for SKYNET-COD;SE for Soonest;CX for GDEX-COD;DHL for DHL)",
            "COD(到付金额)", "属性（0-普货 1-敏感货)", "客户订单号", "货物分类(A-衣服 B-电子 C-鞋子 D-箱包 E-杂货)","产品编码（SKU）","订单留言");

        foreach ($list_new as $key=>$val){
            $val['goods_purchase_url'] = htmlspecialchars_decode(html_entity_decode($val['goods_purchase_url']));
            # 去掉html标签
            $val['goods_purchase_url'] = strip_tags($val['goods_purchase_url']);
            # 件数根据用户购买数量来决定
            $num = $val['declared_pcs'] ? ($val['declared_pcs']*$val['good_count']) : $val['good_count'];
            $rows[] = array($val['id'],$val['username'],$val['address'],"","MY",$val['code'],"", "", $val['phone'],
                "1", "0", $val['description_english'], $val['size_data'], $val['description_chinese'], "", "USD", $num, $val['declared_value'],
                "Voling", "", "", "", "", "", "", "","", "", "NM", $val['money'], $val['is_sensitive'], $val['order_id']."\t",
                $val['category'], $val['goods_number'], $val['remark']);
           /* if(in_array($i, array(1, 2, 3, 5, 6, 9, 10, 12, 13, 14, 15, 26, 27, 28, 29, 30))){
                $rowstyle[] = array('fill'=>"#ffff00");
            }
            $i ++;*/
        }

        # 染色--todo
        /*$i = 1;
        $j = 1;
        $arr = array(1, 2, 3, 5, 6, 9, 10, 12, 13, 14, 15, 26, 27, 28, 29, 30);
        foreach($rows as $row){
            foreach ($row as $r)
                if((in_array($j, $arr))){
                    $rowstyle[] = array('fill'=>"#ffff00");
                }else{
                    $rowstyle[] = array('fill'=>"");
                }
                $j++;

            $i++;
        }*/
        $writer->setAuthor('Some Author');
        foreach($rows as $key=>$row)

            $writer->writeSheetRow('Sheet1', $row, $rowstyle);
        $writer->writeToStdOut();
        exit(0);

    }

    # 物流2(导出第二个物流的Excel信息，这个物流已经不用了。)
    public function export_logistics2(){
        $db = M('orders');
        $keyword = I('get.keyword') ? I('get.keyword') : '';
        $keyword_num = I('get.keyword_num') ? I('get.keyword_num') : '';
        if ($keyword) {
            $where['g.goods_title'] = array('like','%' . $keyword . '%');
        }

        # 查询商品编号
        if ($keyword_num) {
            $where['g.goods_number'] = array('like','%' . $keyword_num . '%');
        }

        $tuanTime = date('Y-m-d H:i:s',time()-300);
        $statue = I('get.statue') ? I('get.statue') : '';
        if ($statue) {
            /*if($statue == 10){*/
            # 可能之前团购的状态是10，现在改成11，因为货到付款是10了。
            if($statue == 11){
                $where['o.create_at'] = array('gt',$tuanTime);
            }else{
                $where['o.statue'] = $statue;
            }
        }
        $area = I('get.time_area');
        if($area){
            $times = explode('~',$area);
            $start_at = $times[0];
            $end_at = $times[1];
            $where['o.create_at'] = array('between',"{$start_at},{$end_at}");
        }
        $admin_id = I('get.admin_id');
        if($admin_id){
            $where['o.admin_id'] = $admin_id;
        }

        $count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
        $table 	= 'pt_orders o';
        $join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
        $field 	= 'o.good_id,o.money, o.order_id,o.good_count,o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_title,o.create_at,o.remark,g.admin_id,g.goods_number';
        $order 	= 'o.id desc';
        $list 	= M()->table($table)->join($join)->where($where)->field($field)->order($order)->select();

        # 查询投放人名称
        $order_ids = array();
        $good_ids = array();
        $admin_data = M('admin')->field('admin_id, admin_name')->select();
        foreach($list as $k=>$v){
            $userInfo = M('member')->field('phone,username,address,code')->find($v['user_id']);
            $list[$k]['phone'] = $userInfo['phone'];
            $list[$k]['username'] = $userInfo['username'];
            $list[$k]['address'] = $userInfo['address'];
            $list[$k]['code'] = $userInfo['code'];
            //合并管理员名称
            foreach($admin_data as $admin_val){
                if($v['admin_id'] == $admin_val['admin_id']){
                    $list[$k]['admin_name'] = $admin_val['admin_name'];
                }
            }


        }

        $list_new = array();
        foreach($list as $v){
            //获取订单id
            $order_ids[] = $v['id'];
            $good_ids[] = $v['good_id'];
            $list_new[$v['id']] = $v;
        }
        # 查询规格信息。
        $where = array();
        $where['order_id'] = array('in', $order_ids);
        $size_data = M('orders_size')->field('order_id, color, size, weight')->where($where)->select();
        if($size_data){
            foreach ($size_data as $v){
                # 合并规格信息
                $list_new[$v['order_id']]['size_data'] = $v['color'] . ' ' . $v['size'] . ' ' . $v['weight'];
            }
        }


        # 查询采购额外信息。
        $where = array();
        $where['good_id'] = array('in', $good_ids);
        $property_data = M('goods_property')->where($where)->select();
        if($property_data){
            foreach ($property_data as $val){
                foreach ($list_new as $v){
                    if($val['good_id'] == $v['good_id']){
                        # 合并规格信息
                        $list_new[$v['id']]['declared_pcs'] = $val['declared_pcs'];
                        $list_new[$v['id']]['declared_value'] = $val['declared_value'];
                        $list_new[$v['id']]['description_english'] = $val['description_english'];
                        $list_new[$v['id']]['description_chinese'] = $val['description_chinese'];
                        $list_new[$v['id']]['is_sensitive'] = $val['is_sensitive'];
                        $list_new[$v['id']]['category'] = $val['category'];
                    }

                }
            }
        }

        # 导出操作

        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);

        $writer = new \Org\Excel\xlsxwriter();

        $filename = "采购二".date("Ymd",time())."-".rand(100,999).".xlsx";
        header('Content-disposition: attachment; filename="'.$writer::sanitize_filename($filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        /*$rows = array(
            array('2003','1','-50.5','2010-01-01 23:00:00','2012-12-31 23:00:00'),
            array('2003','=B1', '23.5','2010-01-01 00:00:00','2012-12-31 00:00:00'),
        );*/
        $rows = array();
        # 设置颜色#ffff00
        $rowstyle = array();
        /*$rows[] = array("S/No", "Consignee", "Cnee Add1", "Cnee Add2", "Cnee Country(马来西亚MY，新加坡SG)", "Cnee Postcode",
            "Cnee ISO Code","Cnee Contact","Cnee Tel","Pcs", "Weight (gm)", "Description of Goods1 - English", "Size of Good1 - English",
            "Description of Goods1 - Chinese", "Size of Good1 - Chinese", "Declared Currency","Declared Pcs", "Declared Value",
            "Shipper - English(留空时为网站客户的对应信息）","Shipper - Chinese(留空时为网站客户的对应信息）", "Shipper Add1(留空时为网站客户的对应信息）",
            "Shipper Add2(留空时为网站客户的对应信息）", "Shipper City(留空时为网站客户的对应信息）", "Shipper State(留空时为网站客户的对应信息）",
            "Shipper Postcode(留空时为网站客户的对应信息）","Shipper ISO Code(留空时为网站客户的对应信息）",  "Shipper Contact(留空时为网站客户的对应信息）", "Shipper Email(留空时为网站客户的对应信息）",
            "渠道(输入规则：PW for POSLAJU西马;PE for POSLAJU东马;S for SKYNET;A for ABX;G for GDEX;DL for 新加坡dragonlink;NV for 新加坡NINJIA;NM for 西马NINJIA;NM6 for 西马敏感NINJIA;CS for SKYNET-COD;SE for Soonest;CX for GDEX-COD;DHL for DHL)",
            "COD(到付金额)", "属性（0-普货 1-敏感货)", "客户订单号", "货物分类(A-衣服 B-电子 C-鞋子 D-箱包 E-杂货)","产品编码（SKU）");

        foreach ($list_new as $key=>$val){
            $val['goods_purchase_url'] = htmlspecialchars_decode(html_entity_decode($val['goods_purchase_url']));
            # 去掉html标签
            $val['goods_purchase_url'] = strip_tags($val['goods_purchase_url']);
            $rows[] = array($val['id'],$val['username'],$val['address'],"","MY",$val['code'],"", "", $val['phone'],
                "1", "0", $val['description_english'], $val['size_data'], $val['description_chinese'], "", "USD", $val['declared_pcs'], $val['declared_value'],
                "Voling", "", "", "", "", "", "", "","", "", "NM", $val['money'], $val['is_sensitive'], $val['order_id']."\t",
                $val['category'], $val['goods_number']);
            /* if(in_array($i, array(1, 2, 3, 5, 6, 9, 10, 12, 13, 14, 15, 26, 27, 28, 29, 30))){
                 $rowstyle[] = array('fill'=>"#ffff00");
             }
             $i ++;
        }*/

        $rows[] = array("运单", "转单号", "订单号", "类别", "件数", "出货渠道",
        "实际重量","收货人/公司","收货人","收货人电话", "收货人地址1", "收货人地址2", "收货人地址3",
        "收货人城市", "电子邮箱", "邮政编码","目的地", "C.O.D",
        "货币类型","关税", "备注",
        "材积1", "材积2", "中文品名1",
        "英文品名1","数量1",  "申报价值1", "海关编码1",
        "用途1",
        "中文品名2", "英文品名2", "申报价值2", "海关编码2","用途2");

        foreach ($list_new as $key=>$val){
        $val['goods_purchase_url'] = htmlspecialchars_decode(html_entity_decode($val['goods_purchase_url']));
            # 去掉html标签
        $val['goods_purchase_url'] = strip_tags($val['goods_purchase_url']);
        # 敏感货物出货渠道不一样。#如果填0，就输出ECOM-GMS-P 如果填1，就输出ECOM-GMS-DM

        $type = $val['is_sensitive'] && $val['is_sensitive'] == 1 ? 'ECOM-GMS-DM' : 'ECOM-GMS-P';
        # 件数根据用户购买数量来决定
        $num = $val['declared_pcs'] ? ($val['declared_pcs']*$val['good_count']) : $val['good_count'];
        $rows[] = array("","",$val['order_id']."\t","包裹","1",$type,"1.00",$val['username'],$val['username'],$val['phone'],
            $val['address'],"","","","",$val['code'],"West Malaysia",$val['money'],"MYR","","","10*10*10*2","",
            $val['description_chinese'],$val['description_english'],$num,$val['declared_value'],
        "","","","","","","");

        }

        # 染色--todo
        /*$i = 1;
        $j = 1;
        $arr = array(1, 2, 3, 5, 6, 9, 10, 12, 13, 14, 15, 26, 27, 28, 29, 30);
        foreach($rows as $row){
            foreach ($row as $r)
                if((in_array($j, $arr))){
                    $rowstyle[] = array('fill'=>"#ffff00");
                }else{
                    $rowstyle[] = array('fill'=>"");
                }
                $j++;

            $i++;
        }*/
        $writer->setAuthor('Some Author');
        foreach($rows as $key=>$row)

            $writer->writeSheetRow('Sheet1', $row, $rowstyle);
        $writer->writeToStdOut();
        exit(0);

    }

    # 导出物流DPE重发订单表格（20180712）
    public function export_logistics3(){
        $db = M('orders');
        $keyword = I('get.keyword') ? I('get.keyword') : '';
        $keyword_num = I('get.keyword_num') ? I('get.keyword_num') : '';
        if ($keyword) {
            $where['g.goods_title'] = array('like','%' . $keyword . '%');
        }

        # 查询商品编号
        if ($keyword_num) {
            $where['g.goods_number'] = array('like','%' . $keyword_num . '%');
        }

        $tuanTime = date('Y-m-d H:i:s',time()-300);
        $statue = I('get.statue') ? I('get.statue') : '';
        if ($statue) {
            /*if($statue == 10){*/
            # 可能之前团购的状态是10，现在改成11，因为货到付款是10了。
            if($statue == 11){
                $where['o.create_at'] = array('gt',$tuanTime);
            }else{
                $where['o.statue'] = $statue;
            }
        }
        $area = I('get.time_area');
        if($area){
            $times = explode('~',$area);
            $start_at = $times[0];
            $end_at = $times[1];
            $where['o.create_at'] = array('between',"{$start_at},{$end_at}");
        }
        $admin_id = I('get.admin_id');
        if($admin_id){
            $where['o.admin_id'] = $admin_id;
        }

        $count 	= M('orders o')->join('pt_goods g on o.good_id=g.id')->where($where)->count();
        $table 	= 'pt_orders o';
        $join 	= array('LEFT JOIN pt_goods g on o.good_id=g.id');
        $field 	= 'o.good_id,o.money, o.order_id,o.good_count,o.id,o.pw_info,o.wl_info,o.from,o.statue,o.create_at,o.user_id,g.goods_title,o.create_at,o.remark,g.admin_id,g.goods_number';
        $order 	= 'o.id desc';
        $list 	= M()->table($table)->join($join)->where($where)->field($field)->order($order)->select();

        # 查询投放人名称
        $order_ids = array();
        $good_ids = array();
        $admin_data = M('admin')->field('admin_id, admin_name')->select();
        foreach($list as $k=>$v){
            $userInfo = M('member')->field('phone,username,address,code')->find($v['user_id']);
            $list[$k]['phone'] = $userInfo['phone'];
            $list[$k]['username'] = $userInfo['username'];
            $list[$k]['address'] = $userInfo['address'];
            $list[$k]['code'] = $userInfo['code'];
            //合并管理员名称
            foreach($admin_data as $admin_val){
                if($v['admin_id'] == $admin_val['admin_id']){
                    $list[$k]['admin_name'] = $admin_val['admin_name'];
                }
            }


        }

        $list_new = array();
        foreach($list as $v){
            //获取订单id
            $order_ids[] = $v['id'];
            $good_ids[] = $v['good_id'];
            $list_new[$v['id']] = $v;
        }
        # 查询规格信息。
        $where = array();
        $where['order_id'] = array('in', $order_ids);
        $size_data = M('orders_size')->field('order_id, color, size, weight')->where($where)->select();
        if($size_data){
            foreach ($size_data as $v){
                # 合并规格信息
                $list_new[$v['order_id']]['size_data'] = $v['color'] . ' ' . $v['size'] . ' ' . $v['weight'];
            }
        }


        # 查询采购额外信息。
        $where = array();
        $where['good_id'] = array('in', $good_ids);
        $property_data = M('goods_property')->where($where)->select();
        if($property_data){
            foreach ($property_data as $val){
                foreach ($list_new as $v){
                    if($val['good_id'] == $v['good_id']){
                        # 合并规格信息
                        $list_new[$v['id']]['declared_pcs'] = $val['declared_pcs'];
                        $list_new[$v['id']]['declared_value'] = $val['declared_value'];
                        $list_new[$v['id']]['description_english'] = $val['description_english'];
                        $list_new[$v['id']]['description_chinese'] = $val['description_chinese'];
                        $list_new[$v['id']]['is_sensitive'] = $val['is_sensitive'];
                        $list_new[$v['id']]['category'] = $val['category'];
                    }

                }
            }
        }

        # 导出操作

        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);

        $writer = new \Org\Excel\xlsxwriter();

        $filename = "物流DPE重发订单".date("Ymd",time())."-".rand(100,999).".xlsx";
        header('Content-disposition: attachment; filename="'.$writer::sanitize_filename($filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        /*$rows = array(
            array('2003','1','-50.5','2010-01-01 23:00:00','2012-12-31 23:00:00'),
            array('2003','=B1', '23.5','2010-01-01 00:00:00','2012-12-31 00:00:00'),
        );*/
        $rows = array();
        # 设置颜色#ffff00
        $rowstyle = array();

        $rows[] = array("序号", "Original CN（多个订单请用英文逗号\",\"分隔）", "Consignee", "Address", "Postal Code", "Contact No",
            "COD","派送(1-Skynet普货 2-Skynet敏感  3-NV普货 4-NV敏感 5-GDEX 6-马来-Resell 7-DHL)",
            "订单号", "备注");

        foreach ($list_new as $key=>$val){
            $val['goods_purchase_url'] = htmlspecialchars_decode(html_entity_decode($val['goods_purchase_url']));
            # 去掉html标签
            $val['goods_purchase_url'] = strip_tags($val['goods_purchase_url']);
            # 敏感货物出货渠道不一样。#如果填0，就输出ECOM-GMS-P 如果填1，就输出ECOM-GMS-DM

            $type = $val['is_sensitive'] && $val['is_sensitive'] == 1 ? 'ECOM-GMS-DM' : 'ECOM-GMS-P';
            # 件数根据用户购买数量来决定
            $num = $val['declared_pcs'] ? ($val['declared_pcs']*$val['good_count']) : $val['good_count'];
            $rows[] = array($val['id'],"",$val['username'],$val['address'],$val['code'],$val['phone'],$val['money'],
                3,$val['order_id']."\t",$val['goods_number']);

        }

        # 染色--todo
        /*$i = 1;
        $j = 1;
        $arr = array(1, 2, 3, 5, 6, 9, 10, 12, 13, 14, 15, 26, 27, 28, 29, 30);
        foreach($rows as $row){
            foreach ($row as $r)
                if((in_array($j, $arr))){
                    $rowstyle[] = array('fill'=>"#ffff00");
                }else{
                    $rowstyle[] = array('fill'=>"");
                }
                $j++;

            $i++;
        }*/
        $writer->setAuthor('Some Author');
        foreach($rows as $key=>$row)

            $writer->writeSheetRow('Sheet1', $row, $rowstyle);
        $writer->writeToStdOut();
        exit(0);

    }

    # 上传修改订单的excel数据
    public function upload_excel_EditStatus() {
        $type = 'file';
        //保存文件目录名称
        $folder = 'EditStatus_Excel';
        $item = 'upimg0';
        $name = "";
        $width = 900;
        $height = 900;
        $this->_ajaxupload($type,$folder,$item,$name,$width,$height);
    }

    # 上传修改订单的excel数据
    public function upload_excel_AddWaybill() {
        $type = 'file';
        //保存文件目录名称
        $folder = 'AddWaybill_Excel';
        $item = 'upimg1';
        $name = "";
        $width = 900;
        $height = 900;
        $this->_ajaxupload($type,$folder,$item,$name,$width,$height);
    }

    # 处理订单excel（修改订单状态）
    public function upload_excel_EditStatus_do() {
        header('Content-Type:application/json; charset=utf-8');

        # ./Uploads/Orders_Excel/5b2cb0dace147.xls
        $url = I('post.url');

        # $content = file_get_contents($url);
        # $content = mb_convert_encoding ( $content, 'UTF-8','Unicode');

        $spreadsheet = IOFactory::load($url);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $db = M('orders');
        unset($sheetData[1]);
        foreach ($sheetData as $key=>$val){
            $data = array();
            # 处理订单状态
            # 10代发货、9已完成、11拒签、3配送中、2已付款、12已重发
            # Excel表中POD状态（0为已付款,1为拒签退货到仓库，2为配送中,3为代发货,所有新订单默认为3代发货）
            $data['statue'] = 10; //默认代发货
            if((int)($val['A']) == 0){
                $data['statue'] = 2;
            }elseif((int)($val['A']) == 1){
                $data['statue'] = 11; // 11是拒签退货到仓库。
            }elseif((int)($val['A']) == 2){
                $data['statue'] = 3; // 3是配送中。
            }elseif((int)($val['A']) == 3){
                $data['statue'] = 10; // 11代发货。
            }elseif((int)($val['A']) == 4){
                $data['statue'] = 12; // 12已重发。
            }
            $where = array();
            $where['wl_info'] = $val['B'];
            $db->where($where)->save($data);
        }

        if(file_exists($url)){
            unlink($url);
        }


        $res = array('code'=>'ok','msg'=>'订单状态修改成功');
        echo json_encode($res);
    }

    # 处理订单excel（添加物流运单）
    public function upload_excel_AddWaybill_do() {
        header('Content-Type:application/json; charset=utf-8');

        # ./Uploads/Orders_Excel/5b2cb0dace147.xls
        $url = I('post.url');

        # $content = file_get_contents($url);
        # $content = mb_convert_encoding ( $content, 'UTF-8','Unicode');

        $spreadsheet = IOFactory::load($url);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $db = M('orders');
        unset($sheetData[1]);
        foreach ($sheetData as $key=>$val){
            $data = array();
            $data['wl_info'] = $val['C'];

            $data['statue'] = 10; //默认代发货
            if((int)($val['B']) == 0){
                $data['statue'] = 2;
            }elseif((int)($val['B']) == 1){
                $data['statue'] = 11; // 11是拒签退货到仓库。
            }elseif((int)($val['B']) == 2){
                $data['statue'] = 3; // 3是配送中。
            }elseif((int)($val['B']) == 3){
                $data['statue'] = 10; // 11代发货。
            }elseif((int)($val['B']) == 4){
                $data['statue'] = 12; // 12已重发。
            }

            $where = array();
            $where['id'] = $val['A'];
            $db->where($where)->save($data);
        }
        if(file_exists($url)){
            unlink($url);
        }


        $res = array('code'=>'ok','msg'=>'物流运单添加成功');
        echo json_encode($res);
    }

    # 根据excel表生成马来西亚的地区文件
    public function upload_excel_do_address() {
        header('Content-Type:application/json; charset=utf-8');
        set_time_limit(0);
        ini_set("memory_limit", "1024M");
        # ./Uploads/Orders_Excel/5b2cb0dace147.xls
        $url = I('post.url');

        # $content = file_get_contents($url);
        # $content = mb_convert_encoding ( $content, 'UTF-8','Unicode');

        $spreadsheet = IOFactory::load($url);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        unset($sheetData[1]);
        $data = array();
        foreach ($sheetData as $key=>$val){
            $data[$val['A']][strtolower(trim($val['B']))][strtolower(trim($val['D']))][$val['C']]['name'] = $val['C'];
            $data[$val['A']][strtolower(trim($val['B']))][strtolower(trim($val['D']))][$val['C']]['code'] = $val['E'];
        }


        $data_json = Json_encode($data);
        $data_json = "var address=".$data_json;

        $filename="./Address.js";
        file_put_contents($filename,$data_json);exit;

        $db = M('orders');
        foreach ($sheetData as $key=>$val){
            if($key > 1){
                $data = array();
                # 处理订单状态
                $data['statue'] = 9; //默认9是已完成
                if((int)($val['A']) == 1){
                    $data['statue'] = 11; // 11是拒签。
                }
                $where = array();
                $where['order_id'] = $val['B'];
                $db->where($where)->save($data);
            }
        }
        if(file_exists($url)){
            unlink($url);
        }


        $res = array('code'=>'ok','msg'=>'修改成功');
        echo json_encode($res);
    }
}
