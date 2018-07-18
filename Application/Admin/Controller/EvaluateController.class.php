<?php
namespace Admin\Controller;
use Think\Controller;
//评论管理
class EvaluateController extends CommonController {

    function __construct()
    {
        parent::__construct();
        if($_SESSION['admin_name'] == 'Wuliu'){

            print_r('没有权限');exit;
        }
    }

	// 评论列表
	public function index() {
		$db = M('orders');
		$keyword = I('get.keyword');
		if ($keyword) {
			$where['g.goods_title|g.id'] = array('like','%' . $keyword . '%');
		}
	    $statue = I('get.statue');
		if ($statue) {
			$where['e.statue'] = $statue;
		}
		$evaluate = M('evaluate e');
		$join 	= array('LEFT JOIN pt_goods g on e.goods_id=g.id');
		$count 	= $evaluate->join($join)->where($where)->count();
		$page 	= show_page($count,10);
		$limit 	= $page->firstRow.','.$page->listRows;		
		$field 	= 'e.*,g.goods_title';
		$order 	= 'e.id desc';
		$list 	= $evaluate->join($join)->where($where)->field($field)->limit($limit)->order($order)->select();
		$this->assign('statue',$statue);
		$this->assign('keyword',$keyword);
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display(); 
	}

	public function edit(){
		$id = I('get.id');
		$r = I('get.r');
		$info = M('evaluate')->find($id);
		$goodsInfo = M('goods')->where('id=%d',$info['goods_id'])->field('goods_title')->find();
		$info['goods_title'] = $goodsInfo['goods_title'];
		$this->r = $r;
		$this->info = $info;
		$this->display();
	}

	public function doedit(){
		$db = M('evaluate');
		$id = I('get.id');
		$data['username'] = I('username');
		$data['phone'] = I('phone');
		$data['level'] = I('level');
		$data['text'] = I('text');
		$data['statue'] = I('statue');
		$imgList = I('img');
		$i = 1;
		foreach($imgList as $k=>$v){
			if($v != ''){
				$data['img'.$i] = $v;
				$i++;
			}
		}

		$r = I('r');
		$res = $db->where('id=%d',$id)->save($data);
		if($res){
			$this->success('评论修改成功',$r);
		}else{
			$this->error('评论修改失败',$r);
		}
	}

	public function edit_statue(){
		$db = M('evaluate');
		$id = I('get.id');
		$data['statue'] = I('get.change');
		$r = I('r');
		$res = $db->where('id=%d',$id)->save($data);
		if($res){
			$this->success('评论修改成功',$r);
		}else{
			$this->error('评论修改失败',$r);
		}
	}

	public function del(){
	//编辑评论状态
		$id = I('get.id');
		$r = I('r');
		$res = M('evaluate')->delete($id);
		if($res){
			$this->success('评论删除成功',$r);
		}else{
			$this->error('评论删除失败',$r);
		}

	}

	public function add(){
		$where['goods_stats'] = 1;
		$goods = M('goods')->where($where)->select();
		$users = M('member')->where('utype=2')->select();
		if($users){
			$user['username'] = $users[rand(0,count($users)-1)]['username'];
			$user['phone'] = $users[rand(0,count($users)-1)]['phone'];
		}
		$this->goods = $goods;
		$this->user = $user;
		$this->display();
	}

	public function doadd(){
		$db = M('evaluate');
		$data['goods_id'] = I('goods_id');
		$data['username'] = I('username');
		$data['phone'] = I('phone');
		$data['level'] = I('level');
		$data['text'] = I('text');
		$data['create_at'] = NowTime();
		$data['update_at'] = NowTime();
		$data['statue'] = I('statue');
		$imgList = I('img');
		$i = 1;
		foreach($imgList as $k=>$v){
			if($v != ''){
				$data['img'.$i] = $v;
				$i++;
			}
		}
		$res = $db->add($data);
		if($res){
			$this->success('评论添加成功',U('Evaluate/index'));
		}else{
			$this->error('评论添加失败',U('Evaluate/index'));
		}
	}

	public function imgup() {
		$num = I('get.num') ? I('get.num') : 0;
		$type = 'images';
		$folder = 'Evaluate';
		$item = 'upimg'.$num;
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	function dodadd()
	{
		ini_set('memory_limit','1024M');    // 临时设置最大内存占用为1G
		set_time_limit(0);   // 设置脚本最大执行时间 为0 永不过期
		$num = 9;   //规定表格9列
		$data = array();
		//获取上传文件
		if(IS_POST){
			$file = I('evaFile');
			$file = fopen($file,"r");

			while(! feof($file))
			{
				$info = fgetcsv($file);
				$info = $this->gbktoutf8($info);
				$data[] = $info;
			}
			if(count($data[0]) !== $num){
				$this->error('评论导入失败，导入文件与示例文件不符。', U('Evaluate/dadd'),5);exit();
			}
			if(!empty($data) && count($data) > 2){
				array_shift($data);
				array_pop($data);
				foreach($data as $k=>$v){
					$fields['goods_id'] = $v[1];
					$fields['username'] = $v[2];
					$fields['phone'] = $v[3];
					$fields['level'] = $v[4];
					$fields['text'] = $v[5];
					if($v[6]){
						$imgList = explode('##',$v[6]);
						if(count($imgList) > 4){
							$this->error('编号:'.$v[0].' 图片超过四张，已导入 '.($v[0]-1).' 条数据', U('Evaluate/dadd'),5);
							exit();
						}
						foreach($imgList as $kk=>$vv){
							$fields['img'.($kk+1)] = $vv;
						}
					}

					$fields['create_at'] = $v[7];
					$fields['update_at'] = $v[7];
					$fields['statue'] = $v[8];
					$res = M('evaluate')->add($fields);
//					deBug($res,false,true);
					if(!$res){
						$this->error('编号:'.$v[0].' 导入失败，已导入 '.($v[0]-1).' 条数据', U('Evaluate/dadd'),5);
						exit();
					}
				}
			}else{
				$this->error('空文件上传', U('Evaluate/dadd'),5);
				exit();
			}
			fclose($file);
		}else{
			$this->error('非法上传', U('Evaluate/dadd'),5);
			exit();
		}
		$this->success('已导入 ' . count($data) . ' 条数据',U('Evaluate/index'),5);

	}
	function gbktoutf8($info){
		$data = array();
	 	foreach($info as $v){
		 	$data[] = iconv('GB2312', 'UTF-8', $v);
	 	}
		return $data;
	}
	public function imgupcsv() {
		$num = I('get.num') ? I('get.num') : 0;
		$type = 'file';
		$folder = 'Eva_csv';
		$item = 'upimg'.$num;
		$name = "";
		$width = 900;
		$height = 900;
		$this->_ajaxupload($type,$folder,$item,$name,$width,$height);
	}

	public function dadd(){

		$this->display();
	}

	public function downFile($path = ''){
		$path = './Uploads/Eva_csv/demo.csv';
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

}
