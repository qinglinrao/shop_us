<?php
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {

	// 检测管理员登录
	protected function _initialize() {
//		if (!session('?admin_id')) {
//			$this->redirect('Public/login');
//		} else {
			// $auth = new \Think\Auth();
			// if (!$auth->check(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME, session('member_id'))) {
			// 	$this->error('操作权限不足');
			// }
//		}
	}


	// AJAX文件上传
	protected function _ajaxupload($type,$folder,$item,$name,$width,$height,$isCut=1) {
		//$type上传类型 $folder上传文件夹 $width缩略图宽 $height缩略图高
		$upload = new \Think\Upload();
		switch ($type) {
			case 'images':
				$upload->exts = array('png','gif','jpg','jpeg');
				$upload->maxSize = 1024*1024*20;
				break;
			case 'file':
				$upload->exts = array('txt','doc','docx','xls','xlsx','pdf','rar','zip','csv');
				$upload->maxSize = 1024*1024*20;
				break;
			case 'video':
				$upload->exts = array('mp4','wmv');
				$upload->maxSize = 1024*1024*20;
				break;
			case 'audio':
				$upload->exts = array('mp3','wma');
				$upload->maxSize = 1024*1024*20;
				break;
		}
		$upload->rootPath = './';
		$upload->savePath = './Uploads/'.$folder.'/';
		if ($name) {
			$upload->replace = true;
			$upload->saveName = $name;
		} else {
			$upload->saveName = array('uniqid','');
		}
		$upload->autoSub = false;
		$upload->subName = array('date','Y-m-d');
		$info = $upload->uploadOne($_FILES[$item]);
		if (!$info) {
			$this->ajaxReturn(array('status'=>'error','imgname'=>$name,'msg'=>$upload->getError()));
		} else {
			$newfile = $info['savepath'].$info['savename'];
		}
		if ($type == 'images' && $width != 0 && $height != 0) {
			$thumb = new \Think\Image(\Think\Image::IMAGE_GD,$newfile);
			if($isCut == 1){
				$thumb->thumb($width,$height,\Think\Image::IMAGE_THUMB_CENTER)->save($newfile);
			}else{
				$thumb->thumb($width,$height,\Think\Image::IMAGE_THUMB_SCALE)->save($newfile);
			}
		}
		echo json_encode(array('status'=>'success','msg'=>'文件上传成功','url'=>trim($newfile,'.')));
	}

	// 删除文件夹
	protected function _delDir($path) {
		$handle = opendir($path);
		while(($item = readdir($handle)) !== false) {
			if ($item != '.' and $item != '..') {
				if (is_dir($path.'/'.$item)) {
					$this->_delDir($path.'/'.$item);
				} else {
					if (!unlink($path.'/'.$item))
					die('清理失败');
				}
			}
		}
		closedir($handle);
		return rmdir($path);
	}



}
