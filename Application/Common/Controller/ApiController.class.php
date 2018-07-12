<?php
namespace Common\Controller;
use Think\Controller\Rest;
class ApiController extends Rest{
    protected $number;                                                         //当前请求的数量
    protected $max_id;                                                         //上翻最大ID
    protected $since_id;                                                       //下拉最大ID
    protected $uid;                                                               //用户ID
    protected $phone_code;                                                 //用户手机唯一标示
    protected $catid;                                                            //分类ID
    protected $max_time;                                                    //上翻请求当前时间
    protected $min_time;                                                     //下拉请求当前时间
    protected $allowMethod = array('get','post','put');     //REST允许的请求类型列表
    protected $allowType = array('html','xml','json');       //REST允许请求的资源类型列表
    /**
     * 架构函数
     * @access public
     */
    function __construct(){
        //初始化参数
        $this->number = I('get.number',10,'intval');
        $this->max_id = I('get.max_id',0,'intval');
        $this->since_id = I('get.since_id',0,'intval');
        $this->uid = I('get.uid',0,'intval');
        $this->phone_code = I('get.phone_code','','trim');
        $this->catid = I('get.catid',0,'intval');
        $this->max_time = I('get.max_time',0,'intval');
        $this->min_time = I('get.min_time',0,'intval');
        //读取配置信息(缓存)
        if(false === $config = S('DB_CONFIG_DATA')){
            $config = api('Config/lists');                       //生成配置列表
            S('DB_CONFIG_DATA',$config);              //重新生成缓存
        }
        C($config);                                                      //批量配置全局*/
        parent::__construct();
       // $this->qiangzhi();
    }
    public function qiangzhi(){
        $this->apiSuccess('强制更新');
    }
    /**
     * 封装where查询条件
     * @param $field 需求字段
     * @return array
     */
    protected function get_where($field){
        if($this->since_id && !$this->max_id){
            $map[$field] = array('gt',$this->since_id);
        }elseif(!$this -> since_id && $this->max_id){
            $map[$field] = array('lt',$this->max_id);
        }else{
            $map = array();
        }
        return $map;
    }
    /**
     * 生成json返回信息
     * @param boole $success 成功或失败
     * @param int $error_code 错误码,如果$success为true,这里为0
     * @param string $message 提示内容
     * @param array $extra 输出需要的返回值
     */
    protected function apiReturn($success,$error_code=0,$message=null,$extra=null){
        $result = array();
        $result['success'] = $success;
        $result['error_code'] = $error_code;
        if($message !== null){
            $result['message'] = $message;
        }
        foreach($extra as $key=>$value){
            $result[$key] = $value;
        }

        //将返回信息进行编码
        $this->response($result);
    }
    /**
     * 正确的返回请求
     * @param string $message 提示信息
     * @param mixed $extra 数据列表
     */
    protected function apiSuccess($message,$extra=null){
        return $this->apiReturn(true,'0',$message,$extra,$type);
    }
    /**
     * 错误的返回请求
     * @param int $error_code 错误编码
     * @param string $message 提示信息
     * @param mixed $extra 数据列表
     */
    protected function apiError($error_code,$message,$extra=null){
        return $this->apiReturn(false,$error_code,$message,$extra);
    }

    /**
     * 检查签名认证,根据URL检测当前请求是否非法
     */
    protected function _checkSign(){
        if(C("SYSTEM_SIGN_OPEN") == '1'){               //签名认证开关
            $callback = IS_GET ? $_GET : $_POST;			//请求类型和获取参数
            $sign = $callback['sign'];					                //获取签名
            !$sign && $this->apiError("100002","签名不存在");
            unset($callback['sign']);				                    //去除签名参数
            ksort($callback);								                //按字母升序重新排序
            $sequence = '';									                //定义签名数列
            foreach($callback as $k=>$v){			                //拼接参数
                $sequence .= "{$k}={$v}";
            }
            $sequence .= C("SYSTEM_KEY");						//拼接key
            $sequence = md5($sequence);			                //加密
            if($sign != $sequence){                                     //签名检测
                $this->apiError("100004","签名不正确");
            }
        }
        return true;
    }

}