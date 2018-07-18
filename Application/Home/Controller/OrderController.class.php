<?php
namespace Home\Controller;
use Think\Controller;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;
class OrderController extends Controller {

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

    # 不在服务范围的邮编。
    public function no_server_code(){
        //实例化redis
        $redis = new \Redis();
        //连接
        $redis->connect('127.0.0.1', 6379);
        //检测是否连接成功
        //echo "Server is running: " . $redis->ping();

        # 这个项目的redis前缀固定是shop2_
        $key = 'shop2_no_server_code';
        $data = $redis->get($key);

        if($data){
            $txt_arr = json_decode($data, true);
        }else{
            # 读取不在范围的邮编。$siteinfo_file = include('Config/siteinfo.config.php');
            $path = 'Config/no_server_code.txt';
            # $file = fopen($path, 'r');

            $content = file_get_contents($path);
            $content = mb_convert_encoding ( $content, 'UTF-8','Unicode');

            # 这里不能使用PHP_EOL，因为txt文件是windows系统的。
            $array = explode("\r\n", $content);
            $txt_arr = array();
            for($i=0; $i<count($array); $i++)
            {
                $txt_arr[] = $array[$i];
                //echo $array[$i].'<br />';
            }

            //缓存1天。
            $redis->set($key, json_encode($txt_arr), 60*60*24);
        }
        return $txt_arr;

    }

    #上面那个方法有问题。--todo
    public function no_server_code2(){

        $noserver_file = include('Config/noserver.config.php');
        return $noserver_file;

        # 读取不在范围的邮编。$siteinfo_file = include('Config/siteinfo.config.php');
        $path = 'Config/no_server_code.txt';
        # $file = fopen($path, 'r');

        $content = file_get_contents($path);
        $content = mb_convert_encoding ( $content, 'UTF-8','Unicode');

        # 这里不能使用PHP_EOL，因为txt文件是windows系统的。
        $array = explode("\r\n", $content);
        $txt_arr = array();
        for($i=0; $i<count($array); $i++)
        {
            $txt_arr[] = $array[$i];
            //echo $array[$i].'<br />';
        }

        return $txt_arr;

    }

    public function test(){
        $noserver_file = include('Config/noserver.config.php');
        print_r($noserver_file);exit;

        $path = 'Config/no_server_code.txt';
        # $file = fopen($path, 'r');

        $content = file_get_contents($path);
        $content = mb_convert_encoding ( $content, 'UTF-8','Unicode');

        $array = explode("\r\n", $content);
        $txt_arr = array();
        for($i=0; $i<count($array); $i++)
        {
            $txt_arr[] = $array[$i];
        }

        $str = '<?php return'.PHP_EOL;
        $str .= var_export($txt_arr, true);
        $str .= ';';
        $noserver_file = 'Config/noserver.config.php';
        file_put_contents($noserver_file, $str);
    }

    public function createOrder()
    {
        //
        $goodId = I('post.goodId');
        $sizeId = I('post.sizeId');
        $userName = I('post.userName');
        $phone = I('post.phone');
        $address = I('post.address');
        $code = I('post.code');
        $email = I('post.email');
        $remark = I('post.remark');
        $payType = I('post.payType');
        $goodCount = I('post.goodCount');
        $refer = I('post.refer');

        # 这里需要安全验证的操作--todo
        if(!$goodId || !$phone){
            exit;
        }
        //验证数据
        $check = 123;

        //订单识别码
        $o_code = I('post.o_code');
        $admin_id = 0;
        if($o_code){
            $o_where = array();
            $o_where['admin_code'] = $o_code;
            $admin_data = M('admin')->where($o_where)->field('admin_id')->find();
            if($admin_data){
                $admin_id = $admin_data['admin_id'];
            }
        }

        //将地址信息写入用户表
        $userModel = M('member');
        $userData = array(
            'phone' => $phone,
            'username' => $userName,
            'address' => $address,
            'email' => $email,
            'create_at' => date('Y-m-d H:i:s'),
            'utype' => 1,
            'code' => $code,
        );

        //判断是返回英文还是中文。
        $model = I('get.model');
        if($model == 'CN'){
            $msg1 = '商品有误';
            $msg2 = '用户信息有误';
            $msg3 = '订单生成成功';
        }else{
            $msg1 = 'something is wrong';
            $msg2 = 'user is wrong';
            $msg3 = 'Success of order generation';
        }

        //根据商品id，查询商品信息
        $goodsInfo = $goodInfo = M('goods')->find($goodId);
        if (empty($goodsInfo)) {

            $res = array('code'=>1,'data'=>array('msg'=>$msg1));
            echo json_encode($res);
            die();
        }

        $userModel->create($userData);
        $userId = $userModel -> add();
        if ($userId <= 0) {
            $res = array('code'=>1,'data'=>array('msg'=>$msg2));
            echo json_encode($res);
            die();
        }

        $strDesc = '';

        $price = $goodsInfo['goods_trprice']*$goodCount;

        $priceAmount = $price;
        //判断商品是否为团购商品
        $tuanFlag = 0;
        if ($goodsInfo['goods_istuan'] > 0) {
            $tuanInfo = M('tourdiy')->find($goodsInfo['goods_istuan']);
            $time = time();
            if (strtotime($tuanInfo['start_at']) < $time && strtotime($tuanInfo['end_at']) > $time) {
                $tuanFlag = 1;
                $strDesc .= '团购价格为'.$goodsInfo['goods_twprice'];
                $priceAmount = $goodsInfo['goods_twprice']*$goodCount;
            }
        }

        //判断商品是否为促销商品
        $promotionFlag = 0;
        $goodsCountAmount = $goodCount;
        if ($goodsInfo['goods_promotion'] > 0) {
            //查询促销信息
            $promotion = M('promotion')->find($goodsInfo['goods_promotion']);
            if ($promotion) {
                $promotionFlag = 1;
                if ($promotion['ptype'] == 1 && $goodCount >= $promotion['first']) {
                    $strDesc .= '/'.'买'.$promotion['first'].'送'.$promotion['second'];
                    $goodsCountAmount = $goodCount + $promotion['second'];
                } else if ($promotion['ptype'] == 2 && $goodCount >= $promotion['first']) {
                    $strDesc .= '/'.'买'.$promotion['first'].'打'.$promotion['second'].'折';
                    $num = $promotion['second']/10;
                    $priceAmount = $price*$num;
                }
            }
        }
        //运费
        $price += $goodInfo['goods_price'];
        $priceAmount += $goodInfo['goods_price'];
        $status = 1;
        //如果为货到付款,修改订单状态为代发货
        if ($payType == 5) {
            $status = 10;
        }

        //将数据写入订单中
        $orderId = date('YmdHis').rand(1000,9999);
        $orderData = array(
            'order_id' => $orderId,
            'good_id' => $goodId,
            'size_id' => $sizeId,     //sizeId是不准的。
            'good_count' => $goodsCountAmount,
            'money' => $priceAmount,
            'user_id' => $userId,
            'from' => $refer,
            'statue' => $status,
            'create_at' => date('Y-m-d H:i:s'),
            'old_price' => $price,
            'old_count' => $goodCount,
            'content' => $strDesc,
            'remark' => $remark,
            'pay_type' => $payType,
            'admin_id' => $admin_id,
        );


        # 判断邮编是否合法
        if($code){
            if(in_array((string)$code, $this->no_server_code2(), true)){
                $orderData['is_useful'] = 0;
                $orderData['is_useful_remark'] = '邮编不在配送服务范围';
            }
        }


        //添加订单
        $orderModel = M('orders');
        $orderModel->create($orderData);
        $orderId = $orderModel -> add();


        //添加记录到新的订单规格表。
        $color = I('post.color');
        $weight = I('post.weight');
        $size = I('post.size');
        /*if($color || $weight || $size){*/
            $sizeData = array();
            $sizeData['color'] = $color;
            $sizeData['weight'] = $weight;
            $sizeData['size'] = $size;
            $sizeData['user_id'] = $userId;
            $sizeData['good_id'] = $goodId;
            $sizeData['order_id'] = $orderId;
            # 订单归属于哪个推广人员的id
            $sizeData['admin_id'] = $admin_id;
            $sizeData['num'] = $goodsCountAmount;
            $sizeData['add_time'] = date('Y-m-d H:i:s');

            $orderSizeModel = M('orders_size');
            $orderSizeModel->create($sizeData);
            $orderSizeModel -> add();
        /*}*/

        //创建Paypal订单和生成连接。
        $approvalUrl = '';
        if($payType == 4){
            $payment = $this->createPayPalOrder($goodsInfo, $priceAmount, $goodsCountAmount, $orderId, $userId);

             # PayPal的订单id
            $paypalId = $payment->getId();
            # PayPal的订单创建时间。
            $paypalTime = $payment->getCreateTime();
            # 转换时间戳
            $paypalTime = strtotime($paypalTime);
            # 跳转url
            $approvalUrl = $payment->getApprovalLink();

        }

        $res = array('code'=>0,'data'=>array("orderId"=>$orderId,'msg'=>$msg3, 'url'=>$approvalUrl));
        echo json_encode($res);
    }

    # 创建paypal订单
    public function createPayPalOrder($goodsInfo, $priceAmount, $goodsCountAmount, $orderId, $userId) {

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // 获取商品信息。
        $item1 = new Item();
        $item1->setName($goodsInfo['title'])  //商品名称
            ->setCurrency('USD')
            ->setQuantity($goodsCountAmount) //数量
            ->setSku($goodsInfo['goods_number']) // Similar to `item_number` in Classic API
            ->setPrice($priceAmount);


        $itemList = new ItemList();
        $itemList->setItems(array($item1));

        # 不需要运费和税
        $details = new Details();
        /*$details->setShipping(1.2)
            ->setTax(1.3)
            ->setSubtotal(17.50);*/
        $details->setSubtotal($priceAmount);

        // ### Amount
        // Lets you specify a payment amount.
        // You can also specify additional details
        // such as shipping, tax.
        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($priceAmount)
            ->setDetails($details);

        // ### Transaction
        // A transaction defines the contract of a
        // payment - what is the payment for and who
        // is fulfilling it.
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($goodsInfo['goods_introduce'])
            ->setInvoiceNumber(uniqid());

        // ### Redirect urls
        // Set the urls that the buyer must be redirected to after
        // payment approval/ cancellation.
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl('http://'.$_SERVER['HTTP_HOST'].'/home/order/paypalCallback/success/true')
            ->setCancelUrl('http://'.$_SERVER['HTTP_HOST'].'/home/order/paypalCallback/success/false');

        // ### Payment
        // A Payment Resource; create one using
        // the above types and intent set to 'sale'
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));


        // For Sample Purposes Only.
        $request = clone $payment;

        //设置时区？
        date_default_timezone_set(@date_default_timezone_get());

        #paypal的配置信息
        $apiContext = $this->getApiContext('ATMFmfgRKf0G_pP9pD65UJ8hQ3Gb-C6zK4oZfyjncVLpfovcjitvm4D4SOmPShBnGZ22YqQOdMkDQntZ', 'ECbCUQqPwKpB3WrcPIteM3Na7jTZ60J0DJaSK0sk7rZA1UYNmgupGwiJV8iHmej_l7vAMClHXWpCg3je');

        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            print_r('create error');
            exit(1);
        }

        # PayPal的订单id
        $paypalId = $payment->getId();
        # PayPal的订单创建时间。
        $paypalTime = $payment->getCreateTime();
        # 转换时间戳
        $paypalTime = strtotime($paypalTime);
        $approvalUrl = $payment->getApprovalLink();

        # 生成pt_paypal_orders订单。
        $orderData['father_id'] = $orderId;
        $orderData['good_id'] = $goodsInfo['id'];
        $orderData['user_id'] = $userId;
        $orderData['paypal_id'] = $paypalId;
        $orderData['money'] = $priceAmount;
        $orderData['ip'] = $this->getClientIP();
        $orderData['pay_url'] = $approvalUrl;
        $orderData['num'] = $goodsCountAmount;
        $orderData['create_at'] = $paypalTime;

        $paypalordersModel = M('paypal_orders');
        $paypalordersModel->create($orderData);
        $paypalordersModel -> add();


        return $payment;
    }

    # paypal支付的回调
    public function paypalCallback(){
        $success = I('get.success');

        if($success && $success == 'true'){
            $paymentId = I('get.paymentId');
            $data['paypal_id'] = $paymentId;
            $orderPaypalInfo = M('paypal_orders')->where($data)->find();
            if(!$orderPaypalInfo){
                echo 'PayPal payment false';exit;
            }

            # 验证
            $PayerID = I('get.PayerID');
            #paypal的配置信息
            #$apiContext = $this->getApiContext('AZgn_deHN3i9-ZuXk9q1aR8hBiuMrfUcmi4nMIdBtO51qn7adZpV2AJJukTvh0NNGOD29U8PG4bmfCNk', 'ELfc76frIqLeVhkLKv-T_D5i6v5OezPkAGn1WWQ5pL-pfpZtkD3NccuU-qflQGptFBX3yWLCqheB6SmC');
            $apiContext = $this->getApiContext('ATMFmfgRKf0G_pP9pD65UJ8hQ3Gb-C6zK4oZfyjncVLpfovcjitvm4D4SOmPShBnGZ22YqQOdMkDQntZ', 'ECbCUQqPwKpB3WrcPIteM3Na7jTZ60J0DJaSK0sk7rZA1UYNmgupGwiJV8iHmej_l7vAMClHXWpCg3je');

            $payment = Payment::get($paymentId, $apiContext);

            #这里再判断一次paypal的订单状态。
            if($payment->getState() != 'created'){
                echo 'PayPal payment false';exit;
            }

            #发票
            $card = $payment->getTransactions();
            $invoice_number = $card[0]->invoice_number;

            # 下单人的信息
            $payerData = $payment->getPayer();

            $data = array();
            $data['status'] = 2;
            $data['payer_id'] = $PayerID;
            $data['invoice_number'] = $invoice_number;
            $data['email'] = $payerData->payer_info->email;
            $data['first_name'] = $payerData->payer_info->first_name;
            $data['last_name'] = $payerData->payer_info->last_name;
            $data['update_at'] = date('Y-m-d H:i:s');

            $db = M('paypal_orders');
            # 修改pt_paypal_orders订单
            $res1 = $db->where('id=%d',$orderPaypalInfo['id'])->save($data);


            $orderInfo = M('orders')->find($orderPaypalInfo['father_id']);
            if(!$orderInfo){
                echo 'PayPal payment false';exit;
            }

            # 修改pt_orders订单
            $db = M('orders');
            $data = array();
            # 你没看错！这里是statue，不是status【注意】
            $data['statue'] = 2;
            $res2 = $db->where('id=%d',$orderPaypalInfo['father_id'])->save($data);

            if($res1 && $res2){
                $id = $orderPaypalInfo['father_id'];
                $fields = 'o.order_id,o.good_id,o.size_id,o.good_count,g.goods_title,g.goods_istuan,g.goods_country,o.statue,o.create_at';
                $info = M('orders as o')->join('left join pt_goods as g on o.good_id=g.id')->field($fields)->where('o.id=%d',$id)->find();
                $sizeInfo = M('orders as o')->join('left join pt_goods_size as s on o.good_id=s.good_id')->field('s.color,s.size,s.weight')->where('o.id=%d',$id)->find();
                $tjGoodsList = M('goods')->where('goods_stats=1 and is_tj=1')->order('tj_sort DESC')->field('id')->select();
                $info = array_merge($info,$sizeInfo);
                if($tjGoodsList){
                    foreach($tjGoodsList as $k=>$v){
                        $itemImg = M('goods_image')->where('stype=1 and good_id=%d', $v['id'])->order('id asc')->limit(1)->field('image')->select();
                        $tjGoodsList[$k]['goods_img'] = $itemImg[0]['image'];
                    }

                }
                $createTime = strtotime($info['create_at']);
                if($info['goods_istuan'] != 0 && time()-$createTime < 300){
                    $info['status_desc'] = '正在拼团中';
                }else{
                    switch($info['statue']) {
                        case 1:
                            $info['status_desc'] = '待买家付款';
                            break;
                        case 2:
                            $info['status_desc'] = '待买家发货';
                            break;
                        case 3:
                            $info['status_desc'] = '已发货';
                            break;
                        case 4:
                            $info['status_desc'] = '已送达';
                            break;
                        case 5:
                            $info['status_desc'] = '已发货，待用户评价';
                            break;
                        case 6:
                            $info['status_desc'] = '已发货，用户已评价';
                            break;
                        case 7:
                            $info['status_desc'] = '待买家退货';
                            break;
                        case 8:
                            $info['status_desc'] = '退货完成';
                            break;
                        case 9:
                            $info['status_desc'] = '订单完成';
                            break;
                    }
                }
                if($info['goods_country'] == 'CN'){
                    $html = 'buysuccess';
                }else{
                    # $html = 'buysuccess-en';
                    # 修改成功页
                    $html = 'buysuccess-new';
                }
                $this->model = $info['goods_country'];
                $this->info = $info;
                $this->list = $tjGoodsList;
                $this->display($html);
            }else{
                echo 'PayPal payment false'.$orderPaypalInfo['father_id'];exit;
            }
        }else{
            echo 'PayPal payment false';exit;

        }
    }
    # 获取PayPal配置。
    public function getApiContext($clientId, $clientSecret)
    {

        // #### SDK configuration
        // Register the sdk_config.ini file in current directory
        // as the configuration source.
        /*
        if(!defined("PP_CONFIG_PATH")) {
            define("PP_CONFIG_PATH", __DIR__);
        }
        */


        // ### Api context
        // Use an ApiContext object to authenticate
        // API calls. The clientId and clientSecret for the
        // OAuthTokenCredential class can be retrieved from
        // developer.paypal.com

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $clientId,
                $clientSecret
            )

        );

        // Comment this line out and uncomment the PP_CONFIG_PATH
        // 'define' block if you want to use static file
        // based configuration

        $apiContext->setConfig(
            array(
                'mode' => 'live', //沙盒环境:sandbox  线上环境：live
                'log.LogEnabled' => true,
                'log.FileName' => '../../logs/paypal.log',
                #测试环境。
                #'log.FileName' => '../../paypal.log',
                'log.LogLevel' => 'INFO', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => true,
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
                //'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
            )
        );

        // Partner Attribution Id
        // Use this header if you are a PayPal partner. Specify a unique BN Code to receive revenue attribution.
        // To learn more or to request a BN Code, contact your Partner Manager or visit the PayPal Partner Portal
        // $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', '123123123');

        return $apiContext;
    }

    function getClientIP()
    {
        if(getenv('HTTP_APP_IP'))
        {
            return getenv('HTTP_APP_IP');
        }

        $ip_keys = array('HTTP_CDN_SRC_IP','HTTP_X_FORWARDED_FOR','HTTP_REALIP','REMOTE_ADDR');

        foreach($ip_keys as $key)
        {
            if(isset($_SERVER[$key]) && !empty($_SERVER[$key]))
            {
                $ips = explode(',',$_SERVER[$key]);
                foreach($ips as $ip)
                {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE) !== false)
                    {
                        if(preg_match('/^123\.134\.95\.\d{1,3}$/',$ip))
                        {
                            //  网通 squid 到 电信 squid ， HTTP_REALIP 为网通squid 的IP 地址 ，跳过
                            continue;
                        }
                        return $ip;
                    }
                }
            }
        }
        return '';
    }

    /**
     * 订单详情页
     */
    public function orderInfo()
    {
        $orderId = I('get.orderId');
        if ($orderId <= 0) {
            echo '订单错误';
            die();
        }

        //根据订单id查询订单信息
        $orderInfo = M('orders')->find($orderId);
        if (empty($orderInfo)) {
            echo '订单不存在';
            die();
        }

        $userId = $orderInfo['user_id'];
        if ($userId <= 0) {
            echo '订单的用户id为空';
            die();
        }
        //查询用户信息
        $userInfo = M('member')->find($userId);
        $orderInfo['wl_info'] = json_decode($orderInfo['wl_info']);
        $orderInfo['pw_info'] = json_decode($orderInfo['pw_info']);
        switch($orderInfo['statue']) {
            case 1:
                $orderInfo['status_desc'] = '待买家付款';
                break;
            case 2:
                $orderInfo['status_desc'] = '待买家发货';
                break;
            case 3:
                $orderInfo['status_desc'] = '已发货';
                break;
            case 4:
                $orderInfo['status_desc'] = '已送达';
                break;
            case 5:
                $orderInfo['status_desc'] = '已发货，待用户评价';
                break;
            case 6:
                $orderInfo['status_desc'] = '已发货，用户已评价';
                break;
            case 7:
                $orderInfo['status_desc'] = '待买家退货';
                break;
            case 8:
                $orderInfo['status_desc'] = '退货完成';
                break;
            case 9:
                $orderInfo['status_desc'] = '订单完成';
                break;
            case 10:
                $orderInfo['status_desc'] = '待买家发货';
                break;
        }

        switch ($orderInfo['pay_type']) {
            case 1:
                $orderInfo['pay_type_desc'] = '微信支付';
                break;
            case 2:
                $orderInfo['pay_type_desc'] = '支付宝支付';
                break;
            case 3:
                $orderInfo['pay_type_desc'] = '信用卡支付';
                break;
            case 4:
                $orderInfo['pay_type_desc'] = 'paypal支付';
                break;
            case 5:
                $orderInfo['pay_type_desc'] = '货到付款';
                break;
        }

        //查询商品信息
        $goodId = $orderInfo['good_id'];
        $goodsInfo = $goodInfo = M('goods')->find($goodId);
        if (empty($goodsInfo)) {
            echo '订单商品不存在';
            die();
        }

        if ($goodsInfo['goods_country'] == 'UN') {

        } else if ($goodsInfo['goods_country'] == 'CK') {

        }

    }



}
