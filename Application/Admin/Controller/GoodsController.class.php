<?php
namespace Admin\Controller;
use Think\Controller;

class GoodsController extends CommonController {
//商品管理
    function __construct()
    {
        parent::__construct();
        if($_SESSION['admin_name'] == 'Wuliu'){

            print_r('没有权限');exit;
        }
    }
	function index(){
	//列表页
		$keyword = I('get.keyword');
		$keyword_num = I('get.keyword_num');
		$db = M('goods');

		# 管理员数据
        $admin_data = M('admin')->field('admin_id, admin_name, admin_code')->select();
		if ($keyword) {
			$where['goods_title'] = array('like','%' . $keyword . '%');
			$wherestr['a.goods_title'] = array('like','%' . $keyword . '%');
		}
        if ($keyword_num) {
            $where['goods_number'] = array('like','%' . $keyword_num . '%');
            $wherestr['a.goods_number'] = array('like','%' . $keyword_num . '%');
        }
		$count 	= $db->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;		
		$table 	= 'pt_goods a';
		$join 	= array('LEFT JOIN pt_goods_type b on a.cate_id = b.id');
		$field 	= 'a.*,b.type_name as tname';
		$order 	= 'a.id desc';
		$list 	= M()->table($table)->join($join)->where($wherestr)->field($field)->limit($limit)->order($order)->select();
		foreach($list as $k=>$v) {
			//商品轮播图首图
			$itemImg = M('goods_image')->where('stype=1 and good_id=%d', $v['id'])->order('id asc')->limit(1)->field('image')->select();
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
            //添加订单识别码
            $code = 'default';
            if($_SESSION['admin_id']){
                $code = M('admin')->field('admin_code')->where('admin_id = %d',$_SESSION['admin_id'])->find();
            }else{
			    # 跳转到登录页
                exit;
            }
			//合并管理员名称
            foreach($admin_data as $admin_val){
                if($v['admin_id'] == $admin_val['admin_id']){
                    $list[$k]['admin_name'] = $admin_val['admin_name'];
                    $list[$k]['admin_code'] = $admin_val['admin_code'];
                }
            }

		}
		$this->assign('keyword',$keyword);
		$this->assign('keyword_num',$keyword_num);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->assign('code',$code['admin_code']);
		$this->display();
	}
		
	function add(){
	//添加页
		if(IS_POST){
			$type = 'images';
			$folder = 'Goods';
			$item = 'upimg5';
			$name = 1;
			$width = 200;
			$height = 200;
			$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
		}
		$list = $slist = '';
		$list = M('goods_type')->where('pid=0 and statue = 1')->select();

		foreach ($list as $k => $v) {
			$slist  = M('goods_type')->where('pid='.$v['id'].' and statue = 1')->select();
			$list[$k]['slist'] = $slist;
		}
		$country = M('country')->order('id desc')->select();
		$this->assign('country',$country);
		$this->assign('list',$list);
		$this->display();
	}

	function doadd(){
	//添加操作
		if(IS_POST){
			$data['goods_country'] = I('goods_country');
			# 投放人（管理员）id
			$data['admin_id'] = $_SESSION['admin_id'];
			$data['goods_title'] = I('goods_title');
			$data['goods_subtitle'] = I('goods_subtitle');
			$data['goods_number'] = I('goods_number');
			$data['video_url'] = I('video_url');
			$data['goods_toprice'] = I('goods_toprice');
			$data['goods_trprice'] = I('goods_trprice');
			$data['goods_twprice'] = I('goods_twprice');
			$data['goods_psprice'] = I('goods_psprice');
			$data['goods_purchase_url'] = I('goods_purchase_url');
			$data['cate_id'] = I('pid');
			$data['cate_id_2'] = I('pid');
			//$data['goods_sort'] = I('goods_sort');
			//$data['goods_gg'] = I('goods_gg');
			$data['goods_price'] = ceil(I('goods_price'));
			$data['goods_introduce'] = I('goods_introduce');
			$data['goods_notice'] = I('goods_notice');
			$imglist = array_filter(I('lunbo'));
			if($imglist){
				//$data['goods_imgs'] = implode(',', $imglist);
			}

			$cateId = I('pid');
            $cateInfo  = M('goods_type')->where('id='.$cateId)->find();
            $data['cate_id_1'] = $cateInfo['pid'];
			/*$data['goods_det'] = I('goods_det');*/
			$goods_det = array_filter(I('goods_det'));
			$data['addtime'] = NowTime();
			$data['edittime'] = NowTime();
			$db = M('goods');
			$db->create($data);
			$goodId = $db -> add();
//            $goodId = $db->insert_id();
            if ($goodId > 0) {
                $imgDb = M('goods_image');
                foreach ($imglist as $k=>$v) {

                    # 保存一下后缀名
                    $name_arr = explode('.', $v);
                    $postfix_name = $name_arr[count($name_arr)-1];

                    $arr = array("gif", "jpg", "jpeg", "bmp", "png");
                    if(in_array(strtolower($postfix_name), $arr)){
                        $is_img = 1;
                    }else{
                        $is_img = 2;
                    }

                    $imageData = array(
                        'good_id' => $goodId,
                        'image' => $v,
                        'is_img' => $is_img,
                        'stype' => 1,
                        'add_time' => time(),
                    );
                    $imgDb->create($imageData);
                    $res = $imgDb -> add();
                }
            }

            # 批量上传描述长图
            if ($goodId > 0) {
                $imgDb = M('goods_image');
                foreach ($goods_det as $k=>$v) {

                    # 保存一下后缀名
                    $name_arr = explode('.', $v);
                    $postfix_name = $name_arr[count($name_arr)-1];

                    $arr = array("gif", "jpg", "jpeg", "bmp", "png");
                    if(in_array(strtolower($postfix_name), $arr)){
                        $is_img = 1;
                    }else{
                        $is_img = 2;
                    }

                    $imageData = array(
                        'good_id' => $goodId,
                        'image' => $v,
                        'is_img' => $is_img,
                        'stype' => 2,  //2是描述长图的类型
                        'add_time' => time(),
                    );
                    $imgDb->create($imageData);
                    $res = $imgDb -> add();
                }
            }

            # 添加采购额外信息。

            if ($goodId > 0) {
                $data = array();
                $data['good_id'] = $goodId;
                $data['declared_pcs'] = I('declared_pcs');
                $data['declared_value'] = I('declared_value');
                $data['description_english'] = I('description_english');
                $data['description_chinese'] = I('description_chinese');
                $data['is_sensitive'] = I('is_sensitive');
                $data['category'] = I('category');
                $db = M('goods_property');
                $db->create($data);
                $db -> add();
            }


            $this->success('信息添加成功',U('Goods/index'));

		}else{
			$this->error('参数错误',U('Goods/add'));
		}

	}

	function edit(){
	//编辑页
		$id = I('id');
		$info = M('goods')->find($id);
		$info['imgs'] = array();
		if($info['goods_imgs']){
			$info['imgs'] = explode(',',$info['goods_imgs']);
		}

		//dump($info);die;
		$list = $slist = '';
        $list = M('goods_type')->where('pid=0 and statue = 1')->select();

        foreach ($list as $k => $v) {
            $slist  = M('goods_type')->where('pid='.$v['id'].' and statue = 1')->select();
            $list[$k]['slist'] = $slist;
        }

        //获取商品轮播图
        $imgDb = M('goods_image');
        $imgList = $imgDb->where('good_id=%d and sid = 0 and stype = 1',$id)->select();
        foreach ($imgList as $k=>$v) {
            $no = $k+1;
            $this->assign('imgList'.$no,$v['image']);//商品信息
        }

        //额外采购信息
        $propertyDb = M('goods_property');
        $propertyList = $propertyDb->where('good_id=%d',$id)->find();

		$country = M('country')->select();
		$this->assign('country',$country);
		$this->assign('list',$list);//分类信息
		$this->assign('info',$info);//商品信息
		$this->assign('propertyList',$propertyList);//额外采购信息
		$this->display();
	}

	function doedit(){
	//编辑操作
		$id = I('id');
		if($id){
			$where['id'] = $id;
			if(IS_POST){
                $data['goods_country'] = I('goods_country');
                $data['goods_title'] = I('goods_title');
                $data['goods_subtitle'] = I('goods_subtitle');
                $data['goods_toprice'] = I('goods_toprice');
                $data['goods_trprice'] = I('goods_trprice');
                $data['goods_twprice'] = I('goods_twprice');
                $data['goods_number'] = I('goods_number');
                $data['goods_psprice'] = I('goods_psprice');
                $data['goods_purchase_url'] = I('goods_purchase_url');
                $data['video_url'] = I('video_url');
                $data['goods_introduce'] = I('goods_introduce');
                $data['cate_id'] = I('pid');
                $data['cate_id_2'] = I('pid');
                //$data['goods_sort'] = I('goods_sort');
                //$data['goods_gg'] = I('goods_gg');
                $data['goods_price'] = ceil(I('goods_price'));
                $data['goods_notice'] = I('goods_notice');
                $imglist = array_filter(I('lunbo'));
                $cateId = I('pid');
                $cateInfo  = M('goods_type')->where('id='.$cateId)->find();
                $data['cate_id_1'] = $cateInfo['pid'];
                $data['goods_det'] = I('goods_det');
				$data['edittime'] = NowTime();
				$db = M('goods');
				$db->create($data);
				$res = $db -> where($where) ->save();
				/*if ($id > 0) {
                    $imgDb = M('goods_image');
                    $res = $imgDb->where('good_id=%d and sid = 0',$id)->delete();
				    foreach ($imglist as $k=>$v) {
				        $imageData = array(
				            'good_id' => $id,
                            'image' => $v,
                            'stype' => 1,
                            'add_time' => time(),
                        );
                        $imgDb->create($imageData);
                        $res = $imgDb -> add();
                    }
                }*/
				# 旧商品没有采购额外信息，需要先查询。
                $property_data = M('goods_property')->where('good_id = %d', $id)->find();
                $data = array();
                $where = array();
                $where['good_id'] = $id;
                $data['declared_pcs'] = I('declared_pcs');
                $data['declared_value'] = I('declared_value');
                $data['description_english'] = I('description_english');
                $data['description_chinese'] = I('description_chinese');
                $data['is_sensitive'] = I('is_sensitive');
                $data['category'] = I('category');
                $db = M('goods_property');
                if($property_data){

                        $db->create($data);
                        $db -> where($where) ->save();
                }else{
                    $data['good_id'] = $id;
                    $db->create($data);
                    $db -> add();
                }


                if ($id > 0) {
                    $data = array();
                    $where = array();
                    $where['good_id'] = $id;
                    $data['declared_pcs'] = I('declared_pcs');
                    $data['declared_value'] = I('declared_value');
                    $data['description_english'] = I('description_english');
                    $data['description_chinese'] = I('description_chinese');
                    $data['is_sensitive'] = I('is_sensitive');
                    $data['category'] = I('category');
                    $db = M('goods_property');
                    $db->create($data);
                    $db -> where($where) ->save();
                }
				$this->success('信息更新成功',U('Goods/index'));
			}else{
				$this->error('请求参数错误',U('Goods/index'));
			}
		}else{
			$this->error('缺少必要参数',U('Goods/index'));
		}

	}

    /**
     * 批量导入
     */
    function addmore()
    {
        //批量导入
        $this->display();
    }

    /**
     * 规格列表
     */
	function configlist()
    {
        $goodId = I('get.id');
        $join 	= array('LEFT JOIN pt_goods_image img on s.id = img.sid');
        $list 	= M('goods_size s')->join($join)->where('s.good_id='.$goodId)->field('s.id,s.color,s.size,s.weight,img.image,s.good_id')->order('s.id desc')->select();

        $this->assign('list',$list);
        $this->assign('goodId',$goodId);
        $this->display();
    }

    function configadd()
    {
        $goodId = I('get.goodsId');
        $this->assign('goodId',$goodId);
        $this->display();
    }

    /**
     * 执行上传商品
     */
    public function doaddmore()
    {
        //获取上传文件
        if(IS_POST){
            $file = I('goodsFile');
           // $file = './Uploads/Goods/5ad32eccbf218.csv';
            $file = fopen($file,"r");
            $data = array();
            $imageEtx = array('png','gif','jpg','jpeg');
            $i = 0;
            while(! feof($file))
            {
                $i++;
                $info = fgetcsv($file);
                $info = $this->gbktoutf8($info);
                if ($i == 1) {
                    continue;
                }
                $data[$i]['goods'] = array(
                    'goods_title' => $info[0],
                    'goods_subtitle' => $info[1],
                    'goods_toprice' => $info[2],
                    'goods_trprice' => $info[3],
                    'goods_twprice' => $info[4],
                    'goods_det' => $info[5],
                    'goods_price' => $info[6],
                    'goods_country' => $info[7],
                    'goods_istuan' => $info[8],
                    'goods_notice' => $info[9],
                    'goods_promotion' => $info[10],
                    'goods_tag' => $info[11],
                    'cate_id1' => $info[12],
                    'cate_id2' => $info[13],
                    'cate_id' => $info[13],
                    'edittime' => NowTime(),
                );

                $goods_notice_ext = pathinfo($data[$i]['goods']['goods_notice']);
                $goods_det_ext = pathinfo($data[$i]['goods']['goods_det']);

                if (!in_array($goods_det_ext['extension'],$imageEtx) || !in_array($goods_notice_ext['extension'],$imageEtx)) {
                    $num = $i-1;
                    $this->error('第'.$num.'购买须知或长描述图格式不正确',U('Goods/addmore'));
                    die();
                }


                $data[$i]['images'] = explode('@',$info[15]);

                foreach ($data[$i]['images'] as $lunboImg) {
                    $lunboImg_ext = pathinfo($lunboImg);
                    if (!in_array($lunboImg_ext['extension'],$imageEtx)) {
                        $num = $i-1;
                        $this->error('第'.$num.'条轮播图图格式不正确',U('Goods/addmore'));
                        die();
                    }
                }

                $data[$i]['size'] = array();

                $sizeInfo = $info[14];
                $sizeArr = explode('@',$sizeInfo);
                if (!empty($sizeArr)) {
                    foreach ($sizeArr as $k=>$v) {
                        if (empty($v)) continue;
                        $sizeOne = explode('|',$v);
                        if (!empty($sizeOne)) {
                            $sizeImg_ext = pathinfo($sizeOne[3]);
                            if (!in_array($sizeImg_ext['extension'],$imageEtx)) {
                                $num = $i-1;
                                $sizeK = $k+1;
                                $this->error('第'.$num.'条的第'.$sizeK.'个规格图格式不正确',U('Goods/addmore'));
                                die();
                            }

                            if (empty($sizeOne[0]) && empty($sizeOne[1]) && $sizeOne[2]) {
                                $num = $i-1;
                                $sizeK = $k+1;
                                $this->error('第'.$num.'条的第'.$sizeK.'规格数据都为空',U('Goods/addmore'));
                                die();
                            }

                            $data[$i]['size'][] = array(
                                'color' => $sizeOne[0],
                                'size' => $sizeOne[1],
                                'weight' => $sizeOne[2],
                                'add_time' => time(),
                                'image' => $sizeOne[3],
                            );
                        }
                    }
                }
            }
            $j = 0;
            if (!empty($data)) {
                $db = M('goods');
                $imgDb = M('goods_image');
                $dbsize = M('goods_size');
                foreach ($data as $k=>$v) {
                    if (empty($v)) continue;
                    $goodImg = $v['images'];
                    $goodInfo = $v['goods'];
                    $goodSize = $v['size'];

                    //添加商品
                    $db->create($goodInfo);
                    $goodId = $db -> add();
                    if ($goodId > 0) {
                        $j++;
                    } else {
                        continue;
                    }

                    //添加轮播图
                    foreach ($goodImg as $k=>$v) {
                        $v = trim($v);
                        if (empty($v)) continue;
                        $imageData = array(
                            'good_id' => $goodId,
                            'image' => $v,
                            'stype' => 1,
                            'add_time' => time(),
                        );
                        $imgDb->create($imageData);
                        $res = $imgDb -> add();
                    }

                    //添加规格
                    foreach ($goodSize as $size) {
                        if (empty($size)) continue;
                        $sizeImage = $size['image'];
                        unset($size['image']);
                        $size['good_id'] = $goodId;
                        $dbsize->create($size);
                        $sid = $dbsize->add();
                        $imageData = array(
                            'good_id' => $goodId,
                            'image' => $sizeImage,
                            'sid' => $sid,
                            'stype' => 0,
                            'add_time' => time(),
                        );
                        $imgDb->create($imageData);
                        $res = $imgDb->add();
                    }
                }
            }

            fclose($file);
        }

        if ($j > 0) {
            $this->success('成功导入'.$j.'条数据',U('Goods/index'));
        } else {
            $this->error('参数错误',U('Goods/addmore'));
        }
    }

    # 添加商品规格,支持批量添加
    function doaddconfig()
    {
		$id = I('get.goodId');
        if(IS_POST){
            # 如果是尺寸和重量有###符号就是批量添加的。60###61###62
            if(preg_match('/\#\#\#/', I('size'), $matches) && !I('image')){
                #存在多个名称
                $name_arr = explode('###', I('size'));

                foreach ($name_arr as $val){
                    $data = array();
                    $data['good_id'] = I('good_id');
                    $data['color'] = I('color');
                    $data['size'] = $val;
                    $data['weight'] = I('weight');
                    $data['add_time'] = time();
                    $db = M('goods_size');
                    $db->create($data);
                    $db -> add();
                }

            }elseif(preg_match('/\#\#\#/', I('weight'), $matches) && !I('image')){
                #存在多个名称
                $name_arr = explode('###', I('weight'));

                foreach ($name_arr as $val){
                    $data = array();
                    $data['good_id'] = I('good_id');
                    $data['color'] = I('color');
                    $data['size'] = I('size');
                    $data['weight'] = $val;
                    $data['add_time'] = time();
                    $db = M('goods_size');
                    $db->create($data);
                    $db -> add();
                }

            }else{
                $data = array();
                $data['good_id'] = I('good_id');
                $data['color'] = I('color');
                $data['size'] = I('size');
                $data['weight'] = I('weight');
                //开启令牌验证需要手动提交token，坑逼！！！--todo
                //$data['token'] = I('token');
                $data['add_time'] = time();
                $db = M('goods_size');
                $db->create($data);
                $sid = $db -> add();
                $imageData = array(
                    'good_id' => $data['good_id'],
                    'image' => I('image'),
                    'sid' => $sid,
                    'stype' => 0,
                    'add_time' => time(),
                );
                $imgDb = M('goods_image');
                $imgDb->create($imageData);
                $res = $imgDb -> add();
            }

            $this->success('信息更新成功',U('Goods/configlist',array('id'=>$data['good_id'])));

        }else{
            $this->error('参数错误',U('Goods/configlist',array('id'=>$id)));
        }
    }

    function editconfig()
    {
        $sid = I('get.id');
        $id = I('id');
        $info = M('goods_size')->find($sid);

        //dump($info);die;

        //获取商品轮播图
        $imgDb = M('goods_image');
        $imgList = $imgDb->where('sid=%d',$sid)->select();
        foreach ($imgList as $k=>$v) {
            $no = $k+1;
            $this->assign('imgList'.$no,$v['image']);//商品信息
        }
        $this->assign('info',$info);//商品信息
        $this->display();

    }

    function doeditconfig()
    {
        $id = I('id');
        $good_id = I('good_id');
        if($id){
            $where['id'] = $id;
            if(IS_POST){

                $data['good_id'] = I('good_id');
                $data['color'] = I('color');
                $data['size'] = I('size');
                $data['weight'] = I('weight');
                $data['add_time'] = time();
                $db = M('goods_size');
                $db->create($data);
                $res = $db -> where($where) ->save();
                if ($id > 0) {
                    $imgDb = M('goods_image');
                    $res = $imgDb->where('sid = %d',$id)->delete();
                        $imageData = array(
                            'good_id' => $good_id,
                            'image' => I('image'),
                            'stype' => 0,
                            'sid' => $id,
                            'add_time' => time(),
                        );
                        $imgDb->create($imageData);
                        $res = $imgDb -> add();
                }
                $this->success('信息更新成功',U('Goods/configlist',array('id'=>I('good_id'))));
            }else{
                $this->error('参数错误',U('Goods/configlist',array('id'=>I('good_id'))));
            }
        }else{
            $this->error('参数错误',U('Goods/configlist',array('id'=>I('good_id'))));
        }
    }

    //删除规格
    function delconfig()
    {
        $goodId = I('get.goodId');
        $id = I('get.id');
        if($id){
            # 1.删除规格图片
            $img_data = M('goods_image')->where('sid=%d',$id)->field('image')->select();
            try{
                //删除购买须知图片
                if(file_exists($img_data[0]['image'])){
                    unlink($img_data[0]['image']);
                }
            }catch (Exception $e){
                echo '删除文件错误: ' .$e->getMessage();
            }

            # 2. 删除图片、规格记录
            $model = new \Think\Model();

            // 启动事务
            $res = 0;
            $model->startTrans();
            try{
                M('goods_image')->where('sid=%d',$id)->delete();
                M('goods_size')->where('id=%d',$id)->delete();
                // 提交事务
                $model->commit();
                $res = 1;
            } catch (\Exception $e) {
                // 回滚事务
                $model->rollback();
            }

            if($res){
                $this->success('信息更新成功',U('Goods/configlist',array('id'=>$goodId)));
            }else{
                $this->error('删除失败',U('Goods/configlist',array('id'=>$goodId)));
            }
        }else{
            $this->error('删除失败',U('Goods/configlist',array('id'=>$goodId)));
        }
    }

	function del(){
	//删除操作
		$id = I('get.id');
		if($id){
		    # 要删除图片和相关规格记录、规格图片。

            # 1.删除商品、规格图片
            $img_data = M('goods_image')->where('good_id=%d',$id)->field('image')->select();
            $img_notic = M('goods')->where('id=%d',$id)->field('goods_notice')->select();

            try{
                foreach ($img_data as $val){
                    //删除
                    if(file_exists($val['image'])){
                        unlink($val['image']);
                    }
                }
                //删除购买须知图片
                if(file_exists($img_notic[0]['goods_notice'])){
                    unlink($img_notic[0]['goods_notice']);
                }
            }catch (Exception $e){
                echo '删除文件错误: ' .$e->getMessage();
            }

            # 2. 删除商品、图片、规格记录
            $model = new \Think\Model();

            // 启动事务
            $res = 0;
            $model->startTrans();
            try{
                # goods表的购买须知图片也要删除，goods_notice
                M('goods')->where('id=%d',$id)->delete();
                M('goods_image')->where('good_id=%d',$id)->delete();
                M('goods_size')->where('good_id=%d',$id)->delete();
                # 删除商品额外信息
                M('goods_property')->where('good_id=%d',$id)->delete();
                // 提交事务
                $model->commit();
                $res = 1;
            } catch (\Exception $e) {
                // 回滚事务
                $model->rollback();
            }

			if($res){
				$this->success('信息删除成功',U('Goods/index'));
			}else{
				$this->error('信息删除失败，没有找到该信息',U('Goods/index'));
			}
		}else{
			$this->error('缺少必要参数',U('Goods/index'));
		}
	}

	function sell(){
	//上下架操作
		$id = I('id');
		$stats = I('get.goods_stats');

		if($stats&&$id){
			$data['goods_stats'] = (int)$stats;
			$where['id']  =	 $id;
			$res = M('goods')->where($where)->save($data);
			if($res){
				$this->success('数据编辑成功',U('Goods/index'));
			}else{
				$this->error('商品状态变更失败');
			}  
		}else{
			$this->error('缺少必要参数');
		}	
	}

    public function imgup6() {
        $type = 'images';
        $folder = 'Goods';
        $item = 'upimg6';
        $name = "";
        $width = 3000;
        $height = 3000;
        $isCut = 0;   //1为居中剪裁，0为只等比缩放
        $this->_ajaxupload($type,$folder,$item,$name,$width,$height,$isCut);
    }

	public function imgup5() {
		$type = 'images';
		$folder = 'Goods';
		$item = 'upimg5';
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	public function imgup4() {
		$type = 'images';
		$folder = 'Goods';
		$item = 'upimg4';
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	public function imgup3() {
		$type = 'images';
		$folder = 'Goods';
		$item = 'upimg3';
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	public function imgup2() {
		$type = 'images';
		$folder = 'Goods';
		$item = 'upimg2';
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	public function imgup1() {
		$type = 'images';
		$folder = 'Goods';
		$item = 'upimg1';
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	public function imgup0() {
		$type = 'images';
		/*$folder = 'Goods';*/
        # 这里是上传购买须知图片的。
		$folder = 'Notices';
		$item = 'upimg0';
		$name = "";
		//取消购买须知图片裁剪
		//$width = 900;
		//$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name);
		//$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	# 这里是上传规格图的，需要裁剪。
    public function imgup_size() {
        # 修改商品规格的同时，删除原来图片和图片记录。
        $sid = I('get.sid');
        if($sid){
            $sdata = M('goods_image')->field('image')->where('sid=%d',$sid)->find();

            try{
                //删除规格图片
                if(file_exists($sdata['image'])){
                    unlink($sdata['image']);
                }

                // 启动事务
                $model = new \Think\Model();
                $model->startTrans();
                M('goods_image')->field('image')->where('sid=%d',$sid)->delete();
                // 提交事务
                $model->commit();

            }catch (Exception $e){
                // 回滚事务
                $model->rollback();
                echo '删除文件错误: ' .$e->getMessage();
            }
        }
        $type = 'images';
        $folder = 'Sizes';
        $item = 'upimg0';
        $name = "";
        $width = 300;
        $height = 300;
        $this->_ajaxupload($type,$folder,$item,$name, $width, $height);
        //$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
    }

    public function imgupcsv() {
        $type = 'file';
        $folder = 'Goods_csv';
        $item = 'upimg0';
        $name = "";
        $width = 900;
        $height = 900;
        $this->_ajaxupload($type,$folder,$item,$name,$width,$height);
    }

	// 设置推荐商品操作
	public function tj() {
		$id = I('get.id');
		if($id){
			$data['tj_sort'] = I('tj_sort');
			$data['is_tj'] = 1;
			$res = M('goods')->where('id=%d',$id)->save($data);
			if($res){
				$this->success('推荐商品成功',U('Goods/index'));
			}else{
				$this->error('推荐商品失败',U('Goods/index'));
			}
		}else{
			$this->error('请选择您要推荐的商品');
		}
	}
	//添加、编辑普通标签页面
	public function goods_tag(){
		$id = I('get.id');
		$type = I('get.type');
		$info = M('goods')->find($id);
		$tagList = explode(',',$info['goods_tag']);
		$tag = M('tag')->where('statue=1')->select();
		$this->type = $type;
		$this->info = $info;
		$this->tagList = $tagList;
		$this->tag = $tag;
		$this->display();
	}
	//添加、编辑普通标签操作
	public function goods_tag_edit(){
		$data['id'] = I('get.id');
		$type = I('get.type');
		$tag = I('tag');
		if($tag == ''){
			$data['goods_tag'] = 0;
		}else{
			$data['goods_tag'] = implode($tag,',');
		}
		$model = M('goods');
		$model->create($data);
		$res = $model->save();
		if($type==1){
			$str = '添加';
		}else{
			$str = '编辑';
		}
		if($res){
			$this->success($str.'商品标签成功',U('Goods/index'));
		}else{
			$this->error($str.'商品标签失败',U('Goods/index'));
		}
	}
	//添加、编辑拼团页面
	public function goods_istuan(){
		$id = I('get.id');
		$type = I('get.type');
		$info = M('goods')->find($id);
		$tag = M('tourdiy')->where('statue=1')->select();
		$this->type = $type;
		$this->info = $info;
		$this->tag = $tag;
		$this->display();
	}
	//添加、编辑拼团操作
	public function goods_istuan_edit(){
		$data['id'] = I('get.id');
		$type = I('get.type');
		$data['goods_istuan'] = I('tag');
		$model = M('goods');
		$model->create($data);
		$res = $model->save();
		if($type==1){
			$str = '添加';
		}else{
			$str = '编辑';
		}
		if($res){
			$this->success('商品'.$str.'拼团成功',U('Goods/index'));
		}else{
			$this->error('商品'.$str.'拼团失败',U('Goods/index'));
		}
	}
	//添加、编辑促销标签页面
	public function goods_promotion(){
		$id = I('get.id');
		$type = I('get.type');
		$info = M('goods')->find($id);
		$tag = M('promotion')->where('statue=1')->select();
		$this->type = $type;
		$this->info = $info;
		$this->tag = $tag;
		$this->display();
	}
	//添加、编辑促销标签操作
	public function goods_promotion_edit(){
		$data['id'] = I('get.id');
		$type = I('get.type');
		$data['goods_promotion'] = I('tag');
		$model = M('goods');
		$model->create($data);
		$res = $model->save();
		if($type==1){
			$str = '添加';
		}else{
			$str = '编辑';
		}
		if($res){
			$this->success('商品'.$str.'促销标签成功',U('Goods/index'));
		}else{
			$this->error('商品'.$str.'促销标签失败',U('Goods/index'));
		}
	}
    public function gbktoutf8($info){
        $data = array();
        foreach($info as $v){
            $encode = mb_detect_encoding($v, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
            $data[] = iconv($encode, 'UTF-8', $v);
        }
        return $data;
    }
    public function downFile($path = ''){
        $path = './Uploads/Goods_csv/demo.csv';
        $this->download($path);
    }

    function download($file_url,$new_name=''){

        if(!isset($file_url)||trim($file_url)==''){
            echo '500';
        }
        if(!file_exists($file_url)){ //检查文件是否存在
            echo '404';
        }
        $file_name=basename($file_url);
        $file_type=explode('.',$file_url);
        $file_type=$file_type[count($file_type)-1];
        $file_name=trim($new_name=='')?$file_name:urlencode($new_name);
        $file_type=fopen($file_url,'r'); //打开文件
        //输入文件标签

        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: ".filesize($file_url));
        header("Content-Disposition: attachment; filename=示例表格.csv");
        //输出文件内容
        echo fread($file_type,filesize($file_url));
        fclose($file_url);
        exit();
    }

    public function upload_file_more(){
        #!! 注意
        #!! 此文件只是个示例，不要用于真正的产品之中。
        #!! 不保证代码安全性。

        #!! IMPORTANT:
        #!! this file is just an example, it doesn't incorporate any security checks and
        #!! is not recommended to be used in production environment as it is. Be sure to
        #!! revise it and customize to your needs.


        // Make sure file is not cached (as it happens for example on iOS devices)
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");


        // Support CORS
        // header("Access-Control-Allow-Origin: *");
        // other CORS headers if any...
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }


        if ( !empty($_REQUEST[ 'debug' ]) ) {
            $random = rand(0, intval($_REQUEST[ 'debug' ]) );
            if ( $random === 0 ) {
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }

        // header("HTTP/1.0 500 Internal Server Error");
        // exit;


        // 5 minutes execution time
                @set_time_limit(5 * 60);

        // Uncomment this one to fake upload time
        // usleep(5000);

        // Settings
        // $targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";


        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds




        // Get a file name
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }
        # 不要原来名称，改成文件格式+随机数
        $name_arr = explode('.', $fileName);
        $fileName = $name_arr[count($name_arr)-1];
        # 改成唯一id
        $fileNameNew = time() . uniqid() . '.'.$fileName;
        # 如果是文件上传，没有后缀名或者上传不成功，可能是服务器显示上传大小的问题。
        file_put_contents("./data.txt",'$fileName'.json_encode($fileName).PHP_EOL, FILE_APPEND);
        file_put_contents("./data.txt",'$name_arr'.json_encode($name_arr).PHP_EOL, FILE_APPEND);
        file_put_contents("./data.txt",'$fileNameNew'.json_encode($fileNameNew).PHP_EOL, FILE_APPEND);


        # 批量上传目录
        $targetDir = 'upload_tmp';
        $arr = array("gif", "jpg", "jpeg", "bmp", "png");
        if(in_array(strtolower($fileName), $arr)){
            $uploadDir = 'upload/Goods';
            $path = 'Goods';
        }else{
            $uploadDir = 'upload/Videos';
            $path = 'Videos';
        }
        file_put_contents("./data.txt",'gettype($fileName)'.json_encode(gettype($fileName)).PHP_EOL, FILE_APPEND);

        file_put_contents("./data.txt",'in_array(strtolower($fileName), $arr)'.json_encode(in_array(strtolower($fileName), $arr)).PHP_EOL, FILE_APPEND);

        // Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        // Create target dir
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir);
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileNameNew;
        $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileNameNew;

        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;


        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }


        // Open temp file
        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }

        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");

        $index = 0;
        $done = true;
        for( $index = 0; $index < $chunks; $index++ ) {
            if ( !file_exists("{$filePath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }
        if ( $done ) {
            if (!$out = @fopen($uploadPath, "wb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }

            if ( flock($out, LOCK_EX) ) {
                for( $index = 0; $index < $chunks; $index++ ) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }

                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }

                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }

                flock($out, LOCK_UN);
            }
            @fclose($out);
        }

        // Return Success JSON-RPC response
        /*die('{"jsonrpc" : "2.0", "url" : "./Goods/'.$fileNameNew.'", "id" : "id"}');*/
        die("./upload/".$path."/$fileNameNew");
    }

    #采购链接界面。
    function purchase_url_view(){
        $id = I('get.id');
        $info = M('goods')->find($id);
        if($info){
            $info['goods_purchase_url'] = htmlspecialchars_decode(html_entity_decode($info['goods_purchase_url']));

        }
        $this->assign('info',$info);
        $this->display();
    }

    # 检查商品编号的唯一性
    public function checkNumber(){
        $num = I('get.number');
        $where['goods_number'] = $num;
        $info = M('goods')->where($where)->select();

        $return = array('code'=>0);
        if($info){
            $return['code'] = 1;
        }
        echo json_encode($return);
    }

    #
    function good_images_sort(){
        $id = I('get.id');
        $type = I('get.type');

        # is_sort是兼容以前没排序的商品。
        $is_sort = 0;
        if($type == 1){
            # 轮播图
            $imgList = M('goods_image')->where('good_id=%d and stype=1',$id)->select();

            if($imgList){
                foreach ($imgList as $key=>$val){
                    if($val['sort'] > 0 ){
                        $is_sort = 1;
                        break;
                    }
                }
            }
            if($is_sort){
                $imgList = M('goods_image')->where('good_id=%d and stype=1',$id)->order('sort asc')->select();
            }

        }elseif($type == 2){
            # 描述长图
            $imgList = M('goods_image')->where('good_id=%d and stype=2',$id)->select();

            if($imgList){
                foreach ($imgList as $key=>$val){
                    if($val['sort'] > 0 ){
                        $is_sort = 1;
                        break;
                    }
                }
            }
            if($is_sort){
                $imgList = M('goods_image')->where('good_id=%d and stype=2',$id)->order('sort asc')->select();
            }
        }

        $this->assign('id',$id);
        $this->assign('imgList',$imgList);
        $this->assign('imgList_num',count($imgList));
        $this->display();
    }

    function good_images_sort_do(){
        $sort_str = I('post.sort_str');
        $imgList_num = I('post.imgList_num');
        if(!$sort_str){
            $this->error('请拖动图片顺序',U('Goods/index'));
        }

        # 0-417###1-418###2-419###3-420###6-421###4-422###8-423###7-424###9-425###5-426###
        if(preg_match('/\#\#\#/', $sort_str, $matches)){
            # Array ( [0] => 0-417 [1] => 3-418 [2] => 4-419 [3] => 5-420 [4] => 6-421 [5] => 2-422 [6] => 7-423 [7] => 1-424 [8] => 8-425 [9] => 9-426 [10] => )
            $arr1 = explode('###', $sort_str);
            $imgDb = M('goods_image');
            $data = array();
            $where = array();
            foreach ($arr1 as $key=>$val){
                if($val){
                    $arr2 = explode('-', $val);
                    $data['sort'] = $arr2[0];
                    $where['id'] = $arr2[1];
                    $imgDb -> where($where) ->save($data);
                }

            }
        }
        # $return = array('code'=>1, 'sort_str'=>$sort_str, 'imgList_num'=>$imgList_num);
        # echo json_encode($return);
        $this->success('修改图片顺序成功',U('Goods/index'));
    }
}