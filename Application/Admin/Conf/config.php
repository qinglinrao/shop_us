<?php

return array(

	// 设置相关文件路径
	'TMPL_PARSE_STRING' => array(
		'__PUBLIC__' => __ROOT__ .'/Public/Admin/resource',
		'__STATIC__' => __ROOT__ .'/Public/static',
		'__EDITOR__' => __ROOT__ .'/Public/editor',
		'__LAYUI__' => __ROOT__ .'/Public/Admin/layui',
		'__AIMG__' => __ROOT__ .'/Public/Admin/resource/images/',
		'__AJS__' => __ROOT__ .'/Public/Admin/resource/js/',
	),

	// 设置URL模式
	'URL_CASE_INSENSITIVE' => true,	// 默认 false 表示URL区分大小写 true 则表示不区分大小写
	'URL_MODEL' => 0,
	// URL访问模式：0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
	'URL_PATHINFO_DEPR' => '/',	// PATHINFO模式下，各参数之间的分割符号

	// 设置上传参数
	'FILE_UPLOAD_CONFIG' => array(
		'maxSize' => 1024*1024*2,
		'exts' => array('txt','doc','docx','xls','xlsx','pdf','rar','zip','png','gif','jpg','jpeg'),
		'autoSub' => false,
		'subName' => array('date','Y-m-d'),
		'rootPath' => './Uploads/',
		'savePath' => '',
		'saveName' => array('uniqid',''),
	),

	// 页面Trace信息
	// 'SHOW_PAGE_TRACE' => true,
	// 'TRACE_PAGE_TABS' => array(
	// 'base'=>'基本',
	// 'think'=>'流程',
	// 'error'=>'错误',
	// 'sql'=>'SQL',
	// 'debug'=>'调试'
	// ),

	// 提示跳转
	'TMPL_ACTION_SUCCESS' => 'Public:dispatch_jump',	// 默认成功跳转对应的模板文件
	'TMPL_ACTION_ERROR' => 'Public:dispatch_jump',	// 默认失败跳转对应的模板文件
);