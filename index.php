<?php
$CookieDefault = "默认Cookie";
error_reporting(0);

/* 虾米音乐 XML 接口
 * 单个音乐信息: http://www.xiami.com/song/playlist/id/音乐ID/type/0 比省略/type/0的要详细
 * 专辑音乐信息: http://www.xiami.com/song/playlist/id/专辑ID/type/1
 * 艺人热度前20的音乐信息(实际上是"http://www.xiami.com/artist/top/id/艺人ID"的歌曲列表): http://www.xiami.com/song/playlist/id/艺人ID/type/2
 * 精选集音乐信息: http://www.xiami.com/song/playlist/id/精选集ID/type/3
 * 今日推荐歌单: http://www.xiami.com/song/playlist/id/1/type/9
*/

function get_info($sid, $type) {
	$url = 'http://www.xiami.com/song/playlist/id/'.$sid.'/type/'.$type;
	$useCookie = $type==9 ? 1 : null;
	$result = curl_http($url, $useCookie);
	$data = array();
	if ($result) {
		$result = str_replace(array('<![CDATA[', ']]>'), array('', ''), $result);
		$xml = simplexml_load_string($result);
		$arr = json_decode(json_encode($xml), true);
		$html = '';
		$track = $arr['trackList']['track'];
		if ($type==0) {
			$html = '<img src="'.$track['pic'].'"><div><strong>标题：</strong>'.$track['title'].'</div><div><strong>艺人：</strong>'._a($track['artist'], $track['artist_id'], 'artist').'</div>';
			if (is_string($track['album_name']))
				$html.= '<div><strong>专辑：</strong>'._a($track['album_name'], $track['album_id'], 'album').'</div>';
			if (is_string($track['lyric']))
				$html.= '<div><strong>歌词：</strong>'.$track['lyric'].'</div>';
			$song = 'http://www.xiami.com/song/gethqsong/sid/'.$sid;
			$json = curl_http($song, 1);
			if ($json) {
				$location = json_decode($json)->location;
				$data['src'] = get_location($location);
				$html.= '<strong id="song">歌曲：</strong><div id="case"><label id="case-label"><input id="src" size="124" onmouseover="this.select()" value="'.$data['src'].'"></label></div>';
			}
		}
		elseif ($type==1) {
			$html = '<img src="'.$track[0]['pic'].'"><div><strong>专辑：</strong>'.$track[0]['album_name'].'</div><div><strong>艺人：</strong>'._a($track[0]['artist'], $track[0]['artist_id'], 'artist').'</div><ol>';
			foreach ($track as $item) {
				$html.= '<li>'._a($item['title'], $item['song_id'], 'song').'</li>';
			}
			$html.= '</ol>';
		}
		elseif ($type==2) {
			$html = '<div><strong>'.$track[0]['artist'].'的热门曲目：</strong></div><ol>';
			foreach ($track as $item) {
				$html.= '<li>'._a($item['title'], $item['song_id'], 'song');
				if (is_string($item['album_name']))
					$html.= ' - 《'._a($item['album_name'], $item['album_id'], 'album').'》';
				$html.= '</li>';
			}
			$html.= '</ol>';
		}
		elseif ($type==3 || $type==9) {
			$title = $type==3 ? '精选集曲目：' : '今日歌单曲目：';
			$html = '<div><strong></strong></div><ol>';
			foreach ($track as $item) {
				$html.= '<li>'._a($item['title'], $item['song_id'], 'song').' - '._a($item['artist'], $item['artist_id'], 'artist');
				if (is_string($item['album_name']))
					$html.= ' - 《'._a($item['album_name'], $item['album_id'], 'album').'》';
				$html.= '</li>';
			}
			$html.= '</ol>';
		}
		$data['info'] = htmlspecialchars($html);
	}
	return $data;
}

function curl_http($url, $useCookie){
	$cookie = $_POST['cookie'];
	if ($cookie==null && $useCookie!=null) {
		global $CookieDefault;
		$cookie = $CookieDefault;
	}
	if (!preg_match('/member_auth=/i', $cookie, $matches)) $cookie = 'member_auth='.$cookie;
	$proxy = $_POST['proxy'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if ($useCookie!=null) curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	curl_setopt($ch, CURLOPT_REFERER, 'www.xiami.com');
	if ($proxy!=null) curl_setopt($ch, CURLOPT_PROXY, $proxy);
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000*10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result=curl_exec($ch);
	$err=curl_errno($ch);
	curl_close($ch);
	if($err){
		return false;
	} else {
		return $result;
	}
}

function _a($a, $id, $l) {
	$_a = '<a href="javascript:void(0)" onclick="setinput(\'/'.$l.'/'.$id.'\')">'.$a.'</a>';
	return $_a;
}

function get_location($str) {
	try{
		$a1=(int)$str{0};
		$a2=substr($str, 1);
		$a3=floor(strlen($a2) / $a1);
		$a4=strlen($a2) % $a1;
		$a5=array();
		$a6=0;
		$a7='';
		for(;$a6 < $a4; ++$a6) {
			$a5[$a6]=substr($a2, ($a3 + 1) * $a6, ($a3 + 1));
		}
		for(;$a6 < $a1; ++$a6) {
			$a5[$a6]=substr($a2, $a3 * ($a6 - $a4) + ($a3 + 1) * $a4, $a3);
		}
		for($i=0, $a5_0_length=strlen($a5[0]); $i < $a5_0_length; ++$i) {
			for($j=0, $a5_length=count($a5); $j < $a5_length; ++$j) {
				if (isset($a5[$j]{$i})) $a7.=$a5[$j]{$i};
			}
		}
		$a7=str_replace('^', '0', urldecode($a7));
		return $a7;
	} catch(Exception $e) {
		return false;
	}
}

// 获取Ajax请求
if (isset($_GET['get'])):

$data['status'] = 0;

if (isset($_POST['url']) && $_POST['url']) {
	$url = $_POST['url'];
	if (preg_match('#/song/(\d+)(\?*|)#i', $url, $matches) || preg_match('#/demo/(\d+)(\?*|)#i', $url, $matches)) {
		$song_id = $matches[1];
		$type = 0;
		$data = array_merge($data, get_info($song_id, $type));
		if (isset($data['src'])) $data['status'] = 1;
	}
	else {
		if (preg_match('#/album/(\d+)(\?*|)#i', $url, $matches)) {
			$sid = $matches[1];
			$type = 1;
		}
		elseif (preg_match('#/artist/(\d+)(\?*|)#i', $url, $matches)) {
			$sid = $matches[1];
			$type = 2;
		}
		elseif (preg_match('#/showcollect/id/(\d+)(\?*|)#i', $url, $matches) || preg_match('#/collect/(\d+)(\?*|)#i', $url, $matches)) {
			$sid = $matches[1];
			$type = 3;
		}
		elseif (preg_match('#/song/playlist/id/1/type/9#i', $url, $matches)) {
			$sid = 1;
			$type = 9;
		}
		$data = array_merge($data, get_info($sid, $type));
		if (isset($data['info'])) $data['status'] = 1;
	}
}
die(json_encode($data));

endif;

// Chrome 获取 Cookie 方法
if (isset($_GET['cookie'])):
die('<img src="assets/Chrome-Cookie.gif">');
endif;

if (isset($_GET['help'])):
echo '<ol><li><p><strong>支持链接，链接后面的</strong> ?spm=xxx <strong>可有可无：</strong></p><ul><li>单曲：<a href="http://www.xiami.com/song/2085857" target=_blank>http://www.xiami.com<strong>/song/2085857</strong></a></li><li>艺人：<a href="http://www.xiami.com/artist/23503" target=_blank>http://www.xiami.com<strong>/artist/23503</strong></a></li><li>专辑：<a href="http://www.xiami.com/album/168931" target=_blank>http://www.xiami.com<strong>/album/168931</strong></a></li><li>精选集：<a href="http://www.xiami.com/collect/42563832" target=_blank>http://www.xiami.com<strong>/collect/42563832</strong></a></li><li>DEMO：<a href="http://i.xiami.com/zhangchao/demo/1775392054" target=_blank>http://i.xiami.com/zhangchao<strong>/demo/1775392054</strong></a></li><li>今日歌单（需要cookie）：<a href="http://www.xiami.com/play?ids=/song/playlist/id/1/type/9" target=_blank>http://www.xiami.com/play?ids=<strong>/song/playlist/id/1/type/9</strong></a></li></ul></li><li><p><strong>高级选项：</strong></p><ul><li>在服务器使用：首先使用国内HTTP代理登录虾米，登录后获取 Cookie: member_auth（<a href="?cookie" target=_blank>获取方法</a>），打开服务器虾米解析页面，点开高级选项，左边输入member_auth，右边输入代理IP进行解析。</li><li>在本地使用：本地登录虾米，本地搭建PHP环境，打开虾米解析页面，点开高级选项，输入 member_auth 并进行解析。</li></ul></li><li><p><strong>注意事项：</strong></p><ul><li>本工具所有代码开源，不会收集用户信息，若不放心可以不输入 Cookie ，或者本地搭建使用。</li><li>今日歌单需要用户自己的member_auth才能解析到，否则解析默认歌单。</li><li>只有member_auth和登录IP同时匹配才能解析到高品质音乐，部分音乐只有普通品质。</li><li>经测试，只要匹配member_auth和登录IP，普通会员也能解析高品质，不信本地测试就知道了。</li></ul></li></ol>';
; else :
?>
<!DOCTYPE html>
<html>
<head>
<title>解析虾米高品质音乐地址</title>
<meta charset="UTF-8">
<meta name="keywords" content="虾米,虾米网,虾米音乐,音乐,MP3,解析,下载"/>
<meta name="description" content="用于解析虾米高品质音乐地址的网页小工具, 输入虾米VIP的member_auth可获得高品质音乐"/>
<link rel="stylesheet" type="text/css" href="assets/style.css"/>
<script type="text/javascript" src="assets/xiami.js"></script>
</head>
<body>
<div id="page">
	<h1>解析虾米高品质音乐地址</h1>
	<form id="form" method="post">
		<div id="div">
			<label id="label">
				<input id="input" class="input" name="url" type="text" value="" placeholder="请输入音乐地址" />
			</label>
		</div>
		<input id="submit" type="submit" value="解析" />
		<div class="clear"></div>
		<div id="advanced">
			<input class="input input-left" name="cookie" type="text" value="" placeholder="Cookie: member_auth" />
			<input class="input input-right" name="proxy" type="text" value="" placeholder="example: 127.0.0.1:8087">
		</div>
	</form>
	<audio id="audio" src="" controls style="display:none"></audio>
	<pre id="info"></pre>
	<div id="error">解析失败！</div>
</div>
<div id="help"></div>
<div id="footer">
	<span class="item item-left"><a id="show-help" href="javascript:void(0)">查看帮助</a></span><span class="item item-center"><a href="http://yuanmu.mzzhost.com/">yuanmu.mzzhost.com</a> © All Rights Reserved</span><span class="item item-right"><a href="javascript:void(0)" onclick="$('#advanced').slideToggle(200)">高级选项</a></span>
</div>
</body>
</html>
<?php endif;