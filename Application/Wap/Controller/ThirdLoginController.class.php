<?php

/*
 *  第三方登陆类
 * @2016年4月12日 15:30:01
 */

namespace Wap\Controller;

class ThirdLoginController extends HomeController
{

    /**
     * 授权页面
     *	type参数说明:
     *  微信 weixin 
     *  微信公众号 weixingz 
     *  qq 
     *  sina
     */  
    public function auth(){
        $type = trim(I('get.type'));
        A('Addons')->execute('QuickLogin','Oauth',$type);
    }
    
    
    /**
     * 授权回调
     * 
     */ 
    public function callback(){
        //获取用户信息
        $type = trim(I('get.type'));
        $user_info = A('Addons')->execute('QuickLogin','Oauth',$type);
        if($user_info['open_id']){
            //获取第三方用户表
            $third_config = array('getQqAT'=>1,'getWeixinAT'=>2,'getSinaAT'=>3);
            $condition = array('open_id'=>$user_info['open_id'],'type'=>$third_config[$type]);
            $third_user = D('ThirdMember')->where($condition)->find();
            if($third_user){
                D('Member')->login($third_user['uid']);
                header('Location:'.U('User/index'));    
            }else{
                //使用加密串加密验证
                $sign = think_encrypt($user_info['open_id'],'THIRD_BIND'); 
                header('Location:'.U('User/thirdBind').'?open_id='.$user_info['open_id'].'&type='.$third_config[$type].'&sign='.$sign.'&nickname='.$user_info['name'].'&headimgurl='.$user_info['head']);  
            }

        }else{
            $this->error('获取第三方信息失败，请重试！',U('User/login'));    
        }

    }
    
    
    
    
    
    
    
    
    


}

