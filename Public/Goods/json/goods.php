<?php

$order[0] = array(
		'goods_title'=>'张少强',
		'username'=>'陈冠希',
		'phone'=>15858589696,
		'country'=>'中国',
		'time'=>'30'
);
$order[1] = array(
		'goods_title'=>'林俊杰',
		'username'=>'张三1',
		'phone'=>15858589696,
		'country'=>'中国',
		'time'=>'30'
);
$order[2] = array(
		'goods_title'=>'康有为',
		'username'=>'张三2',
		'phone'=>15858589696,
		'country'=>'中国',
		'time'=>'30'
);
$order[3] = array(
		'goods_title'=>'梁启超',
		'username'=>'张三3',
		'phone'=>15858589696,
		'country'=>'中国',
		'time'=>'30'
);
$order[4] = array(
		'goods_title'=>'张惠妹',
		'username'=>'张三4',
		'phone'=>15858589696,
		'country'=>'中国',
		'time'=>'30'
);
$order[5] = array(
		'goods_title'=>'徐若彤',
		'username'=>'张三5',
		'phone'=>15858589696,
		'country'=>'中国',
		'time'=>'30'
);
$data[0] = array(
		'goods_title'=>'选手机就选它',
		'username'=>'张三0',
		'phone'=>15858589696,
		'level'=>5,
		'text'=>'这个真不错，这个真不错，这个真不错，这个真不错，这个真不错。',
		'create_at'=>'2018-04-14 21:51:46',
		'img1'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img2'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img3'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img4'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg'
);
$data[1] = array(
		'goods_title'=>'选手机就选它',
		'username'=>'张三1',
		'phone'=>15858589696,
		'level'=>5,
		'text'=>'这个真不错，这个真不错，这个真不错，这个真不错，这个真不错。',
		'create_at'=>'2018-04-14 21:51:46',
		'img1'=>'',
		'img2'=>'',
		'img3'=>'',
		'img4'=>''
);
$data[2] = array(
		'goods_title'=>'选手机就选它',
		'username'=>'张三2',
		'phone'=>15858589696,
		'level'=>5,
		'text'=>'这个真不错，这个真不错，这个真不错，这个真不错，这个真不错。',
		'create_at'=>'2018-04-14 21:51:46',
		'img1'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img2'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img3'=>'',
		'img4'=>''
);
$data[3] = array(
		'goods_title'=>'选手机就选它',
		'username'=>'11张三3',
		'phone'=>15858589696,
		'level'=>5,
		'text'=>'这个真不错，这个真不错，这个真不错，这个真不错，这个真不错。',
		'create_at'=>'2018-04-14 21:51:46',
		'img1'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img2'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img3'=>'',
		'img4'=>''
);
$data[4] = array(
		'goods_title'=>'选手机就选它',
		'username'=>'11张三4',
		'phone'=>15858589696,
		'level'=>5,
		'text'=>'这个真不错，这个真不错，这个真不错，这个真不错，这个真不错。',
		'create_at'=>'2018-04-14 21:51:46',
		'img1'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img2'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img3'=>'',
		'img4'=>''
);
$data[5] = array(
		'goods_title'=>'选手机就选它',
		'username'=>'11张三5',
		'phone'=>15858589696,
		'level'=>5,
		'text'=>'这个真不错，这个真不错，这个真不错，这个真不错，这个真不错。',
		'create_at'=>'2018-04-14 21:51:46',
		'img1'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img2'=>'https://img30.360buyimg.com/shaidan/s310x310_jfs/t11236/279/1805223348/136638/35607a39/5a092d64N91472708.jpg',
		'img3'=>'',
		'img4'=>''
);
//goods.php?type=1&f=1  订单6条
//goods.php?type=1&f=2  订单1条
//goods.php?type=2&f=1  评论6条
//goods.php?type=2&f=2  评论1条

$type = $_GET['type']; //1订单 2评论
$f = $_GET['f'];  //1：6条  2：一条
if($f == 2){
	$data = [$data[0]];
	$order = [$order[0]];
}
if($type == 1){
	$return = $order;
}else{
	$return = $data;
}
echo json_encode($return);