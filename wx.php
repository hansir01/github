<?php
class mobile_controller_wx extends mobile_controller{
	private $source = 1;
	function common($hid){

		$info = $this->xf_info->show($hid);
		$info['name'] = trim($info['name']);
		if (!$hid){
			$this->showmsg('该楼盘不存在','goback','err');
		}
		if ($info['status'] == 0){
			$this->showmsg('该楼盘尚未开通','goback','err');			
		}
		/********hanshaobo 5.6 ***********/
		if($info['isweixin'] == 0)
		{
			$this->showmsg('该楼盘尚未开通微信','goback','err');
		}
		/********hanshaobo 5.6 ***********/
		return $info;
	}
	function PageIndex(){
		$hid = intval($_GET['hid']);		
		$info = $this->common($hid);
		$this->template->assign($info);
		$url = url('mobile/wx',"hid=".$hid);//定义分享url
		$this->template->assign('url',$url);
		$this->template->display('index','wx');
	}
	function PageDetail(){
		$hid = intval($_GET['hid']);
		$info = $this->common($hid);
		$extra = loader::model("xf_info_extra");
		$property_company = $extra->get('hid = '.$hid,'parking_ratio,project_progress,property_company,info,support_school,support_shopping,support_medical,support_repast,support_financial,support_yule'); //物业公司
		$property_company['info'] = htmlspecialchars_decode($property_company['info']);
		$info = array_merge($info,$property_company);
		$slide = array();
		if ($info['titlepic'] >0){
			$pic = loader :: model("xf_album_picture", 'mobile');			
			$slide = $pic->get_slide($hid);
		}
		$this->template->assign('slide',$slide);
		$this->template->assign($info);
		$url = url('mobile/wx/detail',"hid=".$hid);//定义分享url
		$this->template->assign('url',$url);
		unset($extra,$property_company);
		$this->template->display('detail','wx');
	}
	function PageDynamic(){
		$hid = intval($_GET['hid']);	
		$info = $this->common($hid);
		$this->template->assign($info);
		$news = loader::lib('news','mobile');
		if ($_GET['contentid']){ //详情
			$contentid = intval($_GET['contentid']);			
			$xw = $news->get_newsinfo_by_contentid($contentid);
			/************hanshaobo 5.4************/
			if($xw) $xw['title'] = strip_tags($xw['title']);
			$ext = 'gif|jpg|jpeg|bmp|png';
			$xw['content'] = preg_replace(array('/(imgs\..*com\/)'.$this->_config['citycode'].'/i', '/(\d+)\.('.$ext.')/i'), array('$1wap/'.$this->_config['citycode'], '$1_320_240.$2'), $xw['content']);
			/************hanshaobo 5.4************/
			$this->template->assign('xw',$xw);
			
			//$slide = $news->get_picnews($hid,1,5); //获取幻灯
			
			//$this->template->assign('slide',$slide);
			$url = url('mobile/wx/dynamic',"hid=".$hid."&contentid=".$contentid);
			$this->template->assign('url',$url);			
			$this->template->display('news','wx');			
		}else {
			$cur_page = isset($_GET['Page'])?$_GET['Page']:1;
			$per_num = 2;
			$xw = $news->get_picnews($hid,$cur_page,$per_num);
			$count = $news->get_picnewscount($hid);
			$pagenum = ceil($count/$per_num);
			$this->template->assign('pagenum',$pagenum);
			/************hanshaobo 4.30************/
			if($xw)
			{
				foreach($xw as $key => $val)
				{
					$xw[$key]['title'] = strip_tags($val['title']);
				}
			}
			/************hanshaobo 4.30************/
			//print_r($xw);
			$this->template->assign('news',$xw);
			if ($_GET['isAjax'] == 1){
				$js = array();
				if ($xw){				
					foreach ($xw as $key => $val){
						$js[$key]['base_url'] = url('mobile/wx/dynamic',"hid=".$hid."&contentid=".$val['contentid']); 
						$js[$key]['Images'] = $val['pic'];
						$js[$key]['Content'] = $val['description'];
						$js[$key]['AddTime'] = $val['published'];
						$js[$key]['Title'] = $val['title'];
					}
				}
				$result['error_code'] = 0;
				$result['data']['rows']	= $js;
				exit(json_encode($result));
			}
			$url = url('mobile/wx/dynamic',"hid=".$hid);
			$this->template->assign('url',$url);
			unset($xw);			
			$this->template->display('news_list','wx');	
		}
		unset($news);			
	}
	function PageHx(){
		$hid = intval($_GET['hid']);
		$info = $this->common($hid);
		$this->template->assign($info);
		$pid = intval($_GET['pid']);
		$album = loader :: model("xf_album_picture", 'mobile');
		if ($pid){	//详情			
			$room_type = $album->get_roominfo_by_pid($pid);
			$this->template->assign('room_type',$room_type['room']);
			$r_pic= array();
			$r_pic = $album->get_roompic_by_room($hid,$room_type['room']);
			if ($r_pic){
				foreach ($r_pic as &$val){
					$val['pic'] = imgurl($val['filepath'], 400,300);				
				}
			}
			$pic_count = count($r_pic);
			$this->template->assign('pic_count',$pic_count);
			$this->template->assign('room',$r_pic);
			$url = url('mobile/wx/hx',"hid=".$hid."&pid=".$pid);
			$this->template->assign('url',$url);
			unset($room_type,$r_pic);
			$this->template->display('huxing','wx');
		}else {			
			$rootype_num = array();
			$rootype_num = $album->have_rootype_num($hid);
			if ($rootype_num){
				foreach ($rootype_num as &$val){
					$r1 = $album->get_firstroompic_by_room($hid,$val['room']);				
					$r2 =  $album->get_unit_first_roominfo($hid,$val['room'],400,300);//所有该户型下图片				
					$p = explode(',',$r2['p_num']);
					$p2 = substr($r2['p_num'], 0,-1);				
					$r1[0]['pic'] = imgurl($r1[0]['filepath'], 600, 450);	
					$val = $r1[0];
					$val['pic_all'] = $p2;
				}
			}
			$this->template->assign('hx',$rootype_num);
			$url = url('mobile/wx/hx',"hid=".$hid);
			$this->template->assign('url',$url);
			unset($rootype_num,$r1,$r2);
			$this->template->display('huxing_list','wx');
		}
		unset($album);
	}
	function PageJsq(){
		$hid = intval($_GET['hid']);
		$info = $this->common($hid);
		$url = url('mobile/wx/jsq',"hid=".$hid);
		$this->template->assign('url',$url);
		$this->template->display('jisuanqi','wx');
	}
	function PageTuan(){
		$hid = intval($_GET['hid']);
		$info = $this->common($hid);
		$this->template->assign($info);
		
		if ($_POST){
			$xf_apply = loader :: model("xf_apply", "mobile");
			$result = array();
			$result['error_code'] = '0';
			$name = $_POST['Extend']['Name'];
			$mobile = $_POST['Extend']['Mobile'];
			$time = $_POST['Extend']['ViewTime'];
			if (empty($hid)){
				$result['error_code'] = "1";
				$result['error_msg'] = "楼盘不存在，或者该楼盘尚未开通";
				
			}elseif (empty($name)){
				$result['error_code'] = "1";
				$result['error_msg'] = "姓名不得为空";
				
			}elseif (empty($mobile)){
				$result['error_code'] = "1";
				$result['error_msg'] = "手机号码不得为空";
				
			}elseif (empty($time)){
				$result['error_code'] = "1";
				$result['error_msg'] = "日期不得为空";
			}			
			$d = array(
				'id'=>$hid,
				'truename'=>$name,
				'mobile'=>$mobile,
				'source'=>$this->source,
				'postip' => IP,
				'posttime'=>mktime($time)
			);
			
			if ($xf_apply->add($d)){
				$result['error_code'] = '0';
				$result['error_msg'] = '预约成功';
				
			}else {
				$result['error_code'] = '1';
				$result['error_msg'] = '预约失败';
				
			}			
			exit(json_encode($result));	
			unset($xf_apply);	
		}		
		$url = url('mobile/wx/tuan',"hid=".$hid);
		$this->template->assign('url',$url);
		$this->template->display('kanfang','wx');
	}
	function PagePic(){
		$hid = intval($_GET['hid']);
		$info = $this->common($hid);
		$this->template->assign($info);
		$type = intval($_GET['tid']);
		$pid = intval($_GET['pid']);
		$album = loader :: model("xf_album_picture", 'mobile');
		if ($type && $pid){
			$pic = array();				
			if ($type){
				$pic = $album->get_by_tid($hid,$type,'',400,300);
				$pic_count = count($pic);
			}
			foreach ($pic as &$val){
				$val['pic_url'] = imgurl($val['filepath'], 400,300);				
			}
			$this->template->assign('pic_count',$pic_count);
			$this->template->assign('pic',$pic);			
			$single = $album->get_roominfo_by_pid($pid); //当前图片信息
			$single['pic_url'] = imgurl($single['filepath'], 400,300);
			$single['title'] = trim($single['title']);
			$this->template->assign('single',$single);				
			unset($album,$pic,$single);		
			$url = url('mobile/wx/pic',"hid=".$hid."&tid=".$type."&pid=".$pid);
			$this->template->assign('url',$url);		
			$this->template->display('pics','wx');
		}else {			
			$pic = $album->get_album_first_pid($hid);		
			if ($pic){
				foreach ($pic as &$val){
					$data =  $album->get_roominfo_by_pid($val['pid']);
					$val['tid'] = $data['tid'];
					$val['pic'] = imgurl($data['filepath'], 400,300);				
				}			
			}		
			$this->template->assign('pic',$pic);
			unset($album,$pic);
			$url = url('mobile/wx/pic',"hid=".$hid);
			$this->template->assign('url',$url);
			$this->template->display('pics_list','wx');
		}
		unset($album);
	}
}