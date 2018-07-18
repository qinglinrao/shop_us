<?php

return array(

	'DB_TYPE' => 'mysql',	// 数据库类型
	'DB_HOST' => '127.0.0.1',	// 服务器地址
	'DB_NAME' => 'shop_us',	// 数据库名
	'DB_USER' => 'root',	// 登录帐号
	'DB_PWD' => '[eEcI*b-x.t0968~',	// 登录密码
	'DB_PORT' => '3306',	// 端口
	'DB_PREFIX' => 'pt_',	// 数据库表前缀

	'SHOW_PAGE_TRACE' => false,	// 开启TRACE调试 false / true
	'ERROR_MESSAGE' => '页面错误！请稍后再试～',	// 错误显示信息,非调试模式有效

		'MODULE_ALLOW_LIST'    =>    array('Home','Admin','Mobile'),
		'DEFAULT_MODULE'       =>    'Home',
		'URL_MODEL' => '2',

);