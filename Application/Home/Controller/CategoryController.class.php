<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// |  Author: 烟消云散 <1010422715@qq.com> 
// +----------------------------------------------------------------------
namespace Home\Controller;
use OT\DataDictionary;
use User\Api\UserApi;
/**
 * 前台分类控制器
 * 
 $url= $_SERVER[HTTP_HOST]; //获取当前域名  
 */
class CategoryController extends HomeController {
 
	/**系统首页**/
    public function lists(){
        $category_tree = S('category_tree');
        if(!$category_tree){
            $category_tree = D('category')->getTree();
            S('category_tree',$category_tree);
        }
        $this->assign('category_tree',$category_tree);
        
        $goods_list = D('goods')->getGoodsList();
        //print_r($goods_list);
        $this->assign('goods_list',$goods_list);
        //print_r($tree);//exit;
		$this->display();
	}
    
    /*
    public function getGoodslist($cateid=null){
        $cateid=I( 'cateid',0,'intval' ); // 用intval过滤$_POST['id'];  
		$data = D( 'Category' )->getDatalist( $cateid );
        $this->ajaxReturn($data);
    }
    */
      
}


