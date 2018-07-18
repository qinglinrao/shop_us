<?php
	
	// 会员等级
	function show_grade($type, $grade) {
		if ($type == 1) {
			return '普通会员';
		} else {
			$grade_name = M('seller_grade')->where('grade_id = '.$grade)->getField('grade_name');
			return $grade_name;
		}
	}

	// 微信二维码
	function show_weixin_qrcode($img) {
		if ($img) {
			return $img;
		} else {
			return './uploads/site/no_images_200.jpg';
		}
	}

	// 通用分页
	function show_page($count, $pagesize=10) {
		$p = new Think\Page($count, $pagesize);
		$p->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
		$p->setConfig('prev', '上一页');
		$p->setConfig('next', '下一页');
		$p->setConfig('last', '末页');
		$p->setConfig('first', '首页');
		$p->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');
		$p->lastSuffix = false;
		return $p;
	}

	// 通用状态
	function show_state($type, $state) {
		switch ($type) {
			case 'admin':
				if ($state == 1) {
					return '正常使用';
					break;
				}
				if ($state == 2) {
					return '<span class="c-red">禁止使用</span>';
					break;
				}
			case 'payment':
			case 'city':
			case 'area':
			case 'time':
			case 'charge':
				if ($state == 1) {
					return '开启使用';
					break;
				}
				if ($state == 2) {
					return '<span class="c-red">关闭使用</span>';
					break;
				}
		}
	}

	// 通用截取中文字符串
	function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
		if (function_exists("mb_substr")) {
			$slice = mb_substr($str, $start, $length, $charset);
		} else if (function_exists('iconv_substr')) {
			$slice = iconv_substr($str,$start,$length,$charset);
			if (false === $slice) {
				$slice = '';
			}
		} else {
			$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
			preg_match_all($re[$charset], $str, $match);
			$slice = join("",array_slice($match[0], $start, $length));
		}
		return $suffix ? $slice.'...' : $slice;
	}

	/** 调试程序函数
	 * @2016-3-16
	 * @params #type
	 * */
	function deBug($data, $type=false, $sql=false){
		echo '<pre>';
		var_dump($data);
		echo '</pre>';
		if($sql){
			var_dump(M()->getLastSql());
		}
		if(!$type){
			die;
		}
	}
	/**
	 * 当前时间
	 * */
	function NowTime() {
		return date("Y-m-d H:i:s");
	}