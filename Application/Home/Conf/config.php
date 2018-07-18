<?php
return array(
	//'配置项'=>'配置值'
    //'URL_ROUTER_ON'   => false, //开启路由

    //配置路由规则
    /*'URL_MAP_RULES'=>array(
        'product/:id'          => '/home/Index/index/id/:1',
    ),*/

	// 设置相关文件路径
	'TMPL_PARSE_STRING' => array(
		'__PUBLIC__' => __ROOT__ .'/public',
		'__STATIC__' => __ROOT__ .'/Public/static',
		'__EDITOR__' => __ROOT__ .'/Public/editor',
		'__HIMG__' => __ROOT__ .'/Public/Home/images/',
		'__HJS__' => __ROOT__ .'/Public/Home/js/',
		'__HCSS__' => __ROOT__ .'/Public/Home/css/',
		'__UPGOODSIMG__' => __ROOT__ .'/Uploads/Goods/',

			'__GIMG__' => __ROOT__ .'/Public/Goods/img/',
			'__GJS__' => __ROOT__ .'/Public/Goods/js/',
			'__GCSS__' => __ROOT__ .'/Public/Goods/css/',

            //新的首页
            '__NEW-IMG__' => __ROOT__ .'/Public/Goods/index-new/img/',
            '__NEW-JS__' => __ROOT__ .'/Public/Goods/index-new/js/',
            '__NEW-CSS__' => __ROOT__ .'/Public/Goods/index-new/css/',
            '__PAYPAL-CSS__' => __ROOT__ .'/Public/Goods/index-paypal/css/',

            //新的首页2-20180703
            '__MORE-JS__' => __ROOT__ .'/Public/Goods/index-more/js/',
	),
);