<?php
class onecaiAction extends BackendAction{
	protected $status = array(0,1,2,3,4,5,6,7,8,9,10,11);
	public function _initialize() {
		set_time_limit(0);
        parent::_initialize();
        $this->_mod = D('items');
        $this->_cate_mod = D('items_cate');
    }

    public function index(){
		exit;
	}
    public function zhe(){
	  $cates = $this->_cate_mod->field('id,name,pid')->select();
	  $this->assign('cates',$cates);
	  $this->display('onezhe');
	}
    public function begin(){
		if($this->_post('cates','trim')==null){
         $this->ajaxReturn(4,'','请选择自身分类!');
		}elseif($this->_post('zh','trim')==null){
		 $this->ajaxReturn(5,'','请选择目标分类!');
		}
     $fenlei=array(
     	'cates' =>$this->_post('cates','trim'),
     	'zh'=> $this->_post('zh','trim')
     	);
     F('fenlei',$fenlei);//缓存分类信息
	   //开始获取目标网站当前分类所有宝贝数据。
      if (intval($fenlei['zh'])==0) {
      	$mdata = $this->jpc_allitems($fenlei['zh']);
      }elseif (intval($fenlei['zh'])>0) {
      	$mdata = $this->getdata($fenlei['zh']);
      }
	   if ($mdata==2) {
	   	$this->ajaxReturn(2,'','无法从目标网站获取有效数据,请稍后尝试!');
	   }elseif ($mdata==3) {
	   	 $this->ajaxReturn(3,'','数据已获取成功,单处理失败!');
	   }

        // if (is_string($fenlei['zh'])) {
	       //  foreach($rz as $k=>$v){
	       //     $temp=explode('||',$v);
	       //     foreach ($temp as $kk => $vv) {
	       //     	    if    ($kk==0)    $ttemp[$k]['num']=$vv;
	       //     	    elseif($kk==1)    $ttemp[$k]['url']=$vv;
	       //     	    elseif($kk==2)    $ttemp[$k]['type']=$vv;
	       //     	    elseif($kk==3)    $ttemp[$k]['price']=$vv;
	       //     	    elseif($kk==4)    $ttemp[$k]['curprice']=$vv;
	       //     	    elseif($kk==5)    $ttemp[$k]['picurl']=$vv;
	       //     	    elseif($kk==6)    $ttemp[$k]['roc']=$vv;
	       //     	    elseif($kk==7)    $ttemp[$k]['title']=$vv;
	       //     }
	       //  }        	
        // }else
   //      if (is_numeric($fenlei['zh'])) {
   //      		   //去除掉完全重复的宝贝
			// foreach($mdata as $k=>$v){
		 //           $temp[] = implode('||',$v);
		 //       }
		 //        $rz = array_unique($temp);
		 //        $temp =array();
	  //       foreach($rz as $k=>$v){
	  //          $temp=explode('||',$v);
	  //          foreach ($temp as $kk => $vv) {
	  //          	    if    ($kk==0)    $ttemp[$k]['url']=$vv;
	  //          	    elseif($kk==1)    $ttemp[$k]['title']=$vv;
	  //          	    elseif($kk==2)    $ttemp[$k]['price']=$vv;
	  //          	    elseif($kk==3)    $ttemp[$k]['curprice']=$vv;
	  //          	    elseif($kk==4)    $ttemp[$k]['picurl']=$vv;
	  //          	    elseif($kk==5)    $ttemp[$k]['roc']=$vv;
	  //          	    elseif($kk==6)    $ttemp[$k]['type']=$vv;
	  //          	    elseif($kk==7)    $ttemp[$k]['num']=$vv;
	  //          }
	  //       }
	  //       $mdata= $ttemp;        	
   //      }
	   $allnum = count($mdata);
       $this->assign('allnum',$allnum);
	   foreach($mdata as $k=>$v){
        //检测与数据库的数据是否重复
		$num = $this->_mod->where(array('num_iid'=>$v['num']))->find();
	    if(is_array($num)&&$num!=null){
	    	unset($mdata[$k]);
	    }elseif(($v['num']=='')||($v['title']=='')||($v['picurl']=='')||($v['curprice']=='')||($v['price']=='')){
	    	unset($mdata[$k]);
	    }
	   }
	   if(empty($mdata)){
	   	$this->ajaxReturn($this->status[0],'','没有可添加商品');
	   }
	   //缓存采集数据
	   F('mdata',$mdata);
	   $numm = count($mdata);
	   $this->assign('num',$numm);
	   $this->assign('cf',$allnum - $numm);
	   $this->assign('items',$mdata);
       $rz= $this->fetch('onebfbegin');
       $this->ajaxReturn($this->status[1],'',$rz);
	}

  public function additems(){
  	$suc = 0;
  	$err = 0;
  	$mdata = F('mdata');
  	$fenlei= F('fenlei');
  	if (empty($mdata)) {
  		$this->ajaxReturn($this->status[11],'','请先采集数据!');
  	}
  	//获取父分类pid
      $_pid = $this->_cate_mod->where(array('id' =>$fenlei['cates']))->field('pid')->find();
      $pid  = is_numeric($_pid['pid'])?$_pid['pid']:0;  
       //数据入库
	foreach($mdata as $k=>$v){
		//优惠开始时间
		$add   = time();
		$yb    = $add + 5;
		$ye    = $yb  + 3600*24*10;
		$sqlarr = array(               
		 'ordid'=>9999,
		 'cate_id'=>$fenlei['cates'],
		 'orig_id'=>$pid,
		 'title' =>$v['title'],//标题
		 'uid'   =>1,              
		 'uname'  =>'admin',             
		 'pic_url' =>$v['picurl'],//图片链接
		 'price'   =>$v['price'],//实际价格
	//	 'click_url' =>$v[3],//推广地址          
		 'volume'=>rand(100,3000),//数量
		 'coupon_price'=>$v['curprice'],//优惠价格
		 'coupon_rate'=>ceil(($v['price']/$v['curprice'])*10000),//折扣比率
		 'coupon_start_time'=>$yb,//优惠开始时间
		 'coupon_end_time'=>$ye,//优惠结束时间
		 'pass'=>1,//是否通过审核
		 'status'=>'underway',
		 'shop_type'=>$v['type'],//店铺类型
		 'ems'=>1,//是否包邮
		 'hits'=>rand(50,1000),//点击次数
		 'isshow'=>1,//是否显示
		 'likes'=>rand(1000,3000),
		 'seo_title'=>$v['title'],
		 'add_time'=>$add,
		 'num_iid'=>$v['num'],
	     'seo_desc'=>$v['roc']
		);
        if ($this->_mod->add($sqlarr)) {
        	$mdata[$k]['status']=1;
        	$suc++;
        }else{
        	$mdata[$k]['status']=0;
        	$err++;
        }
        usleep(200000);
    }

    $this->assign('items',$mdata);
    $this->assign('suc',$suc);
    $this->assign('err',$err);
    $rz= $this->fetch('onebegin');
    F('mdata',array());//清空临时缓存
    $this->ajaxReturn($this->status[1],'',$rz);
  }














/*****************************************************************************************************/
            /*******************************折800采集*******************************/
/*****************************************************************************************************/
//获取数据
	/***
参数:string
返回:array 二维数组。所有采集数据

***/
protected function getdata($tag_id){
	$ddata = array();
	$str ='http://zhe800.uz.taobao.com/list.php?page=1&tag_id='.$tag_id;
	$rz = file_get_contents($str);
	$rz = iconv('gbk','utf-8',$rz);
	//file_put_contents('./txt.txt',$rz);
	//exit;
	//检测是否有分页信息,如果没有则不在进行分页处理
	$pre_pageinfo = '/<div class=\"page_div clear area page_bottom\">/s';
	preg_match($pre_pageinfo,$rz,$pageinfo);
	if(is_array($pageinfo)&&$pageinfo!=null){
	 //获取总页数
	 $pre_allpages = '/<div class=\"page_div clear area page_bottom\">(.*?)<\/div>/s';
	  preg_match($pre_allpages,$rz,$pagedata);
	  $pre_pages   = '/list.php\?page=(\d+?)\&/s';
	  preg_match_all($pre_pages,$pagedata[1],$pages);
	  $pages = count($pages[1]) - 1;

		if(is_numeric($pages)&&$pages>0){
			//获取需要采集的所有页面$allpages_url.
			$allpages_url = array();
			$url1 ='http://zhe800.uz.taobao.com/list.php?page=';
			$url2 = '&tag_id='.$tag_id;
			for($i=0;$i<$pages;$i++){
			 $allpages_url[$i] = $url1.$i.$url2;
			}
		}
		//------------获取原始数据-------------
		$data = $this->getgoodsdata($allpages_url);
		if(empty($data)) {
			return $this->status[2];
		}
		//------------处理原始数据-------------
		$ddata = $this->deal($data);
	    if(empty($ddata)||($ddata===false)) {
	    	return $this->status[3];
	    }
	}else{
	 $allpages_url = $str;
		//------------获取原始数据-------------
		$data = $this->getgoodsdata($allpages_url);
		if(empty($data)) {
			return $this->status[2];
		}
		//------------处理原始数据-------------
		$ddata = $this->deal($data);
		if(empty($ddata)||($ddata===false)) {
			return $this->status[3];
		}
	}
	return $ddata;
}
/***
 参数:string||array->待采集的所有页面
 返回:array->返回采集的原始数据
***/
protected function getgoodsdata($url){
	static $curdata = array();
	if(is_array($url)&&$url!=null){
		 foreach($url as $v){
			//获取当前页面数据
			@$curpage = file_get_contents($v);
			$curpage = iconv('gbk','utf-8',$curpage);
			//获取当前页面的所有商品
			$pre_allgoods = '/<div class=\"dealinfo\">(.*?)<\/div>/is';
			preg_match_all($pre_allgoods,$curpage,$arrdata);
			if(is_array($arrdata[1])&&$arrdata[1]!=null){
			 $curdata = array_merge($curdata,$arrdata[1]);
			}
			sleep(5);
		}
	}
	if(is_string($url)&&$url!=null){
	       //获取当前页面数据
			$curpage = file_get_contents($url);
			$curpage = iconv('gbk','utf-8',$curpage);
			//获取当前页面的所有商品
			$pre_allgoods = '/<div class=\"dealinfo\">(.*?)<\/div>/is';
			preg_match_all($pre_allgoods,$curpage,$arrdata);
			if(is_array($arrdata[1])&&$arrdata[1]!=null){
			 $curdata = $arrdata[1];
			}
	}
	return $curdata;
}
/***
参数:array ->原始数据信息
返回:array ->干净的数据
***/
protected function deal($data){
 $alldata = array();//储存所有处理后的数据
 $gdata = array();//储存处理后的数据
 if(!is_array($data)) {die('为获取到原始数据');};
   $pre_title    = '/<h2>.*?\/strong.*?href=\"(.*?)\">(.*?)<\/a>/s';//获得链接和标题	  
   // $pre_price    = '/<h3>.*?<i>(.*?)<\/i>/us';//获得原价
   $pre_curprice = '/<h4>.*?<span>.*?<\/b>(.*?)<\/span>.*?<i>.*?(\d+?)<\/i>.*?<\/h4>/s';//获得当前价格
   $pre_picurl   = '/<img.*?src=\"(.*?)\"/s';//获取图片地址
   $pre_roc      = '/<h6.*?<\/em>(.*?)<\/h6>/s';//获取推荐语
   $pre_type     = '/tmall/i';//获取店铺类型
   $pre_num      = '/http.*?id=(\d+)/';//获取商品id   
  //开始处理数据
foreach($data as $v){
	// print_r($v);exit;
	 preg_match($pre_title,$v,$tu);
	 $gdata['url']   = $tu[1];
	 $gdata['title'] = $tu[2];
	 // preg_match($pre_price,$v,$price);
	 // $gdata['price'] = $price[1];
	 preg_match($pre_curprice,$v,$curprice);
	 $gdata['price'] = $curprice[2];
	 $gdata['curprice'] = trim(strip_tags($curprice[1]));
	 preg_match($pre_picurl,$v,$picurl);
	 //进一步处理图片
	 $picurl[1] = substr($picurl[1],0,strrpos($picurl[1], '_'));
	 $gdata['picurl']  = $picurl[1];
	 
	 preg_match($pre_roc,$v,$roc);
	 $gdata['roc']  = trim($roc[1]);
	 //判断店铺类型
	 preg_match($pre_type,$tu[1],$type);
	 if(is_array($type)&&($type!=null)){
	  $gdata['type'] = 'B';
	 }else{
	  $gdata['type'] = 'C';
	 }
	 //获取商品id
	 preg_match($pre_num,$tu[1],$num);
	 $gdata['num'] = $num[1];
	 $alldata[]= $gdata;
	}
	// p($gdata);
	if(empty($alldata)) return false;
	return $alldata;
}

/*****************************************************************************************************/
            /*******************************卷皮尺采集*******************************/
/*****************************************************************************************************/
protected function jpc_allitems($item){
  
  $rz = $this->jpc_getdata($item);//获取页面内容
  if (!$rz) {return $this->status[2];}
  $or_items = $this->jpc_getordata($rz,$item);//
  if (!$or_items) {return $this->status[3];}
  $items = $this->jpc_deal($or_items);
  if (!$items) {return $this->status[3];}
  return $items;
}
/***
  @获取指定url页面内容
  参数:string eg.url
  返回:string
***/
protected function jpc_getdata($item){
if (strlen($item)>26) {$url=$item;}else{$url = 'http://juanpi.uz.taobao.com/d/index?u=index/all/'.$item;}
	$fn = curl_init();//初始化链接句柄
	//参数设置
	curl_setopt($fn,CURLOPT_URL,$url);
	curl_setopt($fn,CURLOPT_TIMEOUT,30);//超时时间30秒防止卡死
	curl_setopt($fn,CURLOPT_RETURNTRANSFER,1);//以文件流的形式返回数据而不是直接显示
	curl_setopt($fn,CURLOPT_HEADER,0);

	$fm = curl_exec($fn);//执行句柄
	curl_close($fn);//关闭句柄
	//转换文档编码为UTF8
    $res = iconv('gbk','utf-8',$fm);
	if(($res==false)||($res==null)){
	  return false;
	}
	return $res;
}
/***
 @处理获取的网页内容
 参数:string,string
 返回:array 单个宝贝的最初数据
***/
protected function jpc_getordata($str='',$item=''){
  if(($str=='')||($str==false)||($item=='')){
   return false;
  }
  $url = 'http://juanpi.uz.taobao.com/d/index?u=index/all/'.$item;
  //匹配分页数量
  $pre_page_1 = '/<div class=\"page\">(.*?)<\/a>/si';
  preg_match($pre_page_1,$str,$temp);
  if (is_array($temp)&&!empty($temp)&&isset($temp[0])) {
    $pre_page_2 ='/<div class=\"page\">.*?(\d)<\/a>/si'; 
      preg_match($pre_page_2,$temp[0],$temps);
      if(is_array($temps)&&!empty($temps)&&isset($temp[1])&&$temps[1]>1){
            for ($i=1; $i <$temps[1]+1 ; $i++) { 
              $url_all[] = $url.'/'.$i; 
            }
      }
      //处理数据得到原始宝贝信息
       $items = $this->jpc_dealor($url_all);
       if (!is_array($items)||!$items) {
         return false;
       }else{
        return $items;
       }
  }else{
      $url_all = $url;
      //处理数据得到原始宝贝
       $items = $this->jpc_dealor($url_all);
       if (!is_array($items)||!$items) {
         return false;
       }else{
        return $items;
       }
  }

  //返回原始数据
}

/***
处理页面信息
  参数:string||array
  返回array
***/
protected function jpc_dealor($str=''){
  //判断页数情况
  $cont = '';
  if (is_array($str)&&$str!='') {
    foreach ($str as $k => $v) {
      $cont .= $this->jpc_getdata($v);
      sleep(2);
    }
    //匹配所有宝贝
    $pre_items = '/<li class=\"goods-box(.*?)<\/li>/si';
    preg_match_all($pre_items, $cont, $temp);
    if (is_array($temp)&&!empty($temp)&&isset($temp[0])) {
      return $temp[0];
    }else{
      return false;
    }
  }elseif (is_string($str)&&$str!='') {
    $cont = $this->jpc_getdata($str);
    //匹配所有宝贝
    $pre_items = '/<li class=\"goods-box(.*?)<\/li>/si';
    preg_match_all($pre_items, $cont, $temp);
    if (is_array($temp)&&!empty($temp)&&isset($temp[0])) {
      return $temp[0];
    }else{
      return false;
    }
  }else{
    return false;
  }

}


/***
  处理原始数据
  参数:array
  返回array
***/
protected function jpc_deal($arr=''){
  $data_all = array();//储存所有处理过的数据
  $data     = array();//储存单个处理过的数据
 if (($arr=='')||!is_array($arr)) {
   return false;
 }
 //匹配商品详情信息:原始价格，当前价格，标题+促销信息，单品id，pic链接
 $pre_info = array(
   'url'    => '/<div class=\"btn buy\">.*?\"(http.*?)\">/s',//获得链接    
   'price'    => '/<span class=\"price-old\">.*?<\/em>(.*?)</us',//获得原价
   'curprice' => '/<span class=\"price-current\">.*?<\/em>(.*?)</s',//获得当前价格
   'picurl'   => '/<img.*?src=\"(.*?)\"\/>/s',//获取图片地址
   'roc'      => '/<div class=\"title-tips\">(.*?)</s',//获取推荐语
   'title'    => '/<h5.*?>.*?<\/a>.*?>(.*?)<\/a>.*?<\/h5>/s',//获取标题
   'type'     => '/<em class=\"icon.*?<\/em>/s',//获取店铺类型
   'pictemp'  => '/^http.*?(http.*)$/s'//处理意外情况
  );
 foreach ($arr as $k => $v) {
  preg_match($pre_info['url'],$v,$tempurl);
  //获商品id
  if (isset($tempurl[1])) {$data['num'] = substr($tempurl[1],strpos($tempurl[1], 'id=')+3);$data['url']=$tempurl[1];}
  //获取店铺类型
  preg_match($pre_info['type'], $v,$temptype);
  if(isset($temptype[0])&&strpos($temptype[0],'tao-n')>1) {$data['type']='C';}else{$data['type']='B';}
  preg_match($pre_info['price'], $v,$tempp);
  //获取原价
  if (isset($tempp[1])) {$data['price'] = trim($tempp[1]);}else{$data['price'] = '';}
  preg_match($pre_info['curprice'], $v,$tempc);
  //获取当前价
  if (isset($tempc[1])) {$data['curprice'] = trim($tempc[1]);}else{$data['curprice'] = '';}  
  preg_match($pre_info['picurl'], $v,$temppic);
  //获取图片链接
  if (isset($temppic)) {
    if (strrpos($temppic[1], 'juanpi')>1) {
     preg_match($pre_info['pictemp'],$temppic[1],$temp);
     $temppic[1] = $temp[1];
    }
    $data['picurl'] = substr($temppic[1],0,strrpos($temppic[1], '_'));}else{$data['picurl'] = '';
  }
  preg_match($pre_info['roc'], $v,$temproc);
  //获取推荐语
  if (isset($temproc[1])) {$data['roc'] = trim($temproc[1]);}else{$data['roc'] = '';}
  preg_match($pre_info['title'], $v,$tempt);
  //获取标题
  if (isset($tempt[1])) {$data['title'] = strip_tags(trim($tempt[1]));}else{$data['title'] = '';}
  $data_all[] = $data;
 }
  if (!empty($data_all)) {return $data_all;}elseif (empty($data_all)) {return false;}else{return false;}
}
}
?>


