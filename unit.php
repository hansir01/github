<?php

class newhouse_controller_unit extends newhouse_controller {
	protected $xf_album_picture,$table_picture,$room_arr,$hall_arr,$toilet_arr,$area_arr,$room_keys,$hall_keys,$toilet_keys,$area_keys;

	function __construct($app) {
		parent :: __construct($app);
		
		$this->xf_info = loader :: model("xf_info");
		$this->xf_album_picture = loader :: model("xf_album_picture");
		$this->table_picture = 'a';

		$this->room_arr = array(
			1=>array('key'=>1,'name'=>'一居室','where'=>$this->table_picture.'.room = 1'),
			2=>array('key'=>2,'name'=>'二居室','where'=>$this->table_picture.'.room = 2'),
			3=>array('key'=>3,'name'=>'三居室','where'=>$this->table_picture.'.room = 3'),
			4=>array('key'=>4,'name'=>'四居室','where'=>$this->table_picture.'.room = 4'),
			5=>array('key'=>5,'name'=>'五居室及以上','where'=>$this->table_picture.'.room >= 5')
		);
		$this->room_keys = $this->get_keys($this->room_arr);

		$this->hall_arr = array(
			1=>array('key'=>1,'name'=>'一厅','where'=>$this->table_picture.'.hall = 1'),
			2=>array('key'=>2,'name'=>'二厅','where'=>$this->table_picture.'.hall = 2'),
			3=>array('key'=>3,'name'=>'三厅','where'=>$this->table_picture.'.hall = 3'),
			4=>array('key'=>4,'name'=>'四厅及以上','where'=>$this->table_picture.'.hall >= 4')
		);
		$this->hall_keys = $this->get_keys($this->hall_arr);

		$this->toilet_arr = array(
			1=>array('key'=>1,'name'=>'一卫','where'=>$this->table_picture.'.toilet = 1'),
			2=>array('key'=>2,'name'=>'二卫','where'=>$this->table_picture.'.toilet = 2'),
			3=>array('key'=>3,'name'=>'三卫','where'=>$this->table_picture.'.toilet = 3'),
			4=>array('key'=>4,'name'=>'四卫及以上','where'=>$this->table_picture.'.toilet >= 4')
		);
		$this->toilet_keys = $this->get_keys($this->toilet_arr);

		$this->area_arr = array(
			1=>array('key'=>1,'name'=>'70平米以下','where'=>$this->table_picture.'.area > 0 and '.$this->table_picture.'.area <= 70'),
			2=>array('key'=>2,'name'=>'70-90平米','where'=>$this->table_picture.'.area > 70 and '.$this->table_picture.'.area <= 90'),
			3=>array('key'=>3,'name'=>'90-120平米','where'=>$this->table_picture.'.area > 90 and '.$this->table_picture.'.area <= 120'),
			4=>array('key'=>4,'name'=>'120-150平米','where'=>$this->table_picture.'.area > 120 and '.$this->table_picture.'.area <= 150'),
			5=>array('key'=>5,'name'=>'150-200平米','where'=>$this->table_picture.'.area > 150 and '.$this->table_picture.'.area <= 200'),
			6=>array('key'=>6,'name'=>'200平米以上','where'=>$this->table_picture.'.area > 200 ')
		);
		$this->area_keys = $this->get_keys($this->area_arr);
	}

	function get_keys($arr){
		$r = array();
		if($arr){
			foreach($arr as &$val){
				$r[] = $val['key'];
			}
		}
		return $r;
	}
	
	function get_url_data($preg,$url_get){
		$r = '';
		if(preg_match($preg, $url_get, $url_matche)) {
		   if($url_matche[1]){
				$r = $url_matche[1];
		   }
		   unset($url_matche);
		}
		return $r;
	}
	
	function pagemorelist(){
		/*判断条件*/
		$room = abs(intval($_GET['room']));
		$room = in_array($room,$this->room_keys) ? $room : 0;
		$hall = abs(intval($_GET['hall']));
		$hall = in_array($hall,$this->hall_keys) ? $hall : 0;
		$toilet = abs(intval($_GET['toilet']));
		$toilet = in_array($toilet,$this->toilet_keys) ? $toilet : 0;
		$area = abs(intval($_GET['area']));
		$area = in_array($area,$this->area_keys) ? $area : 0;
		$key = trim($_GET['key']);
		$hid = intval($this->xf_info->get_hid_by_kw($key));
		$where = array();
		if($hall || $toilet || $area || $key || $room){
			$all_list = false;
			if(!empty($room)){
				$where[] = $this->room_arr[$room]['where'];
			}
			if(!empty($hall)){
				$where[] = $this->hall_arr[$hall]['where'];
			}
			if(!empty($toilet)){
				$where[] = $this->toilet_arr[$toilet]['where'];
			}
			if(!empty($area)){
				$where[] = $this->area_arr[$area]['where'];
			}
			if(!empty($key)){
				$where_hid = '';
				if($hid){
					$where_hid = ' OR '.$this->table_picture.'.hid = '.$hid;
				}
				$where[] = ' ( '.$this->table_picture.'.unitnum LIKE \'%'.$key.'%\' OR  '.$this->table_picture.'.title LIKE \'%'.$key.'%\' OR '.$this->table_picture.'.info LIKE \'%'.$key.'%\' '.$where_hid.' ) ';
			}
			$where = implode(' and ',$where);
		}else{
			$where = '1';
		}		
		/*获取数据*/
		$cur_page = intval($_GET['page']);
		$per_num = 18;	
		$unit_list = $this->xf_album_picture->get_unit_list($where,$cur_page,$per_num,200,150);
		$data = array();
		foreach($unit_list['unit'] as $key=>$val){
			$data[$key]['imgSrc'] = $val['pic_url'];
			$data[$key]['href'] = url('newhouse/detail/picture','spell='.$val["spell"].'&album='.$val["tid"]).'#id='.$val["pid"];
			$data[$key]['imgtxt'] = $val['name'];
			//户型
			$hx = '';
			if($val['room']){
				$hx .= $val['room'].'室';
				if($val['hall']){
					$hx .= $val['hall'].'厅';
				}
				if($val['toilet']){
					$hx .= $val['toilet'].'卫';
				}
			}else{
				$hx = '待定';
			}
			$data[$key]['hx'] = $hx;
			$data[$key]['area'] = $val['area'];
			$data[$key]['content'] = $val['info'];
		}
		$result = array();
		$result['more'] = !empty($data) ? 1: 0;
		$result['data'] = $data;
		echo json_encode($result);
		exit;
	}

	function pagelist() {
		$room = $hall = $toilet = $area = 0;
		$key = $url_get = '';
		$url_go = array();
		if(preg_match('/\/huxingtu\/([^<]*).html/i', empty($_SERVER['PATH_INFO']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PATH_INFO'], $url_matche)) {
		   $url_get = $url_matche[1];
		   unset($url_matche);
		}
		

		/*除了包含本身的url*/
		$url_data = $this->get_url_data('/list(.*)/',$url_get);
		//$url_data = $this->get_url_data('/-page-[0-9](.*)/',$url_data);
		$other_url= array(
			'room' 		=> preg_replace('/\-room\-([0-9]+)/', '', $url_data),
			'hall' 		=> preg_replace('/\-hall\-([0-9]+)/', '', $url_data),
			'toilet' 	=> preg_replace('/\-toilet\-([0-9]+)/', '', $url_data),
			'area' 		=> preg_replace('/\-area\-([0-9]+)/', '', $url_data),
			//分页参数
			'page' 		=> preg_replace('/\-page\-([0-9]+)/', '', $url_data),
			'key' 		=> preg_replace('/\-key\-([^\-\.\+\!\@\#\$\*]+)/', '', $url_data)
		);
		
		$room = $this->get_url_data('/room-([0-9]+)/',$url_get);
		$hall = $this->get_url_data('/hall-([0-9]+)/',$url_get);
		$toilet = $this->get_url_data('/toilet-([0-9]+)/',$url_get);
		$area = $this->get_url_data('/area-([0-9]+)/',$url_get);
		$key = $this->get_url_data('/key-([^\-\.\+\!\@\#\$\*]+)/',$url_get);
		$cur_page = $this->get_url_data('/page-([0-9]+)/',$url_get);
		$nopage_url = array();
		/*******************hanshaobo 5.8*************************/
		//区分分页和换条件
		if($cur_page)
		{
			foreach($other_url as $k => $val)
			{
				$nopage_url[$k] = $this->get_url_data('/(.*)-page-([0-9]+)(.*)/',$val);
			}
		}else
		{
			$nopage_url = $other_url;
		}
		/*******************hanshaobo 5.8*************************/
		$this->template->assign('other_url', $nopage_url);
		$hid = intval($this->xf_info->get_hid_by_kw($key));
		
		$room = abs(intval($room));
		$room = in_array($room,$this->room_keys) ? $room : 0;
		$hall = abs(intval($hall));
		$hall = in_array($hall,$this->hall_keys) ? $hall : 0;
		$toilet = abs(intval($toilet));
		$toilet = in_array($toilet,$this->toilet_keys) ? $toilet : 0;
		$area = abs(intval($area));
		$area = in_array($area,$this->area_keys) ? $area : 0;
		$key = urldecode(addslashes(trim($key)));
		$cur_page = $cur_page > 0 ? $cur_page : 1;

		$where = array();
		if($hall || $toilet || $area || $key || $room){
			if(!empty($room)){
				$where[] = $this->room_arr[$room]['where'];
			}
			if(!empty($hall)){
				$where[] = $this->hall_arr[$hall]['where'];
			}
			if(!empty($toilet)){
				$where[] = $this->toilet_arr[$toilet]['where'];
			}
			if(!empty($area)){
				$where[] = $this->area_arr[$area]['where'];
			}
			if(!empty($key)){
				$where_hid = '';
				if($hid){
					$where_hid = ' OR '.$this->table_picture.'.hid = '.$hid;
				}
				$where[] = ' ( '.$this->table_picture.'.unitnum LIKE \'%'.$key.'%\' OR  '.$this->table_picture.'.title LIKE \'%'.$key.'%\' OR '.$this->table_picture.'.info LIKE \'%'.$key.'%\' '.$where_hid.' ) ';
			}
			$where = implode(' and ',$where);
		}else{
			$where = '1';
		}
		$per_num = 18;
		$unit_list = $this->xf_album_picture->get_unit_list($where,$cur_page,$per_num,200,150);
		/*******************hanshaobo 5.6*************************/
		$total = $unit_list['unit_total'];
		$total_page = ceil ($total / $per_num);
		$this->template->assign('cur_page', $cur_page);
		$this->template->assign('total_page', $total_page);
		$page = '';
		//分页
		if($cur_page >1)
		{
			$page_url = $this->pageurl($cur_page-1,$other_url['page']);
			$page .= "<a href='".$page_url."'><i>&lt;</i>上一页</a>";
		}
		if($cur_page < 3)
		{
			if($total_page < 5){
				for($i=1;$i<=$total_page;$i++)
				{
					$page_url = $this->pageurl($i,$other_url['page']);
					if($cur_page == $i)
					{
						$page .= '<span>'.$i.'</span>';
					}else
					{	
						$page .= "<a href='".$page_url."'>".$i."</a>";
					}
				}
			}else
			{
				for($i=1;$i<=5;$i++)
				{
					$page_url = $this->pageurl($i,$other_url['page']);
					if($cur_page == $i)
					{
						$page .= '<span>'.$i.'</span>';
					}else
					{	
						$page .= "<a href='".$page_url."'>".$i."</a>";
					}
				}

			}
		}
		if($cur_page >= 3 && $cur_page < $total_page-2)
		{
			for($i=$cur_page-2;$i<=$cur_page+2;$i++)
			{
				$page_url = $this->pageurl($i,$other_url['page']);
				if($cur_page == $i)
				{
					$page .= '<span>'.$i.'</span>';
				}else
				{	
					$page .= "<a href='".$page_url."'>".$i."</a>";
				}
			}
		}
		if($cur_page >= $total_page-2 && $cur_page <= $total_page && $total_page > 3)
		{
			for($i=$total_page-4;$i<=$total_page;$i++)
			{
				$page_url = $this->pageurl($i,$other_url['page']);
				if($cur_page == $i)
				{
					$page .= '<span>'.$i.'</span>';
				}else
				{	
					$page .= "<a href='".$page_url."'>".$i."</a>";
				}
			}
		}
					
		if($cur_page < $total_page)
		{
			$page_url = $this->pageurl($cur_page+1,$other_url['page']);
			$page .= "<a href='".$page_url."'>下一页<i>&gt;</i></a>";
		}
		$this->template->assign('page', $page);
		/*******************hanshaobo 5.6*************************/
		$this->template->assign("unit_list", isset($unit_list['unit']) ? $unit_list['unit'] : '');
		
		$this->template->assign('room', $room);
		$this->template->assign('hall', $hall);
		$this->template->assign('toilet', $toilet);
		$this->template->assign('area', $area);
		$this->template->assign('keywords', $key);

		$this->template->assign('room_arr', $this->room_arr);
		$this->template->assign('hall_arr', $this->hall_arr);
		$this->template->assign('toilet_arr', $this->toilet_arr);
		$this->template->assign('area_arr', $this->area_arr);
		$this->template->assign('menumodel', 'house_unit');
		$head['title'] = $this->_config['name'].'楼盘户型图'.'-'.$this->_config['name'].'新房-'.$this->_config['name'].$this->_config['site_name'];
		$head['keywords'] = $this->_config['name'].'新楼盘,'.$this->_config['name'].'房产,'.$this->_config['name'].'楼盘户型图';
		$head['description'] = $this->_config['site_name'].'—提供'.$this->_config['name'].'楼盘, '.$this->_config['name'].'新楼盘信息,提供权威的'.$this->_config['name'].'房价走势分析，方便快捷的地图找房,最全面的'.$this->_config['name'].'新楼盘, '.$this->_config['name'].'楼盘信息以及'.$this->_config['name'].'房价查询,为您找到最适合您的'.$this->_config['name'].'楼盘信息,了解最新楼盘优惠,就上'.$this->_config['name'].'房地产门户-'.$this->_config['site_name'].'。';
		$this->template->assign('head',$head);
		$this->template->display("unit_list","newhouse");
	}
	
	function pagesearch(){
		$key = rawurlencode(trim($_POST['key']));
		$url = trim($_POST['url']);
		if(isset($_POST['key']) && !empty($key)){
			$urls = url("newhouse/unit/list","params=".$url."-key-".urlencode($key));
			header('location:'.$urls);
		}else{
			$urls = url("newhouse/unit/list");
			header('location:'.$urls);			
		}
	}
	//获取分页的路径
	function pageurl($cur_page,$page)
	{
		$page_url = url('newhouse/huxingtu/list'.$page."-page-".$cur_page.".html");
		$page_url = substr($page_url,0,strlen($page_url)-1);
		return $page_url;
	}
}
