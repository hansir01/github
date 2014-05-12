<?php
class newhouse_controller_list extends newhouse_controller {
	protected $member;

	function __construct($app) {
		parent :: __construct($app);
		loader::_load('user','lib','passport');
		$this->member = passport_user_lib::getInstance()->user;
	}
	
	function pagepart(){
		echo $this->forward('newhouse','list','index',array('type'=>'4'));
	}
	
	function pagesubway()
	{
		if($this->_config['setting']['issubway'] == 1)
		{
			echo $this->forward('newhouse','list','index',array('type'=>'5'));
		}else
		{
			$this->showmsg('地铁尚未开通','goback','err');
		}
	}
	function pageindex() {
		//检测用户是否登录
		loader::_load('user','lib','passport');
		$user = passport_user_lib::getInstance();
		$this->_user = $user->user['uid'];
		if($this->_user==0){
			$this->template->assign('loginstatus',0);			
		}else{
			$this->template->assign('loginstatus',1);
		}
		//当前页面地址
		$referer = base64_encode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		$this->template->assign('referer',$referer);

		$xf_count = loader :: model("xf_count");
		
		//判断是否显示地铁
		$this->template->assign('issubway', $this->_config['setting']['issubway']);
		

		$typeid = isset($this->app->args['type']) ? intval($this->app->args['type']) : 0;
		//$typeid = in_array($typeid,array(4)) ? $typeid : 0;
		$typeid = in_array($typeid,array(5)) ? $typeid : 0;
		$type_arr = array(5=>'');
		$type_tag = isset($type_arr[$typeid]) ? $type_arr[$typeid] : null;
		$this->template->assign('typeid', $typeid);

		/*=浏览记录=*/
		loader::_load('history','lib','index');
		$history = new index_history_lib('newhouse');
		$newhouse = $history->history_house('10');
		$this->template->assign('history', $newhouse);

		//$url['this_url'] = $typeid==4 ? 'part' : 'index';
		
		$url['this_url'] = $typeid==5 ? 'subway' : 'index';
		$url['this_ext'] = '.html';
		$this->template->assign("url", $url);
		


		/*获得项目价格区间*/
		$xf_averageprice = $this->cache->get('common_field','xf_average_price');
		$this->template->assign("xf_averageprice", $xf_averageprice[CITY_ID]);

		/*获得房屋类别*/
		$comm_project_type = $this->cache->get('common_field','xf_project_type');
		$this->template->assign("comm_project_type", $comm_project_type[CITY_ID]);
		
		/*获得建筑特色*/
		$xf_project_feature = $this->cache->get('common_field','xf_project_feature');
		$this->template->assign("xf_project_feature", $xf_project_feature[CITY_ID]);

		/*获得环线*/
		$circleline = $this->cache->get('common_field','xf_circle_line');
		$this->template->assign("circleline", $circleline[CITY_ID]);
	
		/*26大写字母 */
		$capital_letters = range('A','Z');
		$this->template->assign("capital_letters", $capital_letters);
		$area_letters = $this->place->getCirclesByLetter();
		$this->template->assign("area_letters", $area_letters);

		/*获得销售状态*/
		$xf_salesstatus = $this->cache->get('common_field','xf_sale_status');
		$this->template->assign("xf_salesstatus", $xf_salesstatus[CITY_ID]);

		/*获得装修情况*/
		$xf_decorate = $this->cache->get('common_field','xf_decorate');
		$this->template->assign("esf_decorate", $xf_decorate[CITY_ID]);

		/*获得开盘时间区间*/
		$xf_opentime = $this->cache->get('common_field','xf_opentime');
		$this->template->assign("xf_opentime", $xf_opentime[CITY_ID]);
		
		/*获得交房时间区间*/
		$xf_livetime = $this->cache->get('common_field','xf_livetime');
		$this->template->assign("xf_livetime", $xf_livetime[CITY_ID]);
		
		/*获得排序方式*/
		$xf_orderby = $this->cache->get('common_field','xf_orderby');
		$this->template->assign("xf_orderby", $xf_orderby[CITY_ID]);		

		loader::_load('search','lib','newhouse');
		$search = new newhouse_search_lib($type_tag);

		$this->template->assign('url_str', $search->url_str); //url有效的where串
		$where_data = $search->creat_where_data();
		if($typeid == 0){ //区域
			/*获得所有区域*/
			$area_all = $this->xf_info->get_area_all();
			$this->template->assign("area_all", $area_all);
			$cur_place['parentid'] = '';
			$get_areaid = '';
			$circle_all = '';
			if($where_data['placename']){
				$place_query = $this->place->getByField(array('letters'=>$where_data['placename']));
				$cur_place = $place_query[0];
				if($cur_place['parentid'] != 0){
					$cur_parent_area = $this->place->getPlace($cur_place['parentid']);
					$this->template->assign('cur_parent_area', $cur_parent_area);
				}
				$cur_place_data = $this->place->getPlace($cur_place['pid']);
				$this->template->assign('cur_place_data', $cur_place_data);
				$circle_all = $this->place->getCircle($cur_place['parentid']==0 ? $cur_place['pid'] : $cur_place['parentid']);
			}
			$this->template->assign("cricle_all", $circle_all); //判断选择的是区域还是商圈 再列表商圈
			$this->template->assign('cur_place', $cur_place);
		}elseif($typeid == 4){
			/*获得片区*/
			$xf_part = $this->cache->get('common_field','xf_part');
			$this->template->assign("xf_part", $xf_part[CITY_ID]);
		}elseif($typeid == 5)
		{
			
			/*************hanshaobo 2014.4.22*********************/
			//获取地铁
			$subwayid = '';
			$subwayline = $this->xf_info->get_subway_all();
			
			$this->template->assign("subwayline", $subwayline);
			//print_r($subwayline);
			//如果有地铁路线和地铁站点数据
			if($where_data['subway'])
			{
				//print_r($where_data);
				//判断地铁站点是否显示
				$circle_all = 1;
				//获取地铁站点的数据
				//print_r($subwayline);
				foreach($subwayline as $key => $val)
				{
					if($val['sid'] == $where_data['subway'] && $where_data['subwaysite'] == '')
					{
						$subwaysite = $val['childrenarr'];
						$subwayid = $val['sid'];
					}elseif($val['sid'] == $where_data['subway'] && $where_data['subwaysite'] != '')
					{
						foreach($val['childrenarr'] as $k => $v)
						{	
							$subwaysite = $val['childrenarr'];
							$subwayid = $val['sid'];
							
						}
                        
					}

				}
				$this->template->assign("sub", $subwayid);
				$this->template->assign("cricle_all", $circle_all); //判断选择的是区域还是商圈 再列表商圈
				$this->template->assign('subwaysite', $subwaysite);
			}

			/*****************hanshaobo 2014-4-22 ************/
			
		}
		$this->template->assign('where_data', $where_data); //传至页面控制样式
		$this->template->assign('url_data', $search->url_data); //传至页面,用于路径参数前字母标识
		
		$choose = $search->choose();
		$this->template->assign("choose",$choose);
		
		//友情链接
		$common_friendlink = loader :: model('common_friendlink');
		$friendlink = $common_friendlink->get_frlink(18);
		$this->template->assign('friendlink',$friendlink);

		$head['title'] = $choose && is_array($choose) ? implode("_", $choose)."楼盘" : "{$this->_config['name']}楼盘";
		$this->template->assign("head", array ("title" => $head['title']));
		
		$except_url = $search->except_where();
		
		$except_url['subway'] = preg_replace('/\-f([0-9]+)/', '', $except_url['subway']);
		//$except_url['subwaysite'] = preg_replace('/\-([0-9]+)/', '', $except_url['subwaysite']);
		$this->template->assign('except_url',$except_url);

		$where_order = $search->creat_where_order();
		
		/*=分页=*/
		$cur_page = $where_data['page'] ? abs(intval($where_data['page'])) : 1 ;
		$per_num = 12;
		$where = $where_order['sql_where'].' cityid = '.CITY_ID.' and status=1';
		$order = $where_order['sql_order'].'istop desc,topsort asc,sort asc,salesstatus desc,monthly_hits desc,updatetime desc';//置顶 排序 时间
		$goto_house = null;
		$this->xf_info->get_subway_all();
		$house_list = $this->xf_info->get_house_list($where,$order,$cur_page,$per_num,'',$this->_config['setting']['house_bbs_url']);
		if(isset($house_list['total']) && $house_list['total'] == 1 && ($where_data['key'] == $house_list['house'][0]['name'] || $where_data['key'] == $house_list['house'][0]['aliasname'])){
			$goto_house = $this->_config['house_url'].$house_list['house'][0]['spell'].$url['this_ext'];
		}
		$this->template->assign("goto_house", $goto_house);
		$this->template->assign("house_list", $house_list['house']);
		$this->template->assign("house_total", isset($house_list['total']) ? $house_list['total'] : 0);
		//页码
		$total = isset($house_list['total']) ? $house_list['total'] : 0;//总数
		$page_url = url('newhouse/list/'.$url['this_url'],'params='.$except_url['page'].'-'.$search->url_data['page'].'_PAGE_');
		$html = $this->page_html($total, $per_num, $cur_page, $page_url);
		$this->template->assign('page', $html);
		//p(url ('newhouse/ajax/house+'));
		//如果搜索结果为一个跳转到详情页
		if($total == 1 && empty($choose)){
			header('location:'.url('newhouse/detail/index', 'spell='.$house_list['house'][0]['spell']));
			exit;
		}

		//上一页 当前 下一页
		$this->template->assign('cur_page', $cur_page);
		$total_page = ceil ($total / $per_num);
		$this->template->assign('total_page', $total_page);

		$this->template->assign('url_str_nopage', $search->get_url_str_nopage());
		
		/*=热点楼盘=*/
		$this->template->assign("hot", $this->xf_info->get_hot(10));
		
		/*=最新楼盘=*/
		$this->template->assign("lastest", $this->xf_info->get_lastest(10));

		/*=近期开盘=*/
		$this->template->assign("recentopen", $this->xf_info->get_recentopen(10));

		/*=团购排行=*/
		$this->template->assign("groupbuy_order", $this->xf_info->get_groupbuy_order(10));

		/*=大家都爱看=*/
		$this->template->assign("lovelook", $xf_count->lovelook(6));

		$this->template->assign("menumodel", 'house_list');
		
		$choose_str = '';
		if($choose){
			foreach($choose as $val){
				$choose_str .= $val.'_';
			}
		}
		$head['title'] = '【'.$head['title'].'】_'.$this->_config['name'].'新楼盘_'.$this->_config['name'].'房价 - '.$this->_config['name'].$this->_config['site_name'];
		$head['keywords'] = $this->_config['name'].$cur_parent_area['name'].$choose['placename'].'楼盘,'.$this->_config['name'].$cur_parent_area['name'].$choose['placename'].'新房,'.$this->_config['name'].$cur_parent_area['name'].$choose['placename'].'房价,'.$this->_config['name'].$choose_str.'新楼盘,'.$this->_config['name'].'楼盘优惠,'.$this->_config['name'].'楼盘价格';
		$head['description'] = $this->_config['site_name'].'—提供'.$this->_config['name'].$cur_parent_area['name'].$choose['placename'].'楼盘, '.$this->_config['name'].$cur_parent_area['name'].$choose['placename'].'新楼盘信息,提供权威的'.$this->_config['name'].$cur_parent_area['name'].$choose['placename'].'房价走势分析，方便快捷的地图找房,最全面的'.$this->_config['name'].'新楼盘, '.$this->_config['name'].'楼盘信息以及'.$this->_config['name'].'房价查询,为您找到最适合您的'.$this->_config['name'].$cur_parent_area['name'].$choose['placename'].'楼盘信息,了解最新楼盘优惠,就上'.$this->_config['name'].'房地产门户-'.$this->_config['site_name'].'。';
		$this->template->assign('head',$head);
		$this->template->display("list","newhouse");
	}

	function pagesearch(){
		loader::_load('search','lib','newhouse');
		$search = new newhouse_search_lib();
		$place = trim($_POST['place']);
		$projecttype = intval($_POST['projecttype']);
		$averageprice = intval($_POST['averageprice']);
		$keyword = rawurlencode(trim($_POST['keyword']));
		$url = '';
		if($place){
			$url .= '-'.$place;
		}
		if($averageprice){
			$url .= '-'.$search->url_data['averageprice'].$averageprice;
		}
		if($projecttype){
			$url .= '-'.$search->url_data['projecttype'].$projecttype;
		}
		if($keyword){
			$url .= '-'.$search->url_data['key'].urlencode($keyword);
		}
		header('location:'.url('newhouse/list/index', "params=$url"));
	}
	
	function pagesearchmin(){
		$key = rawurlencode(trim($_POST['key']));
		$thisurl = trim($_POST['thisurl']);
		$url = trim($_POST['url']);
		if(isset($_POST['key']) && !empty($key)){
			$urls = url("newhouse/list/".$thisurl,"params=".$url.urlencode($key));
			header('location:'.$urls);
		}else{
			header('location:url("newhouse/list/index")');
		}		
	}
	
	public function pageHot(){
		/*热门楼盘*/
		$this->template->assign('hot',$this->xf_info->byPvGetHouse(10));
		
		/*周关注*/
		$this->template->assign('attention', $this->xf_info->attention(10));
		
		/*周400电话*/
		$this->template->assign('tel', $this->xf_info->tel_top(10));
		
		/*本月涨幅*/
		$this->template->assign('price_up', $this->xf_info->get_price_up(10));
		
		/*本月跌幅*/
		$this->template->assign('price_down', $this->xf_info->get_price_down(10));
		
		/*本月团购*/
		$this->template->assign("groupbuy_order", $this->xf_info->get_groupbuy_list(10));
		
		/*本月开盘*/
		$this->template->assign("montyopen", $this->xf_info->get_monthtopen(10));
		
		/*下月开盘*/
		$this->template->assign("montyopen_next", $this->xf_info->get_month_next_open(10));
		
		/*交房计划*/
		$this->template->assign("live", $this->xf_info->get_live(10));
		
		$this->template->assign('menumodel','fengyunbang');
		$head['title'] = $this->_config['name'].'楼盘汇总-'.$this->_config['name'].'最新楼盘排行榜-'.$this->_config['name'].$this->_config['site_name'];
		$head['keywords'] = $house['name'].','.$this->_config['name'].$house['name'].','.$house['name'].'楼盘详情,'.$this->_config['name'].$house['name'].'楼盘均价,'.$this->_config['name'].$house['name'].'楼盘信息,'.$this->_config['name'].'房产';
		$head['description'] = $this->_config['name'].$house['name'].',置家'.$this->_config['name'].'房产网为您提供位于'.$this->_config['name'].$house['areaname'].$house['circlename'].$house['name'].'楼盘最新动态,包括'.$house['name'].'开盘日期,'.$house['name'].'房价走势,户型图,周边交通等'.$this->_config['name'].$house['name'].'楼盘信息,为您购买'.$this->_config['name'].$house['name'].'楼盘提供最优价值的参考。';
		$this->template->assign('head',$head);
		$this->template->display("fengyunbang","newhouse");
	}
	
	/*
	 * //改pid为titlepic 将pid对应的数据写到新改的字段
	 * function pagetitlepic(){
		$data = $this->xf_info->select('hid > 0','hid,titlepic','hid asc');
		if($data){
			foreach($data as $val){
				$result = false;
				$pid = intval($val['titlepic']);
				if($pid){
					$xf_album_picture = loader::model('xf_album_picture');
					$picture = $xf_album_picture->get($pid);
					if($picture['filepath']){
						$this->xf_info->update(array('titlepic' => $picture['filepath']),'hid = '.$val['hid']);
						$result = true;
						echo $val['hid'].'为：'.$picture['filepath'].'<br>';
					}
				}
				if($result === false){
					$this->xf_info->update(array('titlepic' => ''),'hid = '.$val['hid']);
					echo $val['hid'].'置空<br>';
				}
			}
		}
	}*/
	

	/*function pagekeyword(){
		$data = $this->xf_info->select('status = 1','name,spell as url','hid asc');
		if($data){
			$cmstop_keyword = loader::model('cmstop_keyword');
			foreach($data as &$r){
				$r['url'] = $this->_config['house_url'].$r['url'].'.html';
				if($cmstop_keyword->exists('name', $r['name']) == false){
					if($cmstop_keyword->insert($r)){
						echo '<font color="green">插入'.$r['name'].$r['name'].'成功</font><br>';
					}else{
						echo '<font color="red">插入'.$r['name'].$r['name'].'失败</font><br>';
					}
				}else{
					echo ''.$r['name'].'已经存在<br>';
				}
			}
		}
	}*/
}
