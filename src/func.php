<?

function sendMethod( $method, $parm ){
	global $server;
	$curl = curl_init();
	curl_setopt_array( $curl, [
		CURLOPT_URL => $server.'/'.$method,
		CURLOPT_TIMEOUT  => 1,
		CURLOPT_CONNECTTIMEOUT => 1,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query( $parm )
	] );
	$r = curl_exec( $curl );
	curl_close( $curl );
	return $r;
}

function sendGet( $url, $headers=[] ){
	$curl = curl_init();
	$headers[] = 'User-Agent: HorneyBot';
	curl_setopt_array( $curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => true
	] );
	$r = curl_exec( $curl );
	curl_close( $curl );
	return $r;
}

function sendPost( $url, $parm, $headers=[] ){
	$curl = curl_init();
	$headers[] = 'User-Agent: HorneyBot';
	curl_setopt_array( $curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_POSTFIELDS => http_build_query( $parm )
	] );
	$r = curl_exec( $curl );
	curl_close( $curl );
	return $r;
}

function keyboard( $text,$number=NULL,$cb=NULL, $url=NULL ) {
	global $keyboard;
	if( !$cb ){
		$keyboard['keyboard'][$number][] = ['text' => $text];
	}else{
		if( !isset( $url ) ) $keyboard['inline_keyboard'][$number][] = ['text' => $text, 'callback_data' => json_encode( $cb )];
		if( isset( $url ) ) $keyboard['inline_keyboard'][$number][] = ['text' => $text, 'url' => $url];
	}
	return $keyboard;
}

function setData($name, $value=NULL){
	global $_db, $userData, $user;
	if(!isset($value)) unset($userData[$name]);
	else $userData[$name] = $value;
	mysqli_query($_db, ' UPDATE `user` SET `data` = "' . base64_encode(json_encode($userData)) . '" WHERE `id` = "' . $user['id'] . '" ');
}

function setSetting($name, $value=NULL){
	global $_db, $settings, $user;
	if(!isset($value)) unset($settings[$name]);
	else $settings[$name] = $value;
	mysqli_query($_db, ' UPDATE `user` SET `settings` = "' . base64_encode(json_encode($settings)) . '" WHERE `id` = "' . $user['id'] . '" ');
}

function getVideo ($url, $ep){

	$html = str_get_html( file_get_contents( 'https://play.shikimori.org/' . $url . '/video_online/' . $ep ) );
	
	foreach($html->find('div[data-kind="all"]') as $a) {
		foreach($a->find('a') as $b) {
	
			$kind = str_replace(" ", "", $b->find('span.video-kind')[0]->plaintext);
			$hosting = str_replace(" ", "", $b->find('span.video-hosting')[0]->plaintext);
			$author = $b->find('span.video-author')[0]->plaintext;
			$id = end( explode("/", $b->href) );
	
			if( isset($kind) ){
				$video[] = [
					'kind' => $kind,
					'hosting' => $hosting,
					'author' => $author,
					'id' => $id,
				];
				if($lastKind != $kind){
					$lastKind = $kind;
					$play .= "\n<b>$kind:</b>\n";
				}
				$play .= "<a href=\"https://t.me/HorneyBot?start=w_" . $anime['id'] . "_$id\">[$hosting] $author</a>\n";
			}
		}
	}
	return $play;
}

function animeInfo($id){
	global $_db, $tp_token;

	$animeInfo = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `anime` WHERE `id` = "' . $id . '" '  )  );

	if( !isset($animeInfo['id']) ){
		animeUpdate($id);
		$animeInfo = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `anime` WHERE `id` = "' . $id . '" '  )  );
	}

	if( !isset($animeInfo['id']) ){
		return false;
	}else{
		$name = "📺 <b>" . $animeInfo['name'] . " / " . $animeInfo['russian'] . "</b>\n";
		if( $animeInfo['mal'] == 0 ) $animeInfo['mal'] = $animeInfo['id'];
		$info = "ℹ️<b> Информация:</b> " . $animeInfo['telegraph'] . "\n▶️ <b>Шикимори:</b> shikimori.org/animes/" . $animeInfo['id'] . "\n▶️ <b>MAL:</b> myanimelist.net/anime/" . $animeInfo['mal'];
		$result = $name.$info;
		return $result;
	}
}

function animeUpdate($id){
	global $_db, $tp_token, $shikimori_video_token, $keyboard;

	$animeInfo = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `anime` WHERE `id` = "' . $id . '" '  )  );

	if( ($animeInfo['updated']+300) < time() OR !isset($animeInfo) ){

		$anime = json_decode( sendGet ( 'https://shikimori.org/api/animes/' . $id ), true );

		if( isset($anime['id']) ){

			$updated_at = strtotime( $anime['updated_at'] );

			foreach ($anime['genres'] as &$g) $genre[] = mb_strtolower($g['russian'], 'UTF-8');
			$genre = implode(', ', $genre );

			$type = ['tv' => 'TV Сериал', 'movie' => 'Фильм', 'ova' => 'OVA', 'ona' => 'ONA', 'special' => 'Спешл', 'music' => 'Клип' ];

			$months = [ 1 => 'января' , 'февраля' , 'марта' , 'апреля' , 'мая' , 'июня' , 'июля' , 'августа' , 'сентября' , 'октября' , 'ноября' , 'декабря' ];
			$status = ['anons' => 'анонс', 'ongoing' => 'онгоинг с', 'released' => 'вышло'];
			$aired_on = strtotime( $anime['aired_on'] );
			$released_on = strtotime( $anime['released_on'] );
			if( $anime['status'] == 'released' ){
				$time = ' с ' . date('j ' . $months[date( 'n', $aired_on )] . ' Y г.', $aired_on) . ' по '  . date('j ' . $months[date( 'n', $released_on )] . ' Y г.', $released_on);
				$episodes = $anime['episodes'].'';
			}else{
				$time = ' ' . date('j ' . $months[date( 'n', $aired_on )] . ' Y г.', $aired_on);
				$episodes = $anime['episodes_aired'].'/'.$anime['episodes'];
			}

			if( $anime['duration'] < 60 ) $content_duration = date( 'i минут', mktime( 0, $anime['duration'] ) );
			else $content_duration = date( 'H час. i мин.', mktime( 0, $anime['duration'] ) );
			
			$content = [
				[ 'tag' => 'figure', 'children' => [
					['tag' => 'img', 'attrs' => ['src' => 'https://shikimori.org' . $anime['image']['original'] ] ],
					['tag' => 'figcaption', 'children' => [ '' ] ],]
				],
				[ 'tag' => 'h3', 'children' => [ [ 'tag' => 'a', 'attrs' => [ 'href' => "https://t.me/HorneyBot/?start=a_".$anime['id'] ], 'children' => [ $anime['russian'] ] ] ] ],
				[ 'tag' => 'b', 'children' => [ 'Тип: ' ] ], $type[ $anime['kind'] ], [ 'tag' => 'br' ],
				[ 'tag' => 'b', 'children' => [ 'Эпизоды: ' ] ], $episodes, [ 'tag' => 'br' ],
				[ 'tag' => 'b', 'children' => [ 'Длительность эпизода: ' ] ], $content_duration, [ 'tag' => 'br' ],
				[ 'tag' => 'b', 'children' => [ 'Статус: ' ] ], $status[ $anime['status'] ], $time, [ 'tag' => 'br' ],
				[ 'tag' => 'b', 'children' => [ 'Жанры: ' ] ], $genre, [ 'tag' => 'br' ],
				[ 'tag' => 'b', 'children' => [ 'Рейтинг: ' ] ], ''.$anime['score'], [ 'tag' => 'br' ],
				[ 'tag' => 'h3', 'children' => ['Описание'] ],
				strip_tags( $anime['description_html']),
				[ 'tag' => 'br' ],                    
				[ 'tag' => 'h3', 'children' => ['Видео'] ],
			];

			$video = json_decode( sendGet ( 'https://shikimori.org/api/animes/' . $id . '/videos' ), true );
			foreach ($video as &$v){
				$content[] = [ 'tag' => 'a', 'attrs' => ['href' => $v['url'] ], 'children' => [
					[ 'tag' => 'b', 'children' => [ '['.$v['kind'].'] ' ] ],
					[ 'tag' => 'b', 'children' => [ $v['hosting'] . ' ' ] ],
					$v['name'], ['tag' => 'br' ] ]
				];
			}

			$play = json_decode( sendGet ( 'https://shikimori.org/api/animes/' . $id . '/anime_videos?video_token='.$shikimori_video_token ), true );

			$sProv = [ 'vk.com', 'smotret-anime.ru' ];
			$statusNum = [ 'anons' => 0, 'ongoing' => 1, 'released' => 2 ];
			$i = 0;
			foreach ($play as &$value){
				if ( $value['state'] == 'working' ){
					$maxEpisode = $value['episode'];
					$pattern = '#(?<=\.|/|\s)[a-zA-Z0-9-]{2,61}\.[a-zA-Z]{2,3}(?=\s|/)#i';
					preg_match_all($pattern,$value['url'],$domain);
					$domain = $domain[0][0];
					$prov[$value['episode']][$value['kind']][$domain][$value['author_name']] = [ 'id' => $value['id'], 'url' => $value['url'], 'language' => $value['language'], 'quality' => $value['quality'] ];
					if( $domain == 'smotret-anime.ru' ){
						$explode = explode( '/', $value['url'] );
						$pid = end( $explode );
						$prov[$value['episode']][$value['kind']][$domain][$value['author_name']]['dl'] = 'http://smotret-anime.ru/translations/mp4/' . $pid;
						if( $value['kind'] == 'subtitles' ){
							$sub = 'http://smotret-anime.ru/translations/ass/' . $pid . '?download=1';
							$prov[$value['episode']][$value['kind']][$domain][$value['author_name']]['sub'] = $sub;
						}
					}
					$i++;
				}
			}

			$ep = [ 'fandub' => 0, 'subtitles' => 0, 'raw' => 0 ];

			foreach ($prov as $k => $v){
				foreach ($v as $c => $d){
					foreach (array_reverse($sProv) as $a => $b) {
						if(isset($prov[$k][$c][$b])){
							$n = $prov[$k][$c][$b];
							unset($prov[$k][$c][$b]);
							$prov[$k][$c] = [$b => $n]+$prov[$k][$c];
						}
						$ep[$c] = $k;
					}
				}
			}

			$episode = count($prov);

			$prov = json_encode($prov);
			mkdir( realpath(__DIR__ . "/anime/")."/$id", 0777 );
			file_put_contents(realpath(__DIR__ . "/anime/")."/$id/video.json", $prov);
				
			if( $animeInfo['id'] > 0 ){
				$path = end(explode( '/', $animeInfo['telegraph'] ));
				if( strlen($path) == 0 ) json_decode( sendPost ( 'https://api.telegra.ph/createPage', [ 'title' => $anime['name'], 'author_name' => '@HorneyBot', 'author_url' => 'https://t.me/HorneyBot', 'content' => json_encode($content), 'access_token' => $tp_token ] ) , true );
				else json_decode( sendPost ( 'https://api.telegra.ph/editPage/' . $path, [ 'title' => $anime['name'], 'author_name' => '@HorneyBot', 'author_url' => 'https://t.me/HorneyBot', 'content' => json_encode($content), 'access_token' => $tp_token ] ) , true );
				
				if( $episode > 0 && $ep['fandub'] > 0 && $ep['subtitles'] > 0 && $ep['raw'] > 0 ){
					mysqli_query ( $_db, ' UPDATE `anime` SET `mal` = "' . $anime['myanimelist_id'] . '", `name` = "' . mysqli_real_escape_string($_db, $anime['name']) . '", `russian` = "' . mysqli_real_escape_string($_db, $anime['russian']) . '", `telegraph` = "' . $animeInfo['telegraph'] . '", `status` = "' . $statusNum[$anime['status']] . '", `episode` = "' . $episode . '", `episode_fandub` = "' . $ep['fandub'] . '", `episode_subtitles` = "' . $ep['subtitles'] . '", `episode_raw` = "' . $ep['raw'] . '", `updated` = "' . time() . '", `updated_at` = "' . $updated_at . '" WHERE `id` = "' . $id . '" ' );

					$notification_text = [
						'fandub' => "Доступна озвучка <b>" . $ep['fandub'] . " серия {name}</b> для просмотра",
						'subtitles' => "Доступны субтитры <b>" . $ep['subtitles'] . " серия {name}</b> для просмотра",
						'raw' => "Доступен оригинал <b>" . $ep['raw'] . " серия {name}</b> для просмотра",
					];

					if( $ep['fandub'] > 0 && $ep['fandub'] > $animeInfo['episode_fandub']) $notification['fandub'] = 'fandub';
					if( $ep['subtitles'] > 0 && $ep['subtitles'] > $animeInfo['episode_subtitles']) $notification['subtitles'] = 'subtitles';
					if( $ep['raw'] > 0 && $ep['raw'] > $animeInfo['episode_raw']) $notification['raw'] = 'raw';
					
					if( isset($notification) ){
						$favorite = mysqli_query($_db, ' SELECT * FROM `favorite` WHERE `anime` = "' . $id . '" ' );
						while ($row = mysqli_fetch_array($favorite)) {
							$fUser = mysqli_fetch_array(mysqli_query($_db, ' SELECT `settings` FROM `user` WHERE `id` = "' . $row['user'] . '" ' ));
							$fSettings = json_decode(base64_decode($fUser['settings']),true);
							if( $fSettings['notification']['on'] !== 1 ){
								foreach ($notification as $k => $value) {
									if( !empty( $anime['russian'] ) && $fSettings['name_lng'] == 1 ) $name = $anime['russian'];
									else $name = $anime['name'];
									$text = str_replace('{name}', $name, $notification_text[$k]);
									keyboard( '▶️ Смотреть', 0, [ 't' => '2', 'i' => $id ] );
									if( $fSettings['notification']['kind'][$k] !== 1 ) sendMethod ( 'sendMessage', ['chat_id' => $row['user'], 'text' => $text, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML' ] );
									$keyboard = '';
								}
							}
						}
					}
				}
			}else{
				$telegraph = json_decode( sendPost ( 'https://api.telegra.ph/createPage', [ 'title' => $anime['name'], 'author_name' => '@HorneyBot', 'author_url' => 'https://t.me/HorneyBot', 'content' => json_encode($content), 'access_token' => $tp_token ] ) , true )['result']['url'];
				mysqli_query ( $_db, ' INSERT INTO `anime` SET `id` = "' . $id . '", `mal` = "' . $anime['myanimelist_id'] . '", `name` = "' . mysqli_real_escape_string($_db, $anime['name']) . '", `russian` = "' . mysqli_real_escape_string($_db, $anime['russian']) . '", `telegraph` = "' . $telegraph . '", `status` = "' . $statusNum[$anime['status']] . '", `episode` = "' . $episode . '", `episode_fandub` = "' . $ep['fandub'] . '", `episode_subtitles` = "' . $ep['subtitles'] . '", `episode_raw` = "' . $ep['raw'] . '", `updated` = "' . time() . '", `updated_at` = "' . $updated_at . '", `time` = "' . time() . '" ' );
			}
		}
	}

	sendMethod ( 'sendMessage', ['chat_id' => 66478514, 'text' => mysqli_error($_db), 'parse_mode' => 'HTML' ] );
}