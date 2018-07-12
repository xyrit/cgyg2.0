<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// |  Author: 烟消云散 <1010422715@qq.com> 
// +----------------------------------------------------------------------
namespace Home\Controller;
//use OT\DataDictionary;
//use User\Api\UserApi;
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 $url= $_SERVER[HTTP_HOST]; //获取当前域名  
 */
class IndexController extends HomeController {
 
	/**系统首页**/
    public function index(){
        $category_tree = S('category_tree');
        if(!$category_tree){
            $category_tree = D('Category')->getCategory();
            S('category_tree',$category_tree);
        }
        $this->assign('category_tree',$category_tree);
        $goods_data = D('Goods')->getGoodsList();
        $this->assign('goods_data',$goods_data);
       	//print_r($goods_data);
		$this->display();
	}

    public function getGoodlist($cateid=null){
        $cateid=I( 'cateid',0,'intval' ); // 用intval过滤$_POST['id'];  
		$data = D( 'Category' )->getDatalist( $cateid );
        $this->ajaxReturn($data);
    }
    
    
}

