<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// |  Author: 烟消云散 <1010422715@qq.com> 
// +----------------------------------------------------------------------

namespace Wap\Controller;

//use OT\DataDictionary;
//use User\Api\UserApi;
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
  $url= $_SERVER[HTTP_HOST]; //获取当前域名
 */
class IndexController extends HomeController {
    /*     * 系统首页* */

    public function index() {

        /*
         * 取出图片
         */
//        $list = M("document_product")->field("id,pics")->select();
//        foreach ($list as $k => $v) {
//            $pic_arr = explode(",", $list[$k]["pics"]);
//            foreach ($pic_arr as $k2 => $v2) {
//                $pic_id = $pic_arr[$k2];  //取出逗号隔开的id
//                $path = M("picture")->field("path")->where(array("id" => $pic_id))->find();
//                $path = $path["path"];
//                if (!empty($path)) {
//                    $path.="," . $path;
//                }
//            }
//            $test = M("document_product d")->where("d.id=" . $list[$k]["id"])->save(array("pics" => $path));
//        }

        $slide_list = D('Slide')->get_slide();

        $this->assign('slide_list', $slide_list);

        $category_tree = S('category_tree');
        if (!$category_tree) {
            $category_tree = D('Category')->getCategory();
            S('category_tree', $category_tree);
        }
        $this->assign('category_tree', $category_tree);

        $goods_data = D('Goods')->getGoodsList(1,20);
        foreach($goods_data['data'] as &$v){
             $v['thumb']=getfullImg($v['thumb']);
        }

        $this->assign('goods_data',$goods_data['data']);
       	//print_r($goods_data);
        $this->meta_title='首页';
		$this->display();
    }



    public function getGoodlist($cateid = null) {
        $cateid = I('cateid', 0, 'intval'); // 用intval过滤$_POST['id'];  
        $data = D('Category')->getDatalist($cateid);
        $this->ajaxReturn($data);
    }

}
