<?php

/*
 *  第三方登陆类
 * @2016年4月12日 15:30:01
 */

namespace Home\Controller;

class ThirdLoginController extends HomeController
{

    /**
     * 授权页面
     *
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
        A('Addons')->execute('QuickLogin','Oauth',$type);
        
        

    }


}

