<?php
/**
* 
*/
namespace Addons\QuickLogin\Controller;
use Home\Controller\AddonsController; 
use Org\ThinkSDK\ThinkOauth;

class OauthController extends AddonsController{
	public function _initialize(){
		$addon_config = $this->getConfig();
		// QQ互联 sdk配置
		$qq_configs = array(
			'APP_KEY' => $addon_config['qq_qzone_akey'],
			'APP_SECRET' => $addon_config['qq_qzone_skey'],
			'CALLBACK' => 'http://'.$_SERVER['HTTP_HOST'].U('ThirdLogin/callback').'?type=getQqAT',
            //'CALLBACK' => 'http://www.cgyyg'.U('ThirdLogin/callback').'?type=getQqAT',
            //'CALLBACK' => 'http://www.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/qqCallback?sss'
			);
		C('THINK_SDK_QQ',$qq_configs);

		// 新浪微博 sdk配置
		$sina_configs = array(
			'APP_KEY' => $addon_config['sina_wb_akey'],
			'APP_SECRET' => $addon_config['sina_wb_skey'],
			'CALLBACK' => 'http://'.$_SERVER['HTTP_HOST'].U('ThirdLogin/callback').'?type=getSinaAT'
			);
		C('THINK_SDK_SINA',$sina_configs);
        
		// 微信 sdk配置
		$weixin_configs = array(
			'APP_KEY' => $addon_config['weixin_wb_akey'],
			'APP_SECRET' => $addon_config['weixin_wb_skey'],
			//'CALLBACK' => 'http://www.cgyyg.com'.U('ThirdLogin/callback').'?type=getWeixinAT',
            'CALLBACK' => 'http://'.$_SERVER['HTTP_HOST'].U('ThirdLogin/callback').'?type=getWeixinAT',
			);
		C('THINK_SDK_WEIXIN',$weixin_configs);
		// 微信 sdk配置
		$weixin_gz_configs = array(
			'APP_KEY' => $addon_config['weixin_gz_akey'],
			'APP_SECRET' => $addon_config['weixin_gz_skey'],
			'CALLBACK' => 'http://www.cgyyg.com'.U('ThirdLogin/callback').'?type=getWeixinGzAT',
            //'CALLBACK' => 'http://'.$_SERVER['HTTP_HOST'].U('ThirdLogin/callback').'?type=getWeixinGzAT',
			);
		C('THINK_SDK_WEIXINGZ',$weixin_gz_configs);
        
	}
    
    
	// QQ互联 登陆
	public function qq(){

		//加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = ThinkOauth::getInstance('qq');
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
		
	}
    
    
	// QQ互联回调地址
	public function getQqAT(){
		$code = I('get.code');
		return $this->login('qq',$code);
	}


	// 新浪微博登陆
	public function sina(){
		//加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = ThinkOauth::getInstance('sina');
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
	}

	// 新浪微博回调地址
	public function getSinaAT(){
		$code = I('get.code');
		return $this->login('sina',$code);
	}
    
    
	// 微信登陆
	public function weixin(){
		//加载ThinkOauth类并实例化一个对象

        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = ThinkOauth::getInstance('weixin');
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
	}

	// 微信回调地址
	public function getWeixinAT(){
		$code = I('get.code');
		return $this->login('weixin',$code);
	}
    
	// 微信公众号登陆
	public function weixinGz(){
		//加载ThinkOauth类并实例化一个对象

        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = ThinkOauth::getInstance('weixinGz');
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
	}

	// 微信回调地址
	public function getWeixinGzAT(){
		$code = I('get.code');
		return $this->login('weixinGz',$code);
	}
	/**
	 * 用户登陆
	 */
	public function login($type = null, $code = null){
		(empty($type) || empty($code)) && $this->error('参数错误');
        
        //加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = ThinkOauth::getInstance($type);

        //腾讯微博需传递的额外参数
        $extend = null;
        
        //请妥善保管这里获取到的Token信息，方便以后API调用
        //调用方法，实例化SDK对象的时候直接作为构造函数的第二个参数传入
        //如： $qq = ThinkOauth::getInstance('qq', $token);
        $token = $sns->getAccessToken($code , $extend);
        //获取当前登录用户信息
        if(is_array($token)){
            $user_info = A('Addons://QuickLogin/Type', 'Event')->$type($token);
            return $user_info;

        }
        
	}
    
    
	public function FunctionName($value='')
	{
		# code...
	}
    
    
    /**
     * 获取插件的配置数组
     */
    final public function getConfig(){
        static $_config = array();
        $name = 'QuickLogin';
        if(isset($_config[$name])){
            return $_config[$name];
        }
        $config =   array();
        $map['name']    =   $name;
        $map['status']  =   1;
        $config  =   M('Addons')->where($map)->getField('config');
        if($config){
            $config   =   json_decode($config, true);
        }else{
        	return false;
        }
        $_config[$name]     =   $config;
        return $config;
    }
}
?>