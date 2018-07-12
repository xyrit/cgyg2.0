<?php
namespace Wap\Controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/18
 * Time: 10:44
    单元测试入口
 */

class TestController extends HomeController
{

    /* 订单支付  */

    public function testpay(){

    $orders=array(
        'pay_type'=>1,             //支付类型   1 余额支付  2线上支付
        'orders'=>array(
            '0'=>array(
            'lottery_id'=>'10000851',
            'attend_count'=>'10',
            'attend_ip'=>'11111111111',
            'ip_address'=>'asdasd',
            'attend_device'=>'电脑',
            ),
            '1'=>array(
                'lottery_id'=>'10000851',
                'attend_count'=>'10',
                'attend_ip'=>'11111111111',
                'ip_address'=>'asdasd',
                'attend_device'=>'电脑',
            ),
        )
    ) ;
    $rs= A('Shop')->pay_add($orders);
        dump($rs);
    }

        //获取用户IP地址
    public function addr()
    {
      $c= get_device();
        dump($c);
    }
}
