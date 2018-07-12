<?php
namespace Wap\Controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/5/12
 * Time: 13:44
 *
 * 第三方登录
 */

class ConnectController extends HomeController {


    //以手机号判断 用户是绑定还是注册
    public function JudgeUserEidt()
    {
        $this->meta_title = '手机验证';
        $users= json_decode($_COOKIE['users'],1);

        $this->assign('users',$users);
        $this->display();
    }

    public function JudgeUserAction()
    {
         $mobile=I('mobile');
        if(!$mobile)
        {
            $result['code']=500;
            $result['info']='手机号码不存在';
            $this->ajaxReturn($result);
        }
         $yzcode=I('yzm');

        $result= A('User')->checkRegisterVerify($mobile,$yzcode,1);
           //  $result['code']=200;
            if($result['code']==200)
            {
            //查询手机号是否存在
               $member= M('member')->where('mobile='.$mobile)->find();
                if($member)
                {
                  //查询是否已经绑定过
                $opid= M('cnnect')->where('uid='.$member['uid'].' and type="weixin"')->getField('openid');
                if($opid){

                    //提示换绑或者手机登录
                    $result['code']=201;
                    $result['info']='您的微信帐号已经绑定过,是否更换绑定？';
                    $result['btn']=1;
                    session('con_uid',$member['uid']);
                }else {
                    //绑定帐号   //执行登录

                    $users= json_decode($_COOKIE['users'],1);
                    $time=time();
                    $data=array(
                        'uid'=>$member['uid'],
                        'type'=>'weixin',
                        'openid'=>$users['openid'],
                        'binding_time'=>$time,
                        'info'=>$_COOKIE['users']
                    );
                    $rs=M('cnnect')->add($data);
                    if($rs)
                    {
                    A('user')->login_action($member['mobile'],$member['password']);
                    $result['code']=202;
                    }
                }

                }else{
                    //执行新增用户注册
                    $result['code']=203;
                    session('mobile',$mobile);
                }
            }
            $this->ajaxReturn($result);
    }



        //换绑操作
        public function ToChange(){

            //查询到已经登录的帐号
            $time=time();
            $users= json_decode($_COOKIE['users'],1);
            if(strlen($_COOKIE['users']))
            {
             $data=array(
                'openid'=>$users['openid'],
                 'binding_time'=>$time,
                 'unbinding_time'=>$time,
                 'info'=>$_COOKIE['users']
             );
          $rs= M('cnnect')->where("type='weixin' and uid=".session('con_uid'))->save($data);

                if($rs)
                {
                    A('wechat')->auto();
                    exit;
                }
         }

                 $this->error('绑定失败');

         }

        //新注册
        public function Register(){
            $this->meta_title = '注册';
            $mobile=session('mobile');
            $this->assign('mobile',$mobile);
            $this->display();
        }


        //注册绑定  调用

        public function ZcToChange($uid){

            if($_COOKIE['users'])
            {
            $time=time();
            $users= json_decode($_COOKIE['users'],1);
            $data=array(
                'uid'=>$uid,
                'type'=>'weixin',
                'openid'=>$users['openid'],
                'add_time'=>$time,
                'binding_time'=>$time,
                'info'=>$_COOKIE['users']
            );
                // 清空cookie
                setcookie('users',null,time()-3600,'/');
            $rs= M('cnnect')->add($data);
            return $rs;
        }
        }

}