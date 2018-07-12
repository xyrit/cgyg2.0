<?php

namespace Home\Common\API;

/* PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */

class Oauth {

    const VERSION = "2.0";
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
    const CALLBACK_URL = "http://www.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/qqCallback";

    protected $recorder;
    public $urlUtils;
    protected $error;

    function __construct() {
        $this->recorder = new Recorder(); //dump($this->recorder);exit;
        $this->urlUtils = new URL();
        $this->error = new ErrorCase();
    }

    public function qq_login($type = '') {
//         $appid = $this->recorder->readInc("appid");
        $appid = '101267345';
//         $callback = $this->recorder->readInc("callback");
//         $callback ='http://www.cgyyg.com/otherlogin/index.php/home/index/qqCallback';
        // $callback = 'http://test.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/qqCallback';
        $callback = self::CALLBACK_URL . "?type=" . $type;
        $scope = $this->recorder->readInc("scope");

        //-------生成唯一随机串防CSRF攻击
        // $state = md5(uniqid(rand(), TRUE));
        //$this->recorder->write('state',$state);
        //---------不随机生成state，而是固定为state，暂时找不到读取不到state的原因
        $state = 1;
        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $appid,
            "redirect_uri" => $callback,
            "state" => $state,
            "scope" => $scope
        );

        $login_url = $this->urlUtils->combineURL(self::GET_AUTH_CODE_URL, $keysArr);

        header("Location:$login_url");
    }

    public function qq_callback() {//echo 33111;exit;
//        $state = $this->recorder->read("state");
//
//        //--------验证state防止CSRF攻击
//        if($_GET['state'] != $state){
//            $this->error->showError("30001");
//        }
        //损失安全性能
        if ($_GET['state'] != 1) {//echo 555;
            $this->error->showError("30001");
        }

        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
//             "client_id" => $this->recorder->readInc("appid"),
            "client_id" => '101267345',
//             "redirect_uri" => urlencode($this->recorder->readInc("callback")),
//             "redirect_uri" => urlencode('http://www.cgyyg.com/otherlogin/index.php/home/index/qqCallback'),
//             "client_secret" => $this->recorder->readInc("appkey"),
            // "redirect_uri" => urlencode('http://test.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/qqCallback'),
            "redirect_uri" => urlencode(self::CALLBACK_URL),
            "client_secret" => '07b168488647071c608659bdedb078e5',
            "code" => $_GET['code']
        );

        //------构造请求access_token的url
        $token_url = $this->urlUtils->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->urlUtils->get_contents($token_url);

        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response);

            if (isset($msg->error)) {
                $this->error->showError($msg->error, $msg->error_description);
            }
        }

        $params = array();
        parse_str($response, $params);

        $this->recorder->write("access_token", $params["access_token"]);
        return $params["access_token"];
    }

    public function get_openid() {

        //-------请求参数列表
        $keysArr = array(
            "access_token" => $this->recorder->read("access_token")
        );

        $graph_url = $this->urlUtils->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->urlUtils->get_contents($graph_url);

        //--------检测错误是否发生
        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($response);
        if (isset($user->error)) {
            $this->error->showError($user->error, $user->error_description);
        }

        //------记录openid
        $this->recorder->write("openid", $user->openid);
        return $user->openid;
    }

}
