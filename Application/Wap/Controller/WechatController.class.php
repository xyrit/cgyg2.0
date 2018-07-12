<?php
namespace Wap\Controller;
use Vendor\Wechat;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/5/11
 * Time: 15:04
 * 微信方法
 */

class WechatController extends HomeController {


        private $wx_config;

    public function __construct() {

        $this->wx_config=C('wx_config');


    }

    public function valid(){

        Vendor('Wechat.Wechat');

        $wechatObj =  new Wechat\Wechat($this->wx_config);
        $wechatObj->valid();

    }

        //微信跳转验证登录
    public function auto(){
        Vendor('Wechat.Wechat');
        $wechatObj =  new Wechat\Wechat($this->wx_config);
        $callback='http://m2.cgyyg.com'.U('Wechat/wxlogin');

        $state='1';
       $geturl=$wechatObj->getOAuthRedirect($callback,$state,$scope='snsapi_userinfo');

        header("Location: $geturl");
    }


    //微信回调登录页面
    public function wxlogin() {
        Vendor('Wechat.Wechat');
        $wechatObj =  new Wechat\Wechat($this->wx_config);
        //code获取Access Token
        $ac_token = $wechatObj->getOauthAccessToken();
        //换取用户信息
        $UserInfo = $wechatObj->getOauthUserInfo($ac_token['access_token'],$ac_token['openid']);
        if(!$UserInfo)
        {
            redirect('Index/index');exit;
        }
        $where1['openid']=$ac_token['openid'];
        $where1['type']='weixin';
        $uid=M('cnnect')->where($where1)->getField('uid');     //查询第三方表
        if($uid){
            //查询用户帐号密码
          $member=M('member')->where('uid='.$uid)->find();        //查询用户表
          $rs=  A('user')->login_action($member['mobile'],$member['password']);

            if(is_array($rs)){
          //      file_put_contents($rs['success_js']);   //执行同步登录

                $from_url = $_COOKIE['from_url']?$_COOKIE['from_url']:U('index/index');
                redirect($from_url);
            }else{
                redirect(U('user/login'));
            }
        }else{
           $sUserInfo= json_encode($UserInfo);
            //跳转到输入手机验证页面
            setcookie('users',$sUserInfo,time()+3600*24,'/');
            redirect(U('Connect/JudgeUserEidt'));
        }

    }







}