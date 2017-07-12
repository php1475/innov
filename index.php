<?php

/**
 * PHP code
 * ============================================================================
 * filename: index.php
 * ----------------------------------------------------------------------------
 * page: no data
 * ============================================================================
 * modified: 20:35 30.11.2016
*/

define('IN_ECS', true);

// init
require(dirname(__FILE__) . '/includes/init.php');

// debug mode
if ((DEBUG_MODE & 2) != 2) { $smarty->caching = true; }

/*------------------------------------------------------ */
/* INPUT
/*------------------------------------------------------ */

$act		= (isset($_REQUEST['act'])) ? $_REQUEST['act'] : '';
$selector 	= (isset($_REQUEST['selector'])) ? $_REQUEST['selector'] : '';

/*------------------------------------------------------ */
/* JSON THREAD
/*------------------------------------------------------ */

if ($act == 'itemlist' || $act == 'customlist') {
	switch ($act)
	{
		case "itemlist":
			include(dirname(__FILE__) . '/includes/cls_json.php');
			// get data
			$data	= (isset($_REQUEST['data'])) ? $_REQUEST['data'] : 'recent';
			$page	= (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 0;
			// set data
			$_SESSION['_tags'] = '';
			//
			if ($_SESSION['user_id'] > 0) {
				$_content = print_usrcustom_content($data, $page);
			} else {
				$_content = print_index_content($data, $page);
			}
		break;
		case "customlist":
			include(dirname(__FILE__) . '/includes/cls_json.php');
			// get data
			$data	= 'tags';
			$tags	= (isset($_REQUEST['tags'])) ? $_REQUEST['tags'] : '';
			$page	= (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 0;
			// set data
			$_SESSION['_tags'] = '';
			$tags = mb_strtolower($tags, 'UTF-8');
			//
			$sql = "SELECT COUNT(*) FROM ". $ecs->table("user_custom"). " WHERE user_id = '".$_SESSION['user_id']."'";
			if ($db->getOne($sql) == 0)
			{
				$sql = "INSERT INTO ".$ecs->table("user_custom")." (user_id, tags) VALUES ('".$_SESSION['user_id']."', '$tags')";
				$db->query($sql);
			} else {
				$sql = "UPDATE " .$ecs->table('user_custom'). " SET tags = '" .$tags . "' WHERE user_id = '".$_SESSION['user_id']."'";
				$db->query($sql);
			}
			//
			if ($_SESSION['user_id'] > 0) {
				$_content = print_usrcustom_content($data, $page);
			} else {
				$_content = print_index_content(0);
			}
		break;
	}
	// output
	$json   = new JSON;
	$res    = array('err_msg' => '', 'content' => '', 'tags' => $_SESSION['_tags']);
	$res['content'] = $_content;
	die($json->encode($res));
}

/*------------------------------------------------------ */
/* SET VARIABLE
/*------------------------------------------------------ */

$_footer = file_get_contents('themes/2.0/library/footer_block.lbi', true);

/*------------------------------------------------------ */
/* MAIN THREAD
/*------------------------------------------------------ */

if ($_POST['ajax'] == true) { $_dwt = "index-new.data.dwt"; } else { $_dwt = "index-new.dwt"; }
	
if($selector !== '') {
	if ($_SESSION['user_id'] > 0) {
		$showcase_id = $db->getOne("SELECT reg_time FROM " .$ecs->table('users'). " WHERE user_id='" .$_SESSION['user_id']. "'");
		// feeds list: begin
		$sql = "SELECT u.user_name, u.reg_time FROM " .$ecs->table('feeds'). " f INNER JOIN " .$ecs->table('users'). " u ON u.user_id = f.feed_id WHERE f.user_id='" .$_SESSION['user_id']. "'";
		$res = $db->query($sql);
		$feeds_list = array();
		while ($row = $db->fetchRow($res))
		{
			$feeds_list[] = $row;
		}
		$smarty->assign('feeds_list',      $feeds_list);
		// end
		$smarty->assign('selector',        $selector);
		$index_content = print_usrcustom_content($selector, 0);
	} else {
		$smarty->assign('selector',        $selector);
		$index_content = print_index_content($selector, 0);
	}
	$smarty->assign('content',        $index_content);
	
	/* SEO */
	if ($zipcode == 'kg') { $_string_geo = 'Кыргызстан'; }
	if ($zipcode == 'kz') { $_string_geo = 'Казахстан'; }
	if ($zipcode == 'ru') { $_string_geo = 'Россию'; }
	//
	if ($selector == 'recent')  { $_string_str = 'Новинки товаров'; }
	if ($selector == 'popular') { $_string_str = 'Популярные товары'; }
	//
	$_SEO_TITLE 		= $_string_str.' из интернет-магазинов Китая и США без комиссии с доставкой в '.$_string_geo;
	$_SEO_KEYWORDS 		= $_string_str.', Taobao, Tmall, AliExpress, Amazon, eBay, сервис покупок за рубежом, без комиссии, доставка из Китая в '.$_string_geo.', доставка из США в '.$_string_geo.'';
	$_SEO_DESCRIPTION 	= $_string_str.' с Taobao, Tmall, AliExpress, Amazon, eBay без комиссии на сайте '.$_SERVER['HTTP_HOST'].' с доставкой из Китая и США в '.$_string_geo;
} else {
	//die ("Селектор = ".$selector);
	//МОРДА САЙТА
	$adpage = 1;
	$smarty->assign('adpage',        $adpage);
	$_KURS = get_current_rate();

	// РАЗДЕЛ НА ГЛАВНОЙ ЛУЧШИЕ ПЛОЩАДКИ МИРА (МАГАЗИНЫ)
	$shops_list = array();
	$sql = "SELECT link_name, link_url, link_logo FROM " .$GLOBALS['ecs']->table('friend_link'). " ORDER BY show_order";
	$res = $GLOBALS['db']->selectLimit($sql, 100, 0);
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$shops_list[] = Array('name' => $row['link_name'], 'url' => $row['link_url'], 'logo' => $row['link_logo']);
	}
	$smarty->assign('shops_list', $shops_list);
	// РАЗДЕЛ НА ГЛАВНОЙ ЛУЧШИЕ ПЛОЩАДКИ МИРА (МАГАЗИНЫ)

	// ЛУЧШИЕ МИРОВЫЕ БРЕНДЫ
	$brand_list = array();
	$sql = "SELECT brand_name, brand_logo, nick FROM " .$ecs->table('brand'). " WHERE is_show = 1";
	$res = $db->query($sql);
	if ($db->num_rows($res) > 0)
	{
		while ($row = $db->fetchRow($res))
		{
			$brand_list[] = $row;
		}
	}
	$smarty->assign('brand_list', $brand_list);
	// ЛУЧШИЕ МИРОВЫЕ БРЕНДЫ
	
	//КОЛЛЕКЦИИ НА ГЛАВНОЙ СТРАНИЦЕ
	$showcase_list = array();
	$sql = "SELECT DISTINCT c.user_id, u.user_name, u.reg_time, s.id, s.title, s.info, s.add_time, s.points, s.views, s.goods, s.feeds FROM " .$ecs->table('collect_goods'). " c INNER JOIN " .$ecs->table('users'). " u ON u.user_id = c.user_id INNER JOIN " .$ecs->table('showcase'). " s ON s.user_id = c.user_id ORDER BY s.points DESC";
	$res = $db->query($sql);
	if ($db->num_rows($res) > 0)
	{
		while ($row = $db->fetchRow($res))
		{
			if ($row['goods'] >= 10) {
				$showcase_list[] = $row;
			}
		}
	}
	$smarty->assign('showcase_list', $showcase_list);
	//КОЛЛЕКЦИИ НА ГЛАВНОЙ СТРАНИЦЕ
	
	//ПРОФФЕСИОНАЛЬНЫЙ БЛОГ НА ГЛАВНОЙ СТРАНИЦЕ
	$blog_list = array();
	$sql = "SELECT article_id, title, author, add_time, description, file_url FROM " .$GLOBALS['ecs']->table('article'). " WHERE cat_id = 4 AND is_open = 1 AND author='blog' ORDER BY add_time DESC";
	$res = $GLOBALS['db']->selectLimit($sql, 10, 0);
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$_add_time    = date("d.m.Y H:i", $row['add_time']);
		$blog_list[] = Array('id' => $row['article_id'], 'title' => $row['title'], 'description' => $row['description'], 'author' => $row['author'], 'add_time' => $_add_time, 'file_url' => $row['file_url']);
	}
	$smarty->assign('blog_list', $blog_list);
	//ПРОФФЕСИОНАЛЬНЫЙ БЛОГ НА ГЛАВНОЙ СТРАНИЦЕ
	
	// infographics
	$app_statistics = '';
	$smarty->assign('app_statistics',    	$app_statistics);
	
	/* SEO */
	if ($zipcode == 'kg') { $_string_geo = 'Кыргызстан'; }
	if ($zipcode == 'kz') { $_string_geo = 'Казахстан'; }
	if ($zipcode == 'ru') { $_string_geo = 'Россию'; }
	$_SEO_TITLE 		= $_SERVER['HTTP_HOST'].' - доставка товаров с Taobao, AliExpress, Amazon, eBay и любых интернет-магазинов Китая и США в '.$_string_geo.' без комиссии';
	$_SEO_KEYWORDS 		= 'Доставка с Taobao, Tmall, AliExpress, Amazon, eBay в '.$_string_geo.', сервис покупок за рубежом, без комиссии, доставка из Китая в '.$_string_geo.', доставка из США в '.$_string_geo.'';
	$_SEO_DESCRIPTION 	= 'Доставка товаров из Китая и Америки в '.$_string_geo.', сервис покупок за рубежом ooba, поможет Вам купить и доставить товары в '.$_string_geo.' из любых интернет-магазинов Китая и США без комиссии.';
}

/*------------------------------------------------------ */
/* SEO
/*------------------------------------------------------ */

/*------------------------------------------------------ */
/* ASSIGN
/*------------------------------------------------------ */

$smarty->assign('page_title', 		$_SEO_TITLE);
$smarty->assign('keywords', 		$_SEO_KEYWORDS);
$smarty->assign('description', 		$_SEO_DESCRIPTION);
$smarty->assign('shop_notice', 		$_CFG['shop_notice']);
$smarty->assign('categories', 		get_categories_tree());
$smarty->assign('footer',      		$_footer);

/*------------------------------------------------------ */
/* OUTPUT
/*------------------------------------------------------ */

if ($_POST['ajax'] == true) {
	include(dirname(__FILE__) . '/includes/cls_json.php');
	$json   = new JSON;
	$_pagedata = array(
		'title' 		=> $_SEO_TITLE,
		'keywords' 		=> $_SEO_KEYWORDS,
		'description' 	=> $_SEO_DESCRIPTION,
		'content' 		=> $smarty->display_ajax($_dwt)
	);
	die($json->encode($_pagedata));
} else {
	$smarty->display($_dwt);
}

/*------------------------------------------------------ */
/* PRIVATE FUNCTIONS
/*------------------------------------------------------ */

function print_index_content($data='recent', $page) {

	$limit = 20;
	$start = $page * $limit;
	
	if ($data == 'recent')
	{
		$sql = "SELECT user_id, case_id, goods_id, referal_id, title, html, pic_url, nick, price, cid, add_time FROM " .$GLOBALS['ecs']->table('collect_goods'). " WHERE is_promote = 1 ORDER BY add_time DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$sql = "SELECT cat_id, cat_name FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_desc LIKE '%".$row['cid']."%'";
			$_cat = $GLOBALS['db']->getRow($sql);
			if (intval($_cat['cat_id']) > 0) {
				$catname = $_cat['cat_name'];
			} else {
				$catname = "Разное";
			}
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			$referal_list[$row['goods_id']] = $row['referal_id'];
			$case_list[$row['goods_id']] = $row['case_id'];
			$itemlist['taobaoke_items'][] = Array('num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => $row['title'], 'html' => $row['html'], 'catname' => $catname, 'timeago' => $row['add_time']);
		}
	}
	
	if ($data == 'popular')
	{
		//$sql = "SELECT s.goods_id, c.referal_id FROM " .$GLOBALS['ecs']->table('goods_stat'). " s LEFT JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON s.goods_id = c.goods_id ORDER BY s.views DESC";
		//$sql = "SELECT s.goods_id, c.user_id, c.referal_id FROM " .$GLOBALS['ecs']->table('goods_stat'). " s INNER JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON s.goods_id = c.goods_id ORDER BY s.views DESC";
		$sql = "SELECT s.goods_id, c.user_id, c.case_id, c.referal_id, c.title, c.html, c.pic_url, c.nick, c.price, c.cid, c.add_time FROM " .$GLOBALS['ecs']->table('goods_stat'). " s INNER JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON s.goods_id = c.goods_id WHERE c.is_promote = 1 ORDER BY s.views DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$sql = "SELECT cat_id, cat_name FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_desc LIKE '%".$row['cid']."%'";
			$_cat = $GLOBALS['db']->getRow($sql);
			if (intval($_cat['cat_id']) > 0) {
				$catname = $_cat['cat_name'];
			} else {
				$catname = "Разное";
			}
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			if ($row['referal_id']) {
				$referal_list[$row['goods_id']] = $row['referal_id'];
				$case_list[$row['goods_id']] = $row['case_id'];
			} else {
				$referal_list[$row['goods_id']] = $row['goods_id'];
			}
			$itemlist['taobaoke_items'][] = Array('num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => $row['title'], 'html' => $row['html'], 'catname' => $catname, 'timeago' => $row['add_time']);
		}
	}
	
	$num_iids = implode(",",$goods_list);
	
	if (strlen($num_iids) > 0)
	{
		if ($page == 0)
		{
			//$_content = '<div class="unit-four-column unit-auto-row text-unit tag-unit"><div class="unit-header">0 шт. новых предложений из '.$allcount.'</div></div>';
		}
		
		// tag list: begin
		$sql = "SELECT goods_id, tag_words FROM " .$GLOBALS['ecs']->table('tag'). " WHERE goods_id IN (" . $num_iids . ")";
		$res = $GLOBALS['db']->query($sql);
		$tags = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$tags[$row['goods_id']] = $row['tag_words'];
		}
		// end
		
		// infogram list: begin
		$sql = "SELECT g.goods_id, g.views FROM " .$GLOBALS['ecs']->table('goods_stat'). " g WHERE g.goods_id IN (" . $num_iids . ")";
		$res = $GLOBALS['db']->query($sql);
		$_views = array();
		$_likes = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$_views[$row['goods_id']] = $row['views'];
			$_likes[$row['goods_id']] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('likes'). " WHERE goods_id = '".$row['goods_id']."'");
		}
		// end
		
		// case list: begin
		$sql = "SELECT id, user_id, title, add_time FROM " .$GLOBALS['ecs']->table('showcase');
		$res = $GLOBALS['db']->query($sql);
		$case = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$case[$row['id']] = Array('id' => $row['add_time'], 'title' => $row['title'], 'idx' => $row['id']);
		}
		// end
		
		$_cmd = "display:none";
		$_KURS = get_current_rate();
		
		//require_once('includes/import/class.taobao.php');
		//$otapixml = new OTAPIxml();
		//$itemlist = $otapixml->GetVendorItemList($num_iids);
		
		if (count($goods_list) == 1)
		{
			$_temp = $itemlist['taobaoke_items'];
			$itemlist['taobaoke_items'] = Array();
			$itemlist['taobaoke_items'][0] = $_temp;
			$itemlist['taobaoke_items'][1] = Array ( 'nick' => '', 'num_iid' => '', 'pic_url' => '', 'price' => '', 'title' => '', 'catname' => '' );
		}
		
		foreach ($itemlist['taobaoke_items'] as $item)
		{
			if($item['num_iid'] !== '') {
				$output_list[$item['num_iid']] = $item;
			}
		}
		
		foreach ($output_list as $key => $item)
		{
			if ($item['num_iid'] !== '') {
				$img = $item['pic_url'];
				$url = '/taobao/'.$referal_list[$item['num_iid']].'/';
				$price = price_format($item['price'] * $_KURS, true);
			} else {
				$item['num_iid'] = $key;
				$img = '/images/out-of-stock';
				$url = '';
				$price = '';
			}
				$artikul = $item['num_iid'];
				$title = $item['title'];
				$html = $item['html'];
				$catname = $item['catname'];
				$_timeago = $item['timeago'];
				
				if ($_timeago > 0) {
					$timeago = local_date('Y-m-d H:i:s', $_timeago);
				} else {
					$timeago = date("Y-m-d H:i:s");
				}
				
				if ($case[$case_list[$item['num_iid']]]['id'] > 0) {
					$filename = dirname(__FILE__) . '/upload/showcase/'.$case[$case_list[$item['num_iid']]]['idx'].'.png';
					if (file_exists($filename)) {
						$nick = '<a href="/showcase/'.$case[$case_list[$item['num_iid']]]['id'].'/"><img src="/images/upload/showcase/'.$case[$case_list[$item['num_iid']]]['idx'].'.png" align="left">'.substr_str($case[$case_list[$item['num_iid']]]['title']).'</a>';
					} else {
						$nick = '<a href="/showcase/'.$case[$case_list[$item['num_iid']]]['id'].'/"><img src="/images/upload/showcase/none.png" align="left">'.substr_str($case[$case_list[$item['num_iid']]]['title']).'</a>';
					}
				} else {
					$nick = '<a href="/seller/'.$item['nick'].'/"><img src="http://semantic-ui.com/images/avatar/large/elliot.jpg" align="left">'.substr_str($item['nick']).'</a>';
				}
				
				$score = '';
				$tag = $tags[$item['num_iid']];
				$collect_id = $collect_list[$item['num_iid']];
				$interests = '';
				$views = $_views[$item['num_iid']];
				if ($_likes[$item['num_iid']] > 0) { $likes = $_likes[$item['num_iid']]; } else { $likes = 0; }
				if (strlen($tag) > 0) { $infotag = '<div class="infotag"><i title="'.$tag.'" class="fa fa-tags fa-2x"></i></div>'; } else { $infotag = ''; }
				$infolog = 'none';
				
				$title = '<p class="title">'.$title.'</p>';
				$html = '<p class="html">'.$html.'</p>';
				$catname = '<p><a href="">'.$catname.'</a></p>';
				$nick = '<p class="nick">'.$nick.'</p>';
				$price = '<p class="price">'.$price.'</p>';
				
				// generate image data
				//list($width, $height, $type, $attr) = getimagesize($img."_400x400.jpg");
				if (strpos($img, '_attr_') !== false) {
					$_img = explode("_attr_", $img);
					$img = $_img[0];
					$_attr_ = str_replace(".jpg", "", $_img[1]);
					$_attr = explode("x", $_attr_);
					$width = $_attr[0];
					$height = $_attr[1];
					if($width > $height) {
						$ratio = $width/$height;
						$width = 274;
						$height = 274/$ratio;
					} else {
						$ratio = $height/$width;
						$width = 274;
						$height = 274*$ratio;
					}
				} else {
					$width = 274;
					$height = 274;
				}
				//$img = '<img src="'.$img.'_400x400.jpg" width="274" height="274">';
				$img = '<img src="'.$img.'_400x400.jpg" width="'.$width.'" height="'.$height.'">';
				
				$_file = file_get_contents(dirname(__FILE__) . '/themes/'.$GLOBALS['_CFG']['template'].'/library/goods_list2.lbi');

				$_templater = $_file;
				$_templater = str_replace("{\$cmd}", $_cmd, $_templater);
				$_templater = str_replace("{\$tag}", $tag, $_templater);
				$_templater = str_replace("{\$interests}", $interests, $_templater);
				$_templater = str_replace("{\$collect_id}", $collect_id, $_templater);
				$_templater = str_replace("{\$title}", $title, $_templater);
				$_templater = str_replace("{\$bonus}", $bonus, $_templater);
				$_templater = str_replace("{\$url}", $url, $_templater);
				$_templater = str_replace("{\$img}", $img, $_templater);
				$_templater = str_replace("{\$striked_price}", $striked_price, $_templater);
				$_templater = str_replace("{\$price}", $price, $_templater);
				$_templater = str_replace("{\$nick}", $nick, $_templater);
				$_templater = str_replace("{\$score}", $score, $_templater);
				$_templater = str_replace("{\$location}", $location, $_templater);
				$_templater = str_replace("{\$views}", $views, $_templater);
				$_templater = str_replace("{\$likes}", $likes, $_templater);
				$_templater = str_replace("{\$tag}", $tag, $_templater);
				$_templater = str_replace("{\$infotag}", $infotag, $_templater);
				$_templater = str_replace("{\$infolog}", $infolog, $_templater);
				$_templater = str_replace("{\$catname}", $catname, $_templater);
				$_templater = str_replace("{\$html}", $html, $_templater);
				$_templater = str_replace("{\$timeago}", $timeago, $_templater);
				$_content .= $_templater;
			//}
		}
	}
	return $_content;
}

function print_usrcustom_content($data='recent', $page) {

	$moderate = false;
	$limit = 20;
	$start = $page * $limit;
	
	if ($data == 'recent')
	{
		if ($_SESSION['user_id'] == 10) {
			$moderate = true;
			$sql = "SELECT rec_id, user_id, case_id, goods_id, referal_id, pic_url, nick, title, html, price, cid, add_time, is_promote, is_check FROM " .$GLOBALS['ecs']->table('collect_goods'). " ORDER BY add_time DESC";
		} else {
			$sql = "SELECT rec_id, user_id, case_id, goods_id, referal_id, pic_url, nick, title, html, price, cid, add_time, is_promote, is_check FROM " .$GLOBALS['ecs']->table('collect_goods'). " WHERE is_promote = 1 ORDER BY add_time DESC";
		}
		
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$sql = "SELECT cat_id, cat_name FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_desc LIKE '%".$row['cid']."%'";
			$_cat = $GLOBALS['db']->getRow($sql);
			if (intval($_cat['cat_id']) > 0) {
				$catname = $_cat['cat_name'];
			} else {
				$catname = "Разное";
			}
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			$referal_list[$row['goods_id']] = $row['referal_id'];
			$case_list[$row['goods_id']] = $row['case_id'];
			$itemlist['taobaoke_items'][] = Array('id' => $row['rec_id'], 'num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => $row['title'], 'html' => $row['html'], 'catname' => $catname, 'timeago' => $row['add_time'], 'is_promote' => $row['is_promote'], 'is_check' => $row['is_check']);
		}
	}
	
	if ($data == 'popular')
	{
		if ($_SESSION['user_id'] == 10) {
			$moderate = true;
			$sql = "SELECT c.rec_id, s.goods_id, c.user_id, c.case_id, c.referal_id, c.pic_url, c.nick, c.title, c.html, c.price, c.cid, c.add_time, c.is_promote, c.is_check FROM " .$GLOBALS['ecs']->table('goods_stat'). " s INNER JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON s.goods_id = c.goods_id ORDER BY s.views DESC";
		} else {
			$sql = "SELECT c.rec_id, s.goods_id, c.user_id, c.case_id, c.referal_id, c.pic_url, c.nick, c.title, c.html, c.price, c.cid, c.add_time, c.is_promote, c.is_check FROM " .$GLOBALS['ecs']->table('goods_stat'). " s INNER JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON s.goods_id = c.goods_id WHERE c.is_promote = 1 ORDER BY s.views DESC";
		}
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$sql = "SELECT cat_id, cat_name FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_desc LIKE '%".$row['cid']."%'";
			$_cat = $GLOBALS['db']->getRow($sql);
			if (intval($_cat['cat_id']) > 0) {
				$catname = $_cat['cat_name'];
			} else {
				$catname = "Разное";
			}
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			if ($row['referal_id']) {
				$referal_list[$row['goods_id']] = $row['referal_id'];
				$case_list[$row['goods_id']] = $row['case_id'];
			} else {
				$referal_list[$row['goods_id']] = $row['goods_id'];
			}
			$itemlist['taobaoke_items'][] = Array('id' => $row['rec_id'], 'num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => $row['title'], 'html' => $row['html'], 'catname' => $catname, 'timeago' => $row['add_time'], 'is_promote' => $row['is_promote'], 'is_check' => $row['is_check']);
		}
	}
	
	if ($data == 'review')
	{
		//$sql = "SELECT s.goods_id, c.referal_id FROM " .$GLOBALS['ecs']->table('goods_stat'). " s LEFT JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON s.goods_id = c.goods_id ORDER BY s.id DESC";
		//$sql = "SELECT goods_id, referal_id FROM " .$GLOBALS['ecs']->table('goods_log'). " WHERE user_id = '".$_SESSION['user_id']."' ORDER BY add_time DESC";
		//$sql = "SELECT c.user_id, l.goods_id, l.referal_id FROM " .$GLOBALS['ecs']->table('goods_log'). " l LEFT JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON c.referal_id = l.referal_id WHERE l.user_id = '".$_SESSION['user_id']."' ORDER BY l.add_time DESC";
		$sql = "SELECT c.user_id, c.case_id, l.goods_id, l.referal_id, l.pic_url, l.nick, l.price, l.add_time FROM " .$GLOBALS['ecs']->table('goods_log'). " l LEFT JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON c.referal_id = l.referal_id WHERE l.user_id = '".$_SESSION['user_id']."' ORDER BY l.add_time DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			if ($row['referal_id']) {
				$referal_list[$row['goods_id']] = $row['referal_id'];
				$case_list[$row['goods_id']] = $row['case_id'];
			} else {
				$referal_list[$row['goods_id']] = $row['goods_id'];
			}
			$itemlist['taobaoke_items'][] = Array('num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => '', 'timeago' => $row['add_time']);
		}
	}
	
	if ($data == 'likes')
	{
		//$sql = "SELECT goods_id FROM " .$GLOBALS['ecs']->table('likes'). " WHERE user_id = '".$_SESSION['user_id']."' ORDER BY add_time DESC";
		//$sql = "SELECT c.user_id, l.goods_id, l.referal_id FROM " .$GLOBALS['ecs']->table('likes'). " l LEFT JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON c.referal_id = l.referal_id WHERE l.user_id = '".$_SESSION['user_id']."' ORDER BY l.add_time DESC";
		$sql = "SELECT c.user_id, c.case_id, l.goods_id, l.referal_id, l.pic_url, l.nick, l.price, l.add_time FROM " .$GLOBALS['ecs']->table('likes'). " l LEFT JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON c.referal_id = l.referal_id WHERE l.user_id = '".$_SESSION['user_id']."' ORDER BY l.add_time DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			if ($row['referal_id']) {
				$referal_list[$row['goods_id']] = $row['referal_id'];
				$case_list[$row['goods_id']] = $row['case_id'];
			} else {
				$referal_list[$row['goods_id']] = $row['goods_id'];
			}
			$itemlist['taobaoke_items'][] = Array('num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => '', 'timeago' => $row['add_time']);
		}
	}
	
	if ($data == 'feeds')
	{
		//$sql = "SELECT f.feed_id, c.user_id, c.goods_id, c.referal_id FROM " .$GLOBALS['ecs']->table('feeds'). " f INNER JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON f.feed_id = c.user_id WHERE f.user_id = '".$_SESSION['user_id']."' ORDER BY c.add_time DESC";
		$sql = "SELECT f.feed_id, c.user_id, c.case_id, c.goods_id, c.referal_id, c.pic_url, c.nick, c.title, c.html, c.price, c.cid, c.add_time FROM " .$GLOBALS['ecs']->table('feeds'). " f INNER JOIN " .$GLOBALS['ecs']->table('collect_goods'). " c ON f.feed_id = c.case_id WHERE f.user_id = '".$_SESSION['user_id']."' ORDER BY c.add_time DESC";
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$goods_list = array();
		$referal_list = array();
		$case_list = array();
		$output_list = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$sql = "SELECT cat_id, cat_name FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_desc LIKE '%".$row['cid']."%'";
			$_cat = $GLOBALS['db']->getRow($sql);
			if (intval($_cat['cat_id']) > 0) {
				$catname = $_cat['cat_name'];
			} else {
				$catname = "Разное";
			}
			$output_list[$row['goods_id']] = '';
			$goods_list[] = $row['goods_id'];
			$referal_list[$row['goods_id']] = $row['referal_id'];
			$case_list[$row['goods_id']] = $row['case_id'];
			$itemlist['taobaoke_items'][] = Array('num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => $row['title'], 'html' => $row['html'], 'catname' => $catname, 'timeago' => $row['add_time']);
		}
	}
	
	if ($data == 'sellers')
	{
		$sql = "SELECT nick, score, goods FROM " .$GLOBALS['ecs']->table('sellers'). " WHERE user_id = '".$_SESSION['user_id']."'";
		$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
		$_content = "";
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$feeded = 0;
			$sql2 = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('sellers'). " WHERE nick='".$row['nick']."'";
			$feeded = $GLOBALS['db']->GetOne($sql2);
			$goods_list = array();
			/*
			$sql2 = "SELECT DISTINCT(goods_id) as goods_id, pic_url FROM " .$GLOBALS['ecs']->table('goods_log'). " WHERE nick='" .$row['nick']. "' ORDER BY add_time DESC";
			$res2 = $GLOBALS['db']->selectLimit($sql2, 10, 0);
			while ($row2 = $GLOBALS['db']->fetchRow($res2))
			{
				$goods_list[] = Array('goods_id' => $row2['goods_id'], 'pic_url' => $row2['pic_url']);
			}
			*/
			$_dump = array();
			// проверка на наличие в dump
			$_filename = dirname(__FILE__).'/temp/dump_caches/'.$row['nick'].'.php';
			if (file_exists($_filename)) {
				include_once($_filename);
				$goods_list = $_dump;
			}
			
			$_templater = '
		<div class="shcase-info">
		 <div style="width:100%;height:450px;display: table-cell;vertical-align: middle;">
		  <div style="width:280px;display: inline-block;">
			<p>&nbsp;</p>
			<center><div style="width:150px;height:150px;background:url(/images/icon-seller.jpg) 100% 100% no-repeat;background-size:cover;border-radius:75px;border:1px solid #ccc"></div></center>
			<div class="title" style="margin:5px 0 5px 0;text-align:center"><b>'.$row['nick'].'</b></div>
			<p><img src="/images/'.$row['score'].'.gif"></p>
			<p style="font-size:14px;color:#000;margin:5px 0 5px 0;text-align:center">1 просмотров</p>
			<center>
			<a style="
		display:block;
		width:250px;
		padding:10px 0 10px 0;
		font-size:14px;
		text-decoration:none;
		color:#fff;
		background:#ff0054;
		border:0px solid #007cb9;
		border-radius:20px;
		text-align:center;
		cursor:pointer;
		font-family:\'Ubuntu Condensed\', sans-serif;
		text-transform:uppercase;" href="/seller/'.$row['nick'].'/">Посетить</a>
			</center>
			<p style="font-size:14px;color:#000;margin:5px 0 5px 0;text-align:center">Товаров: '.$row['goods'].' &middot; Подписчиков: '.$feeded.'</p>
		  </div>
		 </div>
		</div>
		
		<div class="shcase-block1">
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[0]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[0]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[1]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[1]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[2]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[2]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
		</div>
		
		<div class="shcase-block2">
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[3]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[3]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[4]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[4]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[5]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[5]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
		</div>
		
		<div class="shcase-block3">
			<div class="item" style="width:225px;height:225px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[6]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[6]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:225px;height:225px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[7]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[7]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
		</div>
		
		<div class="shcase-block4">
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[8]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[8]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#efefef">
				<a href="/taobao/'.$goods_list[9]['goods_id'].'/">
					<div class="boxx" style="background:url('.$goods_list[9]['pic_url'].'_400x400.jpg) 100% center no-repeat;background-size: cover;"></div>
				</a>
			</div>
			<div class="item" style="width:150px;height:150px;margin:0px;border: 5px solid #fff;background:#111;color:#fff">
				<div style="font: 60px \'pocket_calculatorregular\', Arial, sans-serif;color:#ff0054;margin-top:45px;line-height:40px">'.$row['goods'].'</div>
				<span style="font-size:12px">ТОВАРОВ</span>
			</div>
		</div>
		
		<div style="width:100%;height:20px;border-top:0px solid #e0e0e0;clear:both"></div>
			';

			$_content .= $_templater;
		}
		return $_content;
	}
	
	if ($data == 'tags')
	{
		$_tags = '';
		$sql = "SELECT tags FROM " .$GLOBALS['ecs']->table('user_custom'). " WHERE user_id = '".$_SESSION['user_id']."'";
		$_tags = $GLOBALS['db']->getOne($sql);
		if (strlen($_tags) > 0)
		{
			$_SESSION['_tags'] = explode(" ", $_tags);
			$_tags = str_replace(" ","|",$_tags);
			//$sql = "SELECT t.goods_id, c.user_id, c.referal_id FROM " .$GLOBALS['ecs']->table('tag'). " t left join " .$GLOBALS['ecs']->table('collect_goods'). " c ON c.goods_id = t.goods_id WHERE t.tag_words RLIKE '" .$_tags. "' ORDER BY c.add_time DESC";
			$sql = "SELECT t.goods_id, c.user_id, c.case_id, c.referal_id, c.pic_url, c.nick, c.price FROM " .$GLOBALS['ecs']->table('tag'). " t left join " .$GLOBALS['ecs']->table('collect_goods'). " c ON c.goods_id = t.goods_id WHERE t.tag_words RLIKE '" .$_tags. "' ORDER BY c.add_time DESC";
			$res = $GLOBALS['db']->selectLimit($sql, $limit, $start);
			$goods_list = array();
			$referal_list = array();
			$case_list = array();
			$output_list = array();
			while ($row = $GLOBALS['db']->fetchRow($res))
			{
				$output_list[$row['goods_id']] = '';
				$goods_list[] = $row['goods_id'];
				$referal_list[$row['goods_id']] = $row['referal_id'];
				$case_list[$row['goods_id']] = $row['case_id'];
				$itemlist['taobaoke_items'][] = Array('num_iid' => $row['goods_id'], 'pic_url' => $row['pic_url'], 'nick' => $row['nick'], 'price' => $row['price'], 'title' => '');
			}
		}
		if ($page == 0)
		{
			$_content = '<center><div class="mytags">
				<h1 id="meta_title">&nbsp;</h1>
				<form id="tagmanager">
				<input type="text" name="tags" placeholder="теги и интересы отдельными словами через пробел" autocomplete="on" class="tm-input"><br>
				<a id="save_tags" style="
		width:150px;
		padding:3px 0 3px 0;
		margin:0 8px 5px 0;
		font-size:14px;
		text-decoration:none;
		color:#fff;
		background:#bd081c;
		border:1px solid #bd081c;
		text-align:center;
		cursor:pointer;
		font-family:\'Ubuntu Condensed\', sans-serif;
		text-transform:uppercase;" href="">Сохранить все теги</a>
				</form>
		</div></center>';
		}
	}
	
	$num_iids = implode(",",$goods_list); //print_r ($num_iids);exit;
	
	if (strlen($num_iids) > 0)
	{
		// log: begin
		$sql = "SELECT id, goods_id FROM " .$GLOBALS['ecs']->table('goods_log'). " WHERE user_id = '".$_SESSION['user_id']."' AND goods_id IN (" . $num_iids . ")";
		$res = $GLOBALS['db']->query($sql);
		$logs = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$logs[$row['goods_id']] = $row['id'];
		}
		// end
		
		// tag list: begin
		$sql = "SELECT goods_id, tag_words FROM " .$GLOBALS['ecs']->table('tag'). " WHERE goods_id IN (" . $num_iids . ")";
		$res = $GLOBALS['db']->query($sql);
		$tags = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$tags[$row['goods_id']] = $row['tag_words'];
		}
		// end
		
		// infogram list: bigin
		$sql = "SELECT g.goods_id, g.views FROM " .$GLOBALS['ecs']->table('goods_stat'). " g WHERE g.goods_id IN (" . $num_iids . ")";
		$res = $GLOBALS['db']->query($sql);
		$_views = array();
		$_likes = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$_views[$row['goods_id']] = $row['views'];
			$_likes[$row['goods_id']] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('likes'). " WHERE goods_id = '".$row['goods_id']."'");
		}
		// end
		
		// case list: begin
		$sql = "SELECT id, user_id, title, add_time FROM " .$GLOBALS['ecs']->table('showcase');
		$res = $GLOBALS['db']->query($sql);
		$case = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$case[$row['id']] = Array('id' => $row['add_time'], 'title' => $row['title'], 'idx' => $row['id']);
		}
		// end
		
		$_cmd = "display:none";
		$_KURS = get_current_rate();
		
		//require_once('includes/import/class.taobao.php');
		//$otapixml = new OTAPIxml();
		//$itemlist = $otapixml->GetVendorItemList($num_iids);
		/*
		if (count($goods_list) == 1)
		{
			$_temp = $itemlist['taobaoke_items'];
			$itemlist['taobaoke_items'] = Array();
			$itemlist['taobaoke_items'][0] = $_temp;
			$itemlist['taobaoke_items'][1] = Array ( 'nick' => '', 'num_iid' => '', 'pic_url' => '', 'price' => '', 'title' => '' );
		}
		*/
		foreach ($itemlist['taobaoke_items'] as $item)
		{
			if($item['num_iid'] !== '') {
				$output_list[$item['num_iid']] = $item;
			}
		}
		
		foreach ($output_list as $key => $item)
		{
			if ($item['num_iid'] !== '') {
				$img = $item['pic_url'];
				$url = '/taobao/'.$referal_list[$item['num_iid']].'/';
				$price = price_format($item['price'] * $_KURS, true);
			} else {
				$item['num_iid'] = $key;
				$img = '/images/out-of-stock';
				$url = '';
				$price = '';
			}
				$artikul = $item['num_iid'];
				$title = $item['title'];
				$html = $item['html'];
				$catname = $item['catname'];
				$_timeago = $item['timeago'];
				
				if ($_timeago > 0) {
					$timeago = local_date('Y-m-d H:i:s', $_timeago);
				} else {
					$timeago = date("Y-m-d H:i:s");
				}
				
				if ($case[$case_list[$item['num_iid']]]['id'] > 0) {
					$filename = '/var/www/capskg/data/www/ooba.kg/images/upload/showcase/'.$case[$case_list[$item['num_iid']]]['idx'].'.png';
					if (file_exists($filename)) {
						$nick = '<a href="/showcase/'.$case[$case_list[$item['num_iid']]]['id'].'/"><img src="/images/upload/showcase/'.$case[$case_list[$item['num_iid']]]['idx'].'.png" align="left">'.substr_str($case[$case_list[$item['num_iid']]]['title']).'</a>';
					} else {
						$nick = '<a href="/showcase/'.$case[$case_list[$item['num_iid']]]['id'].'/"><img src="/images/upload/showcase/none.png" align="left">'.substr_str($case[$case_list[$item['num_iid']]]['title']).'</a>';
					}
				} else {
					$nick = '<a href="/seller/'.$item['nick'].'/"><img src="http://semantic-ui.com/images/avatar/large/elliot.jpg" align="left">'.substr_str($item['nick']).'</a>';
				}
				
				$score = '';
				$tag = $tags[$item['num_iid']];
				$collect_id = $collect_list[$item['num_iid']];
				$interests = '';
				$views = $_views[$item['num_iid']];
				if ($_likes[$item['num_iid']] > 0) { $likes = $_likes[$item['num_iid']]; } else { $likes = 0; }
				if (strlen($tag) > 0) { $infotag = '<div class="infotag"><i title="'.$tag.'" class="fa fa-tags fa-2x"></i></div>'; } else { $infotag = ''; }
				if ($logs[$item['num_iid']])
				{
					if ($data !== 'review' && $data !== 'likes') {
						$infolog = 'block'; 
					} else {
						$infolog = 'none';
					}
				} else { $infolog = 'none'; }
				
				$title = '<p class="title">'.$title.'</p>';
				$html = '<p class="html">'.$html.'</p>';
				$catname = '<p><a href="">'.$catname.'</a></p>';
				$nick = '<p class="nick">'.$nick.'</p>';
				$price = '<p class="price">'.$price.'</p>';
				
				// generate image data
				//list($width, $height, $type, $attr) = getimagesize($img."_400x400.jpg");
				if (strpos($img, '_attr_') !== false) {
					$_img = explode("_attr_", $img);
					$img = $_img[0];
					$_attr_ = str_replace(".jpg", "", $_img[1]);
					$_attr = explode("x", $_attr_);
					$width = $_attr[0];
					$height = $_attr[1];
					if($width > $height) {
						$ratio = $width/$height;
						$width = 274;
						$height = 274/$ratio;
					} else {
						$ratio = $height/$width;
						$width = 274;
						$height = 274*$ratio;
					}
				} else {
					$width = 274;
					$height = 274;
				}
				
				if ($infolog == 'block') {
					$img = '<div style="position:absolute;z-index:99999;width:'.$width.'px;height:'.$height.'px;text-align:center;vertical-align:middle;background-color:rgba(255,255,255,.6);"><div style="margin:'.($height/2 - 20).'px 50px 0 50px;border-radius:5px;padding:10px;box-shadow: 0 0 0 2px #000 inset!important;color:#000;text-shadow: none!important;">ПРОСМОТРЕНО</div></div><img src="'.$img.'_400x400.jpg" width="'.$width.'" height="'.$height.'" style="-webkit-filter: blur(0) grayscale(1);filter: blur(0) grayscale(1);">';
				} else {
					$img = '<img src="'.$img.'_400x400.jpg" width="'.$width.'" height="'.$height.'">';
				}
				
				$tools = '<div class="tools">';
				if ($moderate == true) {
					if ($item['is_check'] == 1) { $tools .= '<button data="'.$item['id'].'" class="is_check active"><i class="fa fa-eye fa-2x"></i></button>'; } else { $tools .= '<button data="'.$item['id'].'" class="is_check"><i class="fa fa-eye fa-2x"></i></button>'; }
					if ($item['is_promote'] == 1) { $tools .= '<button data="'.$item['id'].'" class="is_promote active"><i class="fa fa-exclamation-circle fa-2x"></i></button>'; } else { $tools .= '<button data="'.$item['id'].'" class="is_promote"><i class="fa fa-exclamation-circle fa-2x"></i></button>'; }
				}
				$tools .= '</div>';
				
				$_file = file_get_contents(dirname(__FILE__) . '/themes/'.$GLOBALS['_CFG']['template'].'/library/goods_list2.lbi');
				
				$_templater = $_file;
				$_templater = str_replace("<noscript></noscript>", $tools, $_templater);
				$_templater = str_replace("{\$cmd}", $_cmd, $_templater);
				$_templater = str_replace("{\$tag}", $tag, $_templater);
				$_templater = str_replace("{\$interests}", $interests, $_templater);
				$_templater = str_replace("{\$collect_id}", $collect_id, $_templater);
				$_templater = str_replace("{\$title}", $title, $_templater);
				$_templater = str_replace("{\$bonus}", $bonus, $_templater);
				$_templater = str_replace("{\$url}", $url, $_templater);
				$_templater = str_replace("{\$img}", $img, $_templater);
				$_templater = str_replace("{\$striked_price}", $striked_price, $_templater);
				$_templater = str_replace("{\$price}", $price, $_templater);
				$_templater = str_replace("{\$nick}", $nick, $_templater);
				$_templater = str_replace("{\$score}", $score, $_templater);
				$_templater = str_replace("{\$location}", $location, $_templater);
				$_templater = str_replace("{\$views}", $views, $_templater);
				$_templater = str_replace("{\$likes}", $likes, $_templater);
				$_templater = str_replace("{\$infotag}", $infotag, $_templater);
				$_templater = str_replace("{\$infolog}", $infolog, $_templater);
				$_templater = str_replace("{\$catname}", $catname, $_templater);
				$_templater = str_replace("{\$html}", $html, $_templater);
				$_templater = str_replace("{\$timeago}", $timeago, $_templater);
				$_content .= $_templater;
			//}
		}
	}
	return $_content;
}

function format_by_count($count, $form1, $form2, $form3)
{
    $count = abs($count) % 100;
    $lcount = $count % 10;
    if ($count >= 11 && $count <= 19) return($form3);
    if ($lcount >= 2 && $lcount <= 4) return($form2);
    if ($lcount == 1) return($form1);
    return $form3;
}

function substr_str($str)
{
	mb_internal_encoding("UTF-8");
	$substr = mb_substr($str, 0, 20);
	if ($substr !== $str) {
		$_str = $substr.'...';
	} else {
		$_str = $str;
	}
	
	return $_str;
}

?>