<?php
namespace Home\Controller;
use Think\Controller;

class PayController extends HomeController {
    
    /**
     * 支付页面
     * 
     */ 
	public function index() {
	   
        $config = VENDOR_PATH.'Jubaopay/Config/jubaopay.ini';
        Vendor("Jubaopay.Jubaopay");
        Vendor("Jubaopay.RSA");
        $jubaopay = new \vendor\Jubaopay\Jubaopay($config);
        
        $jubaopay->setEncrypt("payid", $payid); //订单号（保证唯一）
        $jubaopay->setEncrypt("partnerid", $partnerid); //商户号
        $jubaopay->setEncrypt("amount", $amount); //订单金额（单位：元）
        $jubaopay->setEncrypt("payerName", $payerName); //用户ID（保证唯一）
        $jubaopay->setEncrypt("goodsName", $payerName); //商品名称
        $jubaopay->setEncrypt("payMethod", $payerName); //支付方式
        
        
        $jubaopay->setEncrypt("remark", $remark); //备注
        $jubaopay->setEncrypt("returnURL", $returnURL);
        $jubaopay->setEncrypt("callBackURL", $callBackURL);
        
        //对交易进行加密=$message并签名=$signature
        $jubaopay->interpret();
        $message=$jubaopay->message;
        $signature=$jubaopay->signature;
        
        $this->assign('pay_params',array('message'=>$message, 'signature'=>$signature));
        $this->display();

	}
    
    /**
     * 服务器回调
     * 
     * 
     */ 
    public function notify()
    {
        $config = VENDOR_PATH.'Jubaopay/Config/jubaopay.ini';
        Vendor("Jubaopay.Jubaopay");
        Vendor("Jubaopay.RSA");
        $jubaopay = new \vendor\Jubaopay\Jubaopay($config);
        
        $message = I('post.message');
        $signature = ('post.signature');
        
        $jubaopay->decrypt($message);
        // 校验签名，然后进行业务处理
        $result = $jubaopay->verify($signature);
        if($result==1) {
           // 得到解密的结果后，进行业务处理
           // echo "payid=".$jubaopay->getEncrypt("payid")."<br />";
           // echo "mobile=".$jubaopay->getEncrypt("mobile")."<br />";
           // echo "amount=".$jubaopay->getEncrypt("amount")."<br />";
           // echo "remark=".$jubaopay->getEncrypt("remark")."<br />";
           // echo "orderNo=".$jubaopay->getEncrypt("orderNo")."<br />";
           // echo "state=".$jubaopay->getEncrypt("state")."<br />";
           // echo "partnerid=".$jubaopay->getEncrypt("partnerid")."<br />";
           // echo "modifyTime=".$jubaopay->getEncrypt("modifyTime")."<br />";
        	echo "success"; // 像服务返回 "success"
        } else {
        	echo "verify failed";
        } 
        
    }


}
