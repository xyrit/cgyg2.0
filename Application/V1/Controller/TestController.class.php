<?php
namespace V1\Controller;
use Common\Controller\ApiController;
use Think\Controller;
/**
 * 
 */
class TestController extends ApiController {

    public function test_info(){
  //  echo 1;
  //  $this->apiError('20012','返回失败');
        $parm=I();
        $data['items']=$parm;

     $this->apiSuccess('返回成功',$data);
    }
    public function test_error(){
        $this->apiError('12','系统错误');
    }
}




?>