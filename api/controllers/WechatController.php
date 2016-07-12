<?php

namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\filters\Cors;
use api\sdk\WechatPay;
use api\models\WechatUser;
use common\models\Order;
use common\models\Product;

class WechatController extends ActiveController
{

    public $modelClass = '';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        return $behaviors;
    }

    //微信服务接入时，服务器需授权验证
    public function actionValid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        //valid signature , option
        if($this->checkSignature($signature,$timestamp,$nonce)){
            echo $echoStr;
        }
    }

    //参数校验
    private function checkSignature($signature,$timestamp,$nonce)
    {
        $token = Yii::$app->params['wechat']['token'];
        if (!$token) {
            echo 'TOKEN is not defined!';
        }else{
            $tmpArr = array($token, $timestamp, $nonce);
            // use SORT_STRING rule
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );

            if( $tmpStr == $signature ){
                return true;
            }else{
                return false;
            }
        }
    }

    //用户授权接口：获取access_token、openId等；获取并保存用户资料到数据库
    public function actionAccesstoken()
    {
        $code = $_GET["code"];
        $state = $_GET["state"];
        $appid = Yii::$app->params['wechat']['appid'];
        $appsecret = Yii::$app->params['wechat']['appsecret'];
        $request_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';
        //初始化一个curl会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = $this->response($result);
        //获取token和openid成功，数据解析
        $access_token = $result['access_token'];
        $refresh_token = $result['refresh_token'];
        $openid = $result['openid'];

        //请求微信接口，获取用户信息
        $userInfo = $this->getUserInfo($access_token,$openid);

        $user_check = WechatUser::find()->where(['openid'=>$openid])->one();
        if ($user_check) {
            //更新用户资料
            $user_check->nickname = $userInfo['nickname'];
            $user_check->sex = $userInfo['sex'];
            $user_check->headimgurl = $userInfo['headimgurl'];
            $user_check->country = $userInfo['country'];
            $user_check->province = $userInfo['province'];
            $user_check->city = $userInfo['city'];
            $user_check->access_token = $access_token;
            $user_check->refresh_token = $refresh_token;
            $user_check->update();
        } else {
            //保存用户资料
            $user = new WechatUser();
            $user->nickname = $userInfo['nickname'];
            $user->sex = $userInfo['sex'];
            $user->headimgurl = $userInfo['headimgurl'];
            $user->country = $userInfo['country'];
            $user->province = $userInfo['province'];
            $user->city = $userInfo['city'];
            $user->access_token = $access_token;
            $user->refresh_token = $refresh_token;
            $user->openid = $openid;
            $user->save();
        }
        //前端网页的重定向
        if ($openid) {
            return $this->redirect($state.$openid);
        } else {
            return $this->redirect($state);
        }
    }

    //从微信获取用户资料
    public function getUserInfo($access_token,$openid)
    {
        $request_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        //初始化一个curl会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = $this->response($result);
        return $result;
    }

    //获取用户资料接口
    public function actionUserinfo()
    {
        if(isset($_REQUEST["openid"])){
            $openid = $_REQUEST["openid"];
            $user = WechatUser::find()->where(['openid'=>$openid])->one();
            if ($user) {
                $result['error'] = 0;
                $result['msg'] = '获取成功';
                $result['user'] = $user;
            } else {
                $result['error'] = 1;
                $result['msg'] = '没有该用户';
            }
        } else {
            $result['error'] = 1;
            $result['msg'] = 'openid为空';
        }
        return $result;
    }

    private function response($text)
    {
        return json_decode($text, true);
    }

    //微信支付接口：打包支付数据
    public function actionPay(){
        if(isset($_REQUEST["uid"])&&isset($_REQUEST["oid"])&&isset($_REQUEST["totalFee"])&&isset($_REQUEST["orderName"])){
            //uid、oid
            $uid = $_REQUEST["uid"];
            $oid = $_REQUEST["oid"];
            //微信支付参数
            $appid = Yii::$app->params['wechat']['appid'];
            $mchid = Yii::$app->params['wechat']['mchid'];
            $key = Yii::$app->params['wechat']['key'];
            $notifyUrl = Yii::$app->params['wechat']['notifyUrl'];
            //商品订单参数
            $totalFee = $_REQUEST["totalFee"];
            $orderName = $_REQUEST["orderName"];
            //支付打包
            $wx_pay = new WechatPay($mchid, $appid, $key);
            $package = $wx_pay->createJsBizPackage($uid, $totalFee, $oid, $orderName, $notifyUrl, $timestamp);
            $result['error'] = 0;
            $result['msg'] = '支付打包成功';
            $result['package'] = $package;
        }else{
            $result['error'] = 1;
            $result['msg'] = '请求参数错误';
        }
        return $result;
    }

    public function actionConfig(){
        if (isset($_REQUEST['url'])) {
            $url = $_REQUEST['url'];
            //微信支付参数
            $appid = Yii::$app->params['wechat']['appid'];
            $mchid = Yii::$app->params['wechat']['mchid'];
            $key = Yii::$app->params['wechat']['key'];
            $wx_pay = new WechatPay($mchid, $appid, $key);
            $package = $wx_pay->getSignPackage($url);
            $result['error'] = 0;
            $result['msg'] = '获取成功';
            $result['config'] = $package;
        } else {
            $result['error'] = 1;
            $result['msg'] = '参数错误';
        }
        return $result;
    }

    //接收微信发送的异步支付结果通知
    public function actionNotify(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        //
        if ($postObj === false) {
            die('parse xml error');
        }
        if ($postObj->return_code != 'SUCCESS') {
            die($postObj->return_msg);
        }
        if ($postObj->result_code != 'SUCCESS') {
            die($postObj->err_code);
        }

        //微信支付参数
        $appid = Yii::$app->params['wechat']['appid'];
        $mchid = Yii::$app->params['wechat']['mchid'];
        $key = Yii::$app->params['wechat']['key'];
        $wx_pay = new WechatPay($mchid, $appid, $key);

        //验证签名
        $arr = (array)$postObj;
        unset($arr['sign']);
        if ($wx_pay->getSign($arr, $key) != $postObj->sign) {
            die("签名错误");
        }

        //支付处理正确-判断是否已处理过支付状态
        $orders = Order::find()->where(['uid'=>$postObj->openid, 'oid'=>$postObj->out_trade_no, 'status' => 0])->all();
        if(count($orders) > 0){
            //更新订单状态
            $products = array();
            foreach ($orders as $order) {
                $order['status'] = 1;
                $order->update();
            }
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } else {
            //订单状态已更新，直接返回
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }
    }

}
