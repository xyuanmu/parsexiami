<?php
/* !
 * ! 虾米音乐 XML 接口:
 * 单个音乐信息: http://www.xiami.com/song/playlist/id/音乐ID/type/0
 * 专辑音乐信息: http://www.xiami.com/song/playlist/id/专辑ID/type/1
 * 艺人热度歌曲: http://www.xiami.com/song/playlist/id/艺人ID/type/2
 * 精选集音乐信息: http://www.xiami.com/song/playlist/id/精选集ID/type/3
 * 今日推荐歌单: http://www.xiami.com/song/playlist/id/1/type/9 (需要Cookie)
 *
 * ! 网易云API接口：
 * 音乐：http://music.163.com/api/song/detail/?id=音乐ID&ids=%5B音乐ID%5D
 * 专辑：http://music.163.com/api/album/专辑ID (需要referer)
 * 艺人：http://music.163.com/api/artist/艺人ID (需要referer)
 * 歌单：http://music.163.com/api/playlist/detail?id=歌单ID
*/

class Get_Music{
	private static $_COOKIE = "默认Cookie";
	private static $_PROXY = "";
	private static $_REFERER_XIAMI = "http://www.xiami.com/";
	private static $_REFERER_NETEASE = "http://music.163.com/";
	private static $_PUBKEY = "65537";
	private static $_NONCE = "0CoJUm6Qyw8W8jud";
	private static $_MODULUS = "157794750267131502212476817800345498121872783333389747424011531025366277535262539913701806290766479189477533597854989606803194253978660329941980786072432806427833685472618792592200595694346872951301770580765135349259590167490536138082469680638514416594216629258349130257685001248172188325316586707301643237607";
	private static $_HEADER = array(
		'X-Real-IP: 118.88.88.88',
		'Accept-Language: zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
		'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
	);


	public function curl_http($url, $useCookie, $referer=null, $get_header=null, $post=null){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// Use Proxy for Overseas Host
		$proxy = isset($_POST["proxy"]) && $_POST["proxy"] ? $_POST["proxy"] : self::$_PROXY;
		if ($proxy){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		// Use Cookie for xiami Suggested Playlist
		if ($useCookie){
			$cookie = isset($_POST["cookie"]) && $_POST["cookie"] ? $_POST["cookie"] : self::$_COOKIE;
			if (!preg_match('/member_auth=/i', $cookie)) $cookie = 'member_auth='.$cookie;
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::$_HEADER);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000*5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($get_header){
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, 1);
		}
		if ($post){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		for ($i=0; $i<=3; $i++){
			$result = curl_exec($ch);
			$error = curl_errno($ch);
			if (!$error) {
				break;
			}
		}
		curl_close($ch);
		if($error){
			return false;
		} else {
			return $result;
		}
	}

	public function get_xiami($sid, $type){
		if ($sid!=1){
			$info = explode("/", $sid);
			if (preg_match("/[a-zA-Z]/", $info[1])){
				$url = "http://www.xiami.com/".$sid;
				$pageindex = $this->curl_http($url, 0);
				preg_match("#link rel=\"canonical\" href=\"http://www.xiami.com/\w+/(\d+)\"#", $pageindex, $matches);
				if ($matches){
					$sid = $matches[1];
				} else {
					$data['info'] = '获取歌曲ID失败！';
					return $data;
				}
			} else {
				$sid = $info[1];
			}
		}
		$url = 'http://www.xiami.com/song/playlist/id/'.$sid.'/type/'.$type.'/cat/json';
		$useCookie = $type==9 ? 1 : 0;
		$result = json_decode($this->curl_http($url, $useCookie), true);




		if (isset($result["data"]["trackList"])){







			$track = $result["data"]["trackList"];
			$close = '<div class="close" title="关闭">×</div>';
			$html = '<div class="info-item">';
			if ($result["message"])
				$html.= '<p><strong>'.$result["message"].'</strong></p>';
			if ($type==0){
				$url = 'http://www.xiami.com/song/gethqsong/sid/'.$sid;
				$json = json_decode($this->curl_http($url, 0, self::$_REFERER_XIAMI), true);
				$location = $json ? $json["location"] : '';
				if ($location){
					$data["src"] = $this->xiami_location($location);
					$data['status'] = 1;
				} else {
						$data['info'] = '获取歌曲链接失败！';
						return $data;
				}
				$track = $track[0];
				$name = $track["songName"];
				$artist = $track["artist_name"];
				$title = $artist.' - '.$name;
				$html.= '<img class="cover" src="'.$track["pic"].'"><div><strong>标题：</strong>'.$name.'</div><div><strong>艺人：</strong>'.$this->_a($artist, $track["artist_id"], 'artist').'</div>';
				$html.= is_string($track["album_name"]) ? '<div><strong>专辑：</strong>'.$this->_a($track["album_name"], $track["album_id"], 'album').'</div>' : '';
				$html.= is_string($track["lyric"]) ? '<div><strong>歌词：</strong><a href="?xmlyric='.$track["lyric"].'&title='.$title.'">'.$title.'.lrc(点击下载)</a></div>' : '';

				$html.= '<strong>歌曲：</strong><a href="'.$data["src"].'" download="'.$title.'.mp3"></label>'.$title.'.mp3(点击下载)</a>';
				$html.= $close.'</div>';
			}
			else {
				$title = $type==3 ? '精选集曲目：' : '今日歌单曲目：';

				if (isset($track[0])){
					$html.= $type==1 ? '<img src="'.$track[0]["pic"].'"><div><strong>专辑：</strong>'.$track[0]["album_name"].'</div><div><strong>艺人：</strong>'.$this->_a($track[0]["artist"], $track[0]["artist_id"], 'artist') : '';
					$html.= $type==2 ? '<div><strong>'.$track[0]["artist"].'的热门曲目：</strong>' : '';



				}
				$html.= $type==3 || $type==9 ? '<div><strong>'.$title.'</strong>' : '';
				$html.= '</div><ol>';

				foreach ($track as $item){
					$html.= '<li>'.$this->_a($item["songName"], $item["song_id"], 'song');
					$html.= $type==3 || $type==9 ? ' - '.$this->_a($item["artist"], $item["artist_id"], 'artist') : '';







					if ($type!=1)
						$html.= ' - 《'.$this->_a($item["album_name"], $item["album_id"], 'album').'》';
					$html.= '</li>';
				}
				$html.= '</ol>'.$close.'</div>';
				$data['status'] = 1;
			}
			$data['info'] = htmlspecialchars($html);
		} else {
			$data['info'] = '解析失败！';
		}
		return $data;
	}

	public function get_netease($keyLink){
		$keywords = explode("?id=", $keyLink);
		$mid = $keywords[1];
		$type = $keywords[0];
		$close = '<div class="close" title="关闭">×</div>';

		switch ($type){
			case "song";
				$url = "http://music.163.com/api/song/detail/?id=" . $mid . "&ids=[" . $mid . "]";
				$result = json_decode($this->curl_http($url, 0, self::$_REFERER_NETEASE), true);
				if( $result["code"]==200 && isset($result["songs"][0]) ){
					$cake = $result["songs"][0];
					$name = $cake["name"];
					$album = $cake["album"]["name"];
					$albumID = $cake["album"]["id"];
					$artist = $cake["artists"][0]["name"];
					$artistID = $cake["artists"][0]["id"];
					$title = $artist.' - '.$name;
					$song = $this->netease_new_api($mid);
					$song_url = $song['url'];
					$song_bit = substr($song['br'], 0, 3);
					if (!is_string($song_url)) {
						$data['info'] = '付费歌曲暂时无法解析！';
						return $data;
					}
					$data["src"] = $song_url;
					$html = '<div class="info-item"><img class="cover" src="'.$cake["album"]["picUrl"].'?param=100y100"><div><strong>标题：</strong>'.$name.'</div><div><strong>艺人：</strong>'.$this->_a($artist, $artistID, 'artist', 'ne').'</div>';
					$html.= '<div><strong>专辑：</strong>'.$this->_a($album, $albumID, 'album', 'ne').'</div>';
					$html.= '<div><strong>歌词：</strong><a href="?nelyric='.$mid.'&title='.$title.'">'.$title.'.lrc(点击下载)</a></div>';

					$html.= '<strong>歌曲('.$song_bit.'Kbps)：</strong><a href="'.$song_url.'" download="'.$title.'.mp3">'.$title.'.mp3(点击下载)</a>';
					$html.= $close.'</div>';

					$data['info'] = htmlspecialchars($html);
					$data['status'] = 1;
				} else {
					$data['info'] = '获取歌曲链接失败！';
				}
				return $data;
			break;
			case "album":
				$url = "http://music.163.com/api/album/".$mid;
				$result = json_decode($this->curl_http($url, 0, self::$_REFERER_NETEASE), true);
				if (isset($result['album']['songs'])){
					$cake = $result['album'];
					$list = $result['album']['songs'];
					$album = $cake['name'];
					$artist = $cake['artist']['name'];
					$artistID = $cake['artist']['id'];
					$html = '<div class="info-item"><img src="'.$cake['picUrl'].'?param=100y100"><div><strong>专辑：</strong>'.$album.'</div><div><strong>艺人：</strong>'.self::_a($artist, $artistID, 'artist', 'ne').'</div>';
					$html.='<ol>';
					foreach ($list as $single){
						$html.= self::list_template('ne', $single['name'], $single['id'], 'song');
					}
					$html.= '</ol>'.$close.'</div>';
					$data['info'] = htmlspecialchars($html);
					$data['status'] = 1;
				} else {
					$data['info'] = '解析失败！';
				}
				return $data;
			break;

			case "artist";
				$url = "http://music.163.com/api/artist/".$mid;
				$result = json_decode($this->curl_http($url, 0, self::$_REFERER_NETEASE), true);
				if (isset($result['hotSongs'])){
					$list = $result['hotSongs'];
					$artist = $result['artist']['name'];
					$html = '<div class="info-item"><div><strong>'.$artist.'的热门曲目：</strong></div><ol>';
					foreach ($list as $single){
						$html.= self::list_template('ne', $single['name'], $single['id'], 'song', $single['album']['name'], $single['album']['id'], 'album');
					}
					$html.= '</ol>'.$close.'</div>';
					$data['info'] = htmlspecialchars($html);
					$data['status'] = 1;
				} else {
					$data['info'] = '解析失败！';
				}
				return $data;
			break;

			case "playlist";
				$url = "http://music.163.com/api/playlist/detail?id=".$mid;
				$result = json_decode($this->curl_http($url, 0, self::$_REFERER_NETEASE), true);;
				if (isset($result['result']['tracks'])){
					$cake = $result['result'];
					$list = $result['result']['tracks'];
					$name = $cake['name'];
					$html = '<div class="info-item"><div><strong>精选集《'.$name.'》</strong></div>';
					$html.= strlen($cake['description']) > 3 ? '<div class="desc"><strong>简介：</strong>'.str_replace("\n", "", $cake['description']).'</div>' : '';
					$html.='<ol>';
					foreach ($list as $single){
						$artists = $single['artists'][0];
						$html.= $this->list_template('ne', $single['name'], $single['id'], 'song', $single['album']['name'], $single['album']['id'], 'album', $artists['name'], $artists['id'], 'artist');
					}
					$html.= '</ol>'.$close.'</div>';
					$data['info'] = htmlspecialchars($html);
					$data['status'] = 1;
				} else {
					$data['info'] = '解析失败！';
				}
				return $data;
			break;
		}
	}

	public function search($s, $limit=6){
		$close = '<div class="close" title="关闭">×</div>';
		$html = '<div class="info-item">';
		$html.= $this->search_xm($s);
		$html.= $this->search_ne($s);
		$html.= $close.'</div>';
		$data['info'] = htmlspecialchars($html);
		$data['status'] = 1;
		return $data;
	}

	private function search_xm($s, $limit=10){
		$url = 'http://api.xiami.com/web?';
		$get = array(
			'app_key' => '9',
			'key' => $s,
			'page' => 1,
			'limit' => $limit,
			'r' => 'search/songs'
		);
		$result = json_decode($this->curl_http($url.http_build_query($get), 0, self::$_REFERER_XIAMI), true);
		if (isset($result['data']['songs'])){
			$list = $result['data']['songs'];
			$html = '<div><strong>虾米音乐“'.$s.'”的搜索结果</strong></div>';
			$html.='<ol>';
			foreach ($list as $single){
				$html.= $this->list_template('xm', $single['song_name'], $single['song_id'], 'song', $single['album_name'], $single['album_id'], 'album', $single['artist_name'], $single['artist_id'], 'artist');
				#$html.= '<audio src="'.$single['listen_file'].'" preload="metadata" controls ></audio>';
			}
			$html.= '</ol>';
		} else {
			$html = '<div><strong>虾米音乐未搜索到结果！</strong></div>';
		}
		return $html;
	}

	private function search_ne($s, $limit=10){
		$url = 'http://music.163.com/api/search/get/';
		$post = array(
			's' => $s,
			'limit' => $limit,
			'type' => 1
		);
		$result = json_decode($this->curl_http($url, 0, self::$_REFERER_NETEASE, 0, http_build_query($post)), true);
		if (isset($result['result']['songs'])){
			$list = $result['result']['songs'];
			$html = '<div><strong>网易云音乐“'.$s.'”的搜索结果</strong></div>';
			$html.='<ol>';
			foreach ($list as $single){
				$artist = $single['artists'][0];
				$html.= $this->list_template('ne', $single['name'], $single['id'], 'song', $single['album']['name'], $single['album']['id'], 'album', $artist['name'], $artist['id'], 'artist');
				#$html.= '<audio src="'.$single['songurl'].'" preload="metadata" controls ></audio>';
			}
			$html.= '</ol>';
		} else {
			$html = '<div><strong>网易云音乐未搜索到结果！</strong></div>';
		}
		return $html;
	}
	private function _a($name, $id, $tag, $web="xm"){
		if(!is_string($name)) return;
		$_a = "";
		if ($web=="xm") $_a = '<a href="javascript:void(0);" onclick="setinput(\'xiami.com/'.$tag.'/'.$id.'\')">'.$name.'</a>';
		if ($web=="qq") $_a = '<a href="javascript:void(0);" onclick="setinput(\'y.qq.com/'.$tag.'/'.$id.'.html\')">'.$name.'</a>';
		if ($web=="ne") $_a = '<a href="javascript:void(0);" onclick="setinput(\'music.163.com/'.$tag.'?id='.$id.'\')">'.$name.'</a>';
		return $_a;
	}

	private function list_template($web, $song, $song_id, $song_tag, $album=null, $album_id=null, $album_tag=null, $artist=null, $artist_id=null, $artist_tag=null){

		$html = '<li>'.$this->_a($song, $song_id, $song_tag, $web);
		if (is_array($artist)){
			foreach ($artist as $art){
				
			}
		} else {
		$html.= $artist ? ' - '.$this->_a($artist, $artist_id, $artist_tag, $web) : '';
		}
		$html.= $album ? ' - 《'.$this->_a($album, $album_id, $album_tag, $web).'》' : '';
		$html.= '</li>';
		return $html;
	}

	// 虾米歌曲链接转换代码
	private function xiami_location($str){
		try{
			$a1=(int)$str{0};
			$a2=substr($str, 1);
			$a3=floor(strlen($a2) / $a1);
			$a4=strlen($a2) % $a1;
			$a5=array();
			$a6=0;
			$a7='';
			for(;$a6 < $a4; ++$a6){
				$a5[$a6]=substr($a2, ($a3 + 1) * $a6, ($a3 + 1));
			}
			for(;$a6 < $a1; ++$a6){
				$a5[$a6]=substr($a2, $a3 * ($a6 - $a4) + ($a3 + 1) * $a4, $a3);
			}
			for($i=0, $a5_0_length=strlen($a5[0]); $i < $a5_0_length; ++$i){
				for($j=0, $a5_length=count($a5); $j < $a5_length; ++$j){
					if (isset($a5[$j]{$i})) $a7.=$a5[$j]{$i};
				}
			}
			$a7=str_replace('^', '0', urldecode($a7));
			return $a7;
		} catch(Exception $e){
			return false;
		}
	}

	// 网易云音乐歌曲ID加密代码
	private function encrypted_id($dfsid){
		$key = '3go8&$8*3*3h0k(2)2';
		$key_len = strlen($key);
		for($i = 0; $i < strlen($dfsid); $i++){
			$dfsid[$i] = $dfsid[$i] ^ $key[$i % $key_len];
		}
		$raw_code = base64_encode(md5($dfsid, true));
		$code = str_replace(array('/', '+'), array('_', '-'), $raw_code);
		return $code;
	}

	// 网易云音乐新API
	private function netease_new_api($song_id, $bit_rate=320000){
		$url = 'http://music.163.com/weapi/song/enhance/player/url?csrf_token=';
		$data = "{'ids': [$song_id], 'br': $bit_rate, 'csrf_token': ''}";
		$data = $this->encrypted_request($data);
		$result = json_decode($this->curl_http($url, 0, self::$_REFERER_NETEASE, 0, http_build_query($data)), true);
		if (isset($result['data'][0])) return $result['data'][0];
		return false;
	}

	// 网易云音乐 weapi 加密数据
	public function encrypted_request($data){
		$secKey = $this->randString(16);
		$encText = $this->aesEncrypt( $this->aesEncrypt($data, self::$_NONCE), $secKey );
		$pow = $this->bchexdec( bin2hex( strrev($secKey) ) );
		$encKeyMod = bcpowmod($pow, self::$_PUBKEY, self::$_MODULUS);
		$encSecKey = $this->bcdechex($encKeyMod);
		$data = array(
			'params' => $encText,
			'encSecKey' => $encSecKey
		);
		return $data;
	}

	// 生成16位随机字符串
	private function randString($length){
		$chars = 'abcdef0123456789';
		$result = '';
		$max = strlen($chars) - 1;
		for ($i = 0; $i < $length; $i++){
			$result .= $chars[rand(0, $max)];
		}
		return $result;
	}

	// AES 证书加密
	private function aesEncrypt($data, $secKey){
		if (function_exists('openssl_encrypt')) {
			$cip = openssl_encrypt($data, 'aes-128-cbc', pack('H*', bin2hex($secKey)), OPENSSL_RAW_DATA, "0102030405060708");
		} else {
			$pad = 16 - strlen($data) % 16;
			$data = $data . str_repeat(chr($pad), $pad);
			$cip = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $secKey, $data, MCRYPT_MODE_CBC, "0102030405060708");
		}
		$cip = base64_encode($cip);
		return $cip;
	}

	private function bcdechex($dec) {
		$hex = '';
		do {
			$last = bcmod($dec, 16);
			$hex = dechex($last).$hex;
			$dec = bcdiv(bcsub($dec, $last), 16);
		} while($dec>0);
		return $hex;
	}

	private function bchexdec($hex) {
		if(strlen($hex) == 1) {
			return hexdec($hex);
		} else {
			$remain = substr($hex, 0, -1);
			$last = substr($hex, -1);
			return bcadd(bcmul(16, self::bchexdec($remain)), hexdec($last));
		}
	}

}


$get_music = new Get_Music;

// 返回Ajax请求
if (isset($_GET["socool"])):
$data = array(
	'status' => 0,
	'info' => '请输入正确网址！'
);

if (isset($_POST["url"]) && $_POST["url"]){
	$url = $_POST["url"];
	if (preg_match('#/song/playlist/id/1/type/9#i', $url)){
		$sid = 1;
		$type = 9;
		$data = array_merge($data, $get_music->get_xiami($sid, $type));
	}
	elseif (preg_match('#/(demo/\w+)(\?*|)#i', $url, $matches)){
		$sid = $matches[1];
		$type = 0;
		$data = array_merge($data, $get_music->get_xiami($sid, $type));
	}
	elseif (preg_match('/xiami.com/i', $url)){
		if (preg_match('#/(song/\w+)(\?*|)#i', $url, $matches)){
			$sid = $matches[1];
			$type = 0;
		}
		elseif (preg_match('#/(album/\w+)(\?*|)#i', $url, $matches)){
			$sid = $matches[1];
			$type = 1;
		}
		elseif (preg_match('#/(artist/\w+)(\?*|)#i', $url, $matches)){
			$sid = $matches[1];
			$type = 2;
		}
		elseif (preg_match('#/(collect/\w+)(\?*|)#i', $url, $matches)){
			$sid = $matches[1];
			$type = 3;
		}
		$data = array_merge($data, $get_music->get_xiami($sid, $type));
	}
	elseif (preg_match('/y.qq.com/i', $url)){
		$data['info'] = "(>_<)  QQ音乐解析暂不开源";
	}
	elseif (preg_match('/music.163.com/i', $url) || preg_match('/igame.163.com/i', $url)){
		if (preg_match('#/(\w+\?id=\d+)#i', $url, $matches)){
			$data = array_merge($data, $get_music->get_netease($matches[1]));
		}
	}
	elseif (preg_match('/==(.*)/i', $url, $matches)){
		$data = array_merge($data, $get_music->search($matches[1]));
	}
}
die(json_encode($data));
endif;

// 下载虾米音乐歌词
if (isset($_GET["xmlyric"]) && $_GET["xmlyric"]):
	$url = $_GET["xmlyric"];
	// 获取歌词
	$lyric = $get_music->curl_http($url, 0);
	if ($lyric){
		$title = isset($_GET["title"]) && $_GET["title"] ? $_GET["title"] : $song_id;
		// 下载歌词
		header( "Content-type: text/plain" );
		header( "Content-disposition: attachment; filename=$title.lrc" );
		header( "Content-length: ".strlen($lyric) );
		die($lyric);
	} else {
		die("抱歉！未能匹配到歌词！");
	}
endif;

// 下载网易云音乐歌词
if (isset($_GET["nelyric"]) && $_GET["nelyric"]):
	$song_id = $_GET["nelyric"];
	// 获取歌词
	$curl_lyric = $get_music->curl_http('http://music.163.com/api/song/lyric/?id='.$song_id.'&lv=-1', 0);
	$curl_lyric = json_decode($curl_lyric, true);
	if (isset($curl_lyric["lrc"])){
		$lyric = $curl_lyric["lrc"]["lyric"];
		$title = isset($_GET["title"]) && $_GET["title"] ? $_GET["title"] : $song_id;
		// 下载歌词
		header( "Content-type: text/plain" );
		header( "Content-disposition: attachment; filename=$title.lrc" );
		header( "Content-length: ".strlen($lyric) );
		die($lyric);
	} else {
		die("抱歉！未能匹配到歌词！");
	}
endif;

if (isset($_GET["help"])):
echo '<ol><li><p><strong>支持链接，加粗的是关键字：</strong></p><p><strong>虾米网：</strong></p><ul><li>单曲：<a href="javascript:void(0);" onclick="setinput(\'xiami.com/song/2085857\')">http://www.<strong>xiami.com/song/2085857</strong></a></li><li>艺人：<a href="javascript:void(0);" onclick="setinput(\'xiami.com/artist/23503\')">http://www.<strong>xiami.com/artist/23503</strong></a></li><li>专辑：<a href="javascript:void(0);" onclick="setinput(\'xiami.com/album/168931\')">http://www.<strong>xiami.com/album/168931</strong></a></li><li>精选集：<a href="javascript:void(0);" onclick="setinput(\'xiami.com/collect/42563832\')">http://www.<strong>xiami.com/collect/42563832</strong></a></li><li>DEMO：<a href="javascript:void(0);" onclick="setinput(\'/demo/1775392054\')">http://i.xiami.com/zhangchao<strong>/demo/1775392054</strong></a></li><li>今日歌单（需要cookie）：<a href="javascript:void(0);" onclick="setinput(\'/song/playlist/id/1/type/9\')">http://www.xiami.com/play?ids=<strong>/song/playlist/id/1/type/9</strong></a></li></ul><p><strong>QQ音乐：</strong></p><ul><li>单曲：<a href="javascript:void(0);" onclick="setinput(\'y.qq.com/song/004ZTgPR3p7ftT.html\')">https://<strong>y.qq.com</strong>/n/yqq<strong>/song/004ZTgPR3p7ftT.html</strong></a></li><li>艺人：<a href="javascript:void(0);" onclick="setinput(\'y.qq.com/singer/000GDDuQ3sGQiT.html\')">https://<strong>y.qq.com</strong>/n/yqq<strong>/singer/000GDDuQ3sGQiT.html</strong></a></li><li>专辑：<a href="javascript:void(0);" onclick="setinput(\'y.qq.com/album/000Nkr7111lq0q.html\')">https://<strong>y.qq.com</strong>/n/yqq<strong>/album/000Nkr7111lq0q.html</strong></a></li><li>精选集：<a href="javascript:void(0);" onclick="setinput(\'y.qq.com/playlist/9666360.html\')">https://<strong>y.qq.com</strong>/n/yqq<strong>/playlist/9666360.html</strong></a></li></ul><p><strong>网易云音乐：</strong></p><ul><li>单曲：<a href="javascript:void(0);" onclick="setinput(\'music.163.com/song?id=190449\')">http://<strong>music.163.com/#/song?id=190449</strong></a></li><li>艺人：<a href="javascript:void(0);" onclick="setinput(\'music.163.com/artist?id=6452\')">http://<strong>music.163.com/#/artist?id=6452</strong></a></li><li>专辑：<a href="javascript:void(0);" onclick="setinput(\'music.163.com/album?id=27483\')">http://<strong>music.163.com/#/album?id=27483</strong></a></li><li>精选集：<a href="javascript:void(0);" onclick="setinput(\'music.163.com/playlist?id=691394551\')">http://<strong>music.163.com/#/playlist?id=691394551</strong></a></li></ul></li><li><p><strong>高级选项：</strong></p><ul><li>此功能用于解析虾米音乐今日歌单（Cookie），或者国外服务器解析失败时使用（代理IP）。</li><li>在服务器使用：首先登录虾米，登录后获取 Cookie: member_auth（<a href="assets/Chrome-Cookie.gif">获取方法</a>），打开服务器虾米解析页面，点开高级选项，左边输入 member_auth，（可选，右边输入代理IP）进行解析。</li><li>在本地使用：本地搭建PHP环境（如：<a href="http://www.phpstudy.net/" target=_blank>phpStudy</a>），打开虾米解析页面，点开高级选项，输入 member_auth 并进行解析。</li><li>使用HTTP代理：如果服务器不在国内有可能会解析失败，在高级选项右边输入国内代理IP进行解析。</li></ul></li><li><p><strong>注意事项：</strong></p><ul><li>本工具有关虾米、网易云音乐的解析代码开源，QQ音乐因特殊原因不予开源，本人以人格保证此工具不会收集用户信息，若不放心可以不输入 Cookie，或者本地搭建使用。</li><li>虾米今日歌单需要用户自己的 Cookie: member_auth 才能解析到，否则解析默认歌单。</li><li>部分音乐没有提供高品质的，如DEMO，网易云音乐版权歌曲等。虾米部分正版歌曲只能解析到试听30秒版本。</li><li>本工具只用于学习交流，下载试听音乐，禁止用于商业用途！最后，请支持正版！</li></ul></li><li><p><strong>GitHub项目地址：</strong><a href="https://github.com/xyuanmu/parsexiami" target=_blank>https://github.com/xyuanmu/parsexiami</a></p></li></ol>';
; else :
?>
<!DOCTYPE html>
<html>
<head>
<title>解析虾米、QQ音乐、网易云音乐 320Kbps 高品质音乐地址</title>
<meta charset="UTF-8">
<meta name="keywords" content="虾米,虾米网,虾米音乐,QQ音乐,网易云音乐,音乐,MP3,解析,下载"/>
<meta name="description" content="用于解析虾米,QQ音乐,网易云音乐高品质音乐地址的网页小工具, 解析虾米音乐推荐歌单需要 Cookie 才能获取本人的歌单"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
<link rel="stylesheet" type="text/css" href="assets/style.css"/>
<script type="text/javascript" src="assets/xiami.js"></script>
</head>
<body>
<div id="bg"></div>
<div id="page">
	<h1>解析虾米、QQ音乐、网易云音乐</h1>
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
	<button id="overlay" class="wait"><div id="close" title="关闭">×</div><span id="wait"></span></button>
	<audio id="audio" src="" preload="metadata" controls style="display:none"></audio>
	<pre id="info"></pre>
<div id="cloud-tie-wrapper" class="cloud-tie-wrapper"></div>
</div>
<div id="help"></div>
<div id="footer">
	<span class="item item-left"><a id="show-help" href="javascript:void(0)">查看帮助</a></span><span class="item item-center"><a href="http://yuanmu.mzzhost.com/">yuanmu.mzzhost.com</a> © All Rights Reserved</span><span class="item item-right"><a href="javascript:void(0)" onclick="$('#advanced').slideToggle(200)">高级选项</a></span>
</div>
</body>
</html>
<?php endif;