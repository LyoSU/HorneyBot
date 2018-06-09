<?

set_time_limit( 20 );

require( __DIR__ . '/dbConnect.php'  );
require( __DIR__ . '/config.php'  );

$input = file_get_contents( 'php://input' );
$input = json_decode( $input, true );

if( isset( $input['message'] ) ){
    $chat_id = $input['message']['chat']['id'];
    $user_id = $input['message']['from']['id'];
    $login = $input['message']['from']['first_name'];
    if( isset($input['message']['from']['last_name']) ) $login .= ' ' . $input['message']['from']['last_name'];
    $username = $input['message']['from']['username'];
    $language = $input['message']['from']['language_code'];
    $message_id = $input['message']['message_id'];
    $text = $input['message']['text'];
}elseif( isset( $input['callback_query'] ) ){
    $chat_id = $input['message']['chat']['id'];
    $user_id = $input['callback_query']['from']['id'];
    $text = $input['callback_query']['message']['text'];
    $login = $input['callback_query']['from']['first_name'];
    if( isset($input['callback_query']['from']['last_name']) ) $login .= ' ' . $input['message']['from']['last_name'];
    $username = $input['callback_query']['from']['username'];
    $language = $input['callback_query']['from']['language_code'];
    $message_id = $input['callback_query']['message']['message_id'];
    $callback_query_id = $input['callback_query']['id'];
    $query = json_decode( $input['callback_query']['data'], true );
}

$login = mysqli_real_escape_string( $_db, $login );

$user = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `user` WHERE `id` = "' . $user_id . '" '  )  );

if( !isset( $user ) && isset( $user_id ) ){
    mysqli_query ( $_db, 'INSERT INTO `user` SET `id` = "' . $user_id . '", `login` = "' . $login . '", `username` = "' . $username . '", `time_reg` = "' . time( ) . '" ' );
    $user = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `user` WHERE `id` = "' . $user_id . '" '  )  );
}
$userData = json_decode(base64_decode($user['data']),true);
$settings = json_decode(base64_decode($user['settings']),true);

require( __DIR__ . '/func.php'  );

$filter_status = [ 1 => 'anons', 2 => 'ongoing', 3 => 'released' ];
$filter_order = [ 1 => 'ranked', 2 => 'popularity', 3 => 'name', 3 => 'aired_on' ];
$filter_kind = [ 1 => 'tv', 2 => 'movie' ];

function mainMenu(){
    global $keyboard;
    keyboard( '🔎 Поиск', 0 );
    keyboard( '⭐️ Избранные', 0 );
    keyboard( '⚙️ Настройки', 1 );
    $keyboard['keyboard'] = array_values($keyboard['keyboard']);
    $keyboard['resize_keyboard'] = true;
}

function filterMenu(){
    global $keyboard, $settings;

    $filter_status_name = [ 1 => 'анонсировано', 2 => 'сейчас выходит', 3 => 'вышедшее' ];
    $filter_order_name = [ 1 => 'по рейтингу', 2 => 'по популярности', 3 => 'по алфавиту', 3 => 'по дате выхода' ];
    $filter_kind_name = [ 1 => 'TV Сериал', 2 => 'фильм' ];

    if( $settings['filter']['menu'] == 0 OR !isset($settings['filter']['menu']) ){
        keyboard( '🔽 Включить фильтр 🔽', 0, [ 't' => '3', 'm' => 1 ] );
    }else{

        keyboard( '🔼 Выключить фильтр 🔼', 0, [ 't' => '3', 'm' => 0 ] );

        switch ($settings['filter']['menu']) {
            case 1:
                keyboard( 'Статус' . ((isset($settings['filter']['status']))?': '.$filter_status_name[$settings['filter']['status']]:''), 1, [ 't' => '3', 'm' => 2 ] );
                keyboard( 'Сортировка' . ((isset($settings['filter']['order']))?': '.$filter_order_name[$settings['filter']['order']]:''), 1, [ 't' => '3', 'm' => 3 ] );
                keyboard( 'Тип' . ((isset($settings['filter']['kind']))?': '.$filter_kind_name[$settings['filter']['kind']]:''), 2, [ 't' => '3', 'm' => 4 ] );
                keyboard( 'Жанр', 2, [ 't' => '3', 'm' => 5 ] );
            break;

            case 2:
                keyboard( 'Анонсировано', 11, [ 't' => '3', 'm' => 1, 'n' => 1, 's' => 1 ] );
                keyboard( 'Сейчас выходит', 11, [ 't' => '3', 'm' => 1, 'n' => 1, 's' => 2 ] );
                keyboard( 'Вышло', 12, [ 't' => '3', 'n' => 1, 's' => 3 ] );
                keyboard( 'Не важно', 90, [ 't' => '3', 'm' => 1, 'n' => 1, 's' => 0 ] );
            break;

            case 3:
                keyboard( 'По рейтингу', 11, [ 't' => '3', 'm' => 1, 'n' => 2, 's' => 1 ] );
                keyboard( 'По популярности', 11, [ 't' => '3', 'm' => 1, 'n' => 2, 's' => 2 ] );
                keyboard( 'По алфавиту', 12, [ 't' => '3', 'm' => 1, 'n' => 2, 's' => 3 ] );
                keyboard( 'По дате выхода', 12, [ 't' => '3', 'm' => 1, 'n' => 2, 's' => 4 ] );
            break;
            
            case 4:
                keyboard( 'TV Сериал', 11, [ 't' => '3', 'm' => 1, 'n' => 3, 's' => 1 ] );
                keyboard( 'Фильм', 11, [ 't' => '3', 'm' => 1, 'n' => 3, 's' => 2 ] );
                keyboard( 'Не важно', 90, [ 't' => '3', 'm' => 1, 'n' => 3, 's' => 0 ] );
            break;
        }
    }

    $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
}

function settingsMenu(){
    global $keyboard, $settings;

    keyboard( ( ($settings['notification']['on'] == 1)?"🔕":"🔔" )." Уведомления", 0, [ 't' => 4, 's' => 1 ] );

    keyboard( ( ($settings['notification']['kind']['fandub'] == 1)?"☑️":"✅" )." Озвучка", 1, [ 't' => 4, 's' => 2, 'k' => 1 ] );
    keyboard( ( ($settings['notification']['kind']['subtitles'] == 1)?"☑️":"✅" )." Субтитры", 1, [ 't' => 4, 's' => 2, 'k' => 2 ] );
    keyboard( ( ($settings['notification']['kind']['raw'] == 1)?"☑️":"✅" )." Оригинал", 1, [ 't' => 4, 's' => 2, 'k' => 3 ] );

    keyboard( ( ($settings['notification']['video'] == 1)?"☑️":"✅" )." Появление видео на канале @Horney", 2, [ 't' => 4, 's' => 3 ] );
    keyboard( "🛎 Фильтр авторов", 3, [ 't' => 4, 's' => 4, 'd' => 1 ] );

    keyboard( ( ($settings['name_lng'] == 1)?"🇷🇺":"🇯🇵" )." Язык названий", 4, [ 't' => 4, 's' => 5 ] );

    keyboard( "🔑 Авторизация shikimori", 30, [""], "http://lyo.su/shikimori.php" );

    keyboard( "🛠 Отладка", 100, [ 't' => 4, 's' => 100 ] );

    $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
}

if( isset( $input['message'] ) ){

    //sendMethod ( 'sendMessage', ['chat_id' => 66478514, 'text' => json_encode($input), 'parse_mode' => 'HTML'] );

    if( $input['message']['chat']['type'] == 'private' ){

        if( $chat_id == 166478514 ){
            $horney = json_decode( file_get_contents("horney.json" ), true );
            foreach ($horney['questions'] as $quest => $answer) {
                $atext = explode(" ", $text);
                $aquest = explode(" ", $quest);
                $result = array_diff($atext, $aquest);
                if( count($result) > 0 ) sendMethod ( 'sendMessage', ['chat_id' => 66478514, 'text' => $quest, 'parse_mode' => 'HTML'] );
            }
        }

        if( stripos($text, 'start') == 1 ){
            $start = explode(' ', $text )[1];
            if( isset($start) ){

                $parm = explode('_', $start );

                if( $parm[0] == 'a' ){
                    $id = $parm[1];
                    goto getAnime;
                }else{
                    $parm = [
                        "grant_type" => "authorization_code",
                        "client_id" => $shikimori_client_id,
                        "client_secret" => $shikimori_client_secret,
                        "code" => $start,
                        "redirect_uri" => "https://lyo.su/shikimori.php",
                    ];
                    $r = json_decode(sendPost( "https://shikimori.org/oauth/token", $parm ), true);
                    if( !empty($r['access_token']) ) $settings['shikimori'] = $r;
                    $headers = [
                        "User-Agent: HorneyBot",
                        "Authorization: Bearer ".$settings['shikimori']['access_token']
                    ];
                    $r = json_decode(sendGet( "https://shikimori.org/api/users/whoami", $headers ), true);
                    $nickname = $r['nickname'];
                    sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => "Вы успешно авторизовались как <a href=\"https://shikimori.org/$nickname\">$nickname</a>", 'parse_mode' => 'HTML'] );
                }

            }else{
                $r = sendMethod ( 'sendSticker', ['chat_id' => $user_id, 'sticker' => 'CAADAgADsgADrHkzBjh5lUFtaqHEAg'] );
                mainMenu();
                sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => "Привет, <b>$login</b>✌🏻\nЯ помогу тебе найти аниме, для этого отправь мне его название или скриншот.\nТакже я буду присылать тебе уведомления о выходе новых серий из твоего списка \"избранное\".", 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML'] );
            }
            
        }elseif( explode('_', $text )[0] == '/a' ){
            $id = explode('_', $text )[1];
            getAnime:
            $result = animeInfo( $id );
            if( $result != false ){
                $anime = json_decode( sendGet ( 'https://shikimori.org/api/animes/' . $id ), true );
                keyboard( '▶ Смотреть', 0, [ 't' => '2', 'm' => '1', 'i' => $id ] );
                keyboard( '🔽 Скачать', 0, [ 't' => '2', 'm' => '2', 'i' => $id ] );
                keyboard( '⭐️ Избранные', 1, [ 't' => '1', 'i' => $id ] );
                $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
                sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML' ] );
                exec ('php ' . __DIR__ . '/update.php ' . $id . ' > /dev/null 2>&1 &');
            }else{
                sendMethod ( 'sendSticker', ['chat_id' => $user_id, 'sticker' => 'CAADAgADtgADrHkzBm3I12LyI4uFAg'] );
            }
        }elseif( explode('_', $text )[0] == '/dl' ){
            $video_id = explode('_', $text )[1];
            if( isset($video_id) ){
                $video = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `video` WHERE `id` = "' . $video_id . '" ' ) );
                if( isset($video) ){
                    sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => "<b>Это видео уже загружено или загружается</b>", 'parse_mode' => 'HTML' ] );
                }else{
                    $video = json_decode(file_get_contents("http://smotret-anime.ru/api/translations?id=".$video_id),true)['data'];
                    mysqli_query ( $_db, 'INSERT INTO `video` SET `id` = "'.$video['id'].'", `author` =  "'.$video['authorsList'][0].'", `episode` = "'.$video['episode']['episodeInt'].'", `anime` = "'.$video['series']['myAnimeListId'].'", `file_id` = "0", `message` = "0", `time` = "' . time( ) . '" ' );
                    sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => "<b>Добавлено в очередь</b>", 'parse_mode' => 'HTML' ] );
                }
            }
        }elseif( isset($input['message']['photo']) OR isset($input['message']['video']) OR isset($input['message']['document']) ){

            $message_id = json_decode( sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => "<b>🔎 Ищу...</b>", 'parse_mode' => 'HTML'] ), true )['result']['message_id'];

            if( isset($input['message']['document']) ) $getFile = json_decode( sendMethod ( 'getFile', [ 'file_id' => $input['message']['document']['thumb']['file_id'] ] ), true )['result']['file_path'];
            elseif( isset($input['message']['video']) ) $getFile = json_decode( sendMethod ( 'getFile', [ 'file_id' => $input['message']['video']['thumb']['file_id'] ] ), true )['result']['file_path'];
            else $getFile = json_decode( sendMethod ( 'getFile', [ 'file_id' => $input['message']['photo'][0]['file_id'] ] ), true )['result']['file_path'];

            $post = [
                'image' => base64_encode(file_get_contents( "https://api.telegram.org/file/bot$tg_token/".$getFile )),
            ];

            $ch = curl_init( );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_URL, "https://whatanime.ga/api/search?token=$whatanimeToken" );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_SAFE_UPLOAD, false );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
            $r = json_decode( curl_exec( $ch ), true );
            curl_close( $ch );
            
            if( !isset($r['docs'][0]['mal_id']) ){
                sendMethod ( 'sendMessage', ['chat_id' => 66478514, 'text' => json_encode($r), 'parse_mode' => 'HTML'] );
                $result = "<b>Ошибка.</b>Попробуй повторить попытку позже\n";
            }else{
                animeUpdate($r['docs'][0]['mal_id']);
                $animeInfo = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `anime` WHERE `id` = "' . $r['docs'][0]['mal_id'] . '" '  )  );

                $result = "<b>🤔 Это похоже на:</b>\n";
                if( isset( $animeInfo['russian'] ) && $settings['name_lng'] == 1 ) $result .= $animeInfo['russian']."\n";
                else $result .= $r['docs'][0]['title_romaji']."\n";
                if( $r['docs'][0]['episode'] > 0 ) $result .= $r['docs'][0]['episode'] . " серия " . date("i:s", $r['docs'][0]['at']) . "\n";
                $result .= "<b>Точность:</b> " . round($r['docs'][0]['similarity']*100) . "%\n";
                if( $r['docs'][0]['mal_id'] > 0 ) $result .= "<b>ℹ️ Информация:</b> /a_" . $r['docs'][0]['mal_id'];
                $result .= "\nРаботает на основе whatanime.ga";
                $result .= "<a href=\"https://whatanime.ga/preview.php?season=".$r['docs'][0]['season']."&anime=".urlencode($r['docs'][0]['anime'])."&file=".urlencode($r['docs'][0]['filename'])."&t=".$r['docs'][0]['at']."&token=".$r['docs'][0]['tokenthumb']."\">&#8203;</a>";
            }

            sendMethod ( 'editMessageText', ['chat_id' => $user_id, 'message_id' => $message_id, 'text' => $result, 'parse_mode' => 'HTML'] );

        }else{
            if( $text == '⭐️ Избранные' ){

                $result = "<b>Избранные аниме:</b>\n";

                $favorite = mysqli_query($_db, ' SELECT * FROM `favorite` WHERE `user` = "' . $user_id . '" ' );
                if( mysqli_num_rows($favorite) > 0 ){

                    while ($row = mysqli_fetch_array($favorite)) {
                        $anime = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `anime` WHERE `id` = "' . $row['anime'] . '" '  )  );
                        if( !empty( $anime['russian'] ) && $settings['name_lng'] == 1 ) $name = $anime['russian'];
                        else $name = $anime['name'];
                        $result .= "📺 " . $name . " - /a_" . $anime['id'] . "\n";
                    }

                }else{
                    $result = "<b>Твой список избранных пуст😟</b>";
                }

                mainMenu();
                $r = sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML'] );

                file_put_contents("log.txt", $r.PHP_EOL, FILE_APPEND | LOCK_EX);
                
            }elseif( $text == '⚙️ Настройки' ){
                $result = "<b>Настройки</b>";
                if( $settings['dev'] == 1 ) $result .= "\n<code>".json_encode($settings, JSON_PRETTY_PRINT)."</code>";
                settingsMenu();
                sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML'] );
            }else{
                if( $text != '🔎 Поиск' ){
                    $q['search'] = $text;
                    setData( 'search_text', $text );
                }else setData( 'search_text' );
                if( $settings['filter']['status'] > 0 ) $q['status'] = $filter_status[$settings['filter']['status']];
                if( $settings['filter']['order'] > 0 ) $q['order'] = $filter_order[$settings['filter']['order']];
                if( $settings['filter']['kind'] > 0 ) $q['kind'] = $filter_kind[$settings['filter']['kind']];
                $q['limit'] = 10;
                $search = json_decode( sendGet ( "https://shikimori.org/api/animes?" . http_build_query($q) ), true );
                if( count($search) > 0 ){
                    $result = "<b>Вот что я нашла для тебя:</b>\n";
                    foreach ($search as &$anime) {
                        if( !empty( $anime['russian'] ) && $settings['name_lng'] == 1 ) $name = $anime['russian'];
                        else $name = $anime['name'];
                        $result .= "📺 " . $name . " (" . date( 'Y', strtotime( $anime['aired_on'] ) ) . ") - /a_" . $anime[id] . "\n";
                    }
                }else{
                    $result = "У меня не получилось ничего найти по твоему запросу 😞";
                }
                
                filterMenu();

                sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML'] );
            }
        }
    }elseif( $input['message']['chat']['type'] == 'supergroup' ){
        $text = mb_strtolower($text);
        if( $text == 'привет' ){
            //sendMethod ( 'sendMessage', ['chat_id' => $chat_id, 'text' => "Привет, <b>$login</b>✌🏻", 'parse_mode' => 'HTML', 'reply_to_message_id' => $message_id ] );
            sendMethod ( 'sendSticker', ['chat_id' => $chat_id, 'sticker' => 'CAADAgADsgADrHkzBjh5lUFtaqHEAg', 'reply_to_message_id' => $message_id ] );
        }
    }
}elseif( isset( $input['callback_query'] ) ){

    $answerCallbackText = '';

    switch ($query['t']) {

        case 1:

            $favorite = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `favorite` WHERE `anime` = ' . $query['i'] . ' AND `user` = ' . $user_id ) );

            if( !isset($favorite) ){
                mysqli_query ( $_db, 'INSERT INTO `favorite` SET `anime` = "' . $query['i'] . '", `user` = "' . $user_id . '", `time` = "' . time() . '" ' );
                $answerCallbackText = 'Добавлено в избранные';
            }else{
                mysqli_query ( $_db, 'DELETE FROM `favorite` WHERE `id` = ' . $favorite['id'] );
                $answerCallbackText = 'Удалено из избранных';
            }
            
        break;

        case 2:

            keyboard( '▶ Смотреть', 0, [ 't' => '2', 'm' => '1', 'i' => $query['i'] ] );
            keyboard( '🔽 Скачать', 0, [ 't' => '2', 'm' => '2', 'i' => $query['i'] ] );

            $favorite = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `favorite` WHERE `anime` = ' . $query['i'] . ' AND `user` = ' . $user_id ) );
            $anime = mysqli_fetch_assoc( mysqli_query ( $_db, 'SELECT * FROM `anime` WHERE `id` = "' . $query['i'] . '" '  )  );

            if( isset($favorite) ){
                if( !isset($query['e']) ) $query['e'] = $favorite['episode'];
                mysqli_query ( $_db, ' UPDATE `favorite` SET `episode` = "' . $query['e'] . '" WHERE `anime` = ' . $query['i'] . ' AND `user` = ' . $user_id );
            }elseif( !isset($query['e']) ) $query['e'] = 1;

            if( isset($query['k']) ) setSetting('anime_type', $query['k']);
            if( isset($settings['anime_type']) ) $type = $settings['anime_type'];
            if( !isset($type) ) $type = 0;

            $result .= "<b>📺 ".$query['e']." серия ".$anime['name']."</b>\n";

            if( $query['m'] == 1 ){
                $typeName = [ 'fandub', 'subtitles', 'raw', 'unknown' ];

                $animeFile = __DIR__ . "/anime/" . $query['i'] . "/video.json";

                if( file_exists( $animeFile ) ){
                    $prov = json_decode( file_get_contents( $animeFile ), true );

                    $play = $prov[$query['e']][$typeName[$type]];

                    if( count($play) > 0 ){
                    $result .= "\n<b>▶ Смотреть:</b>\n";

                        foreach ($play as $k => $a){
                            foreach ($a as $c => $n){

                                if( $lk != $k){
                                    $lk = $k;
                                    $result .= "<b>$k</b>\n";
                                }

                                if( $n['language'] == 'english' ) $result .= '🇬🇧';

                                if( isset($n['dl']) ){

                                    $result .= '<a href="' . $n['dl'] . '">[dl]</a> ';
                                    if( $user_id == 66478514 ) $result .= "/dl_" . end( explode("/", $n['dl']) ) . " ";
                                }
                                if( isset($n['sub']) ) $result .= '<a href="' . $n['sub'] . '">[sub]</a> ';
                                if( empty($c) ) $c = "Неизвестный автор"; 
                                $result .= '<a href="' . $n['url'] . '">' . $c . '</a> ' . PHP_EOL;
                                $num++;
                            }
                        }
                    }else{
                        $result .= "\n\n<b>Нет доступных источников😔</b>";
                    }
                    
                }else{
                    animeUpdate($query['i']);
                    $result = '<b>🐌Идет загрузка...</b>';
                    keyboard( 'Обновить', 0, [ 't' => '2', 'i' => $query['i'], 'e' => $query['e'] ] );
                }
            }else{
                $result .= "\n<b>🔽 Скачать:</b>\n";

                $video = mysqli_query($_db, ' SELECT * FROM `video` WHERE `anime` = "' . $query['i'] . '" AND `episode` = "' . $query['e'] . '" AND `message` > 0 ' );
                while ($row = mysqli_fetch_array($video)) {
                    if( empty($row['author']) ) $row['author'] = "Неизвестный автор";
                    $result .= '<a href="t.me/Horney/' . $row['message'] . '">' . $row['author'] . '</a> ' . PHP_EOL;
                }
            }

            if( $anime['episode'] > 1 ){
                $t = 50;
                for ($i = 1; $i <= $anime['episode']; $i++) if( $i < 65 ) {
                    if( $i == $query['e'] ) $num = "[$i]";
                    else $num = $i;
                    keyboard( $num, $t, [ 't' => '2', 'i' => $query['i'], 'e' => $i, 'm' => $query['m'] ] );
                    if ($i % 8 == 0) $t++;
                }
            }
    
            keyboard( ( ($type == 0)? '▶️':'' ) . 'Озвучка', 90, [ 't' => '2', 'k' => 0, 'i' => $query['i'], 'e' => $query['e'], 'm' => $query['m'] ] );
            keyboard( ( ($type == 1)? '▶️':'' ) . 'Субтитры', 90, [ 't' => '2', 'k' => 1, 'i' => $query['i'], 'e' => $query['e'], 'm' => $query['m'] ] );
            keyboard( ( ($type == 2)? '▶️':'' ) . 'Оригинал', 90, [ 't' => '2', 'k' => 2, 'i' => $query['i'], 'e' => $query['e'], 'm' => $query['m'] ] );

            keyboard( '⭐️ Избранные', 100, [ 't' => '1', 'i' => $query['i'] ] );

            $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);

            $r = sendMethod ( 'editMessageText', ['chat_id' => $user_id, 'message_id' => $message_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true ] );

        break;

        case 3:

            if( $query['n'] > 0 ){
                $filterS = [ 1 => 'status', 2 => 'order', 3 => 'kind', 4 => 'genre' ];
                if( $query['s'] == 0 ) unset($settings['filter'][$filterS[$query['n']]]);
                else $settings['filter'][$filterS[$query['n']]] = $query['s'];
            }

            if( isset($query['m']) ) $settings['filter']['menu'] = $query['m'];

            if( isset($userData['search_text']) ){
                $q['search'] = $userData['search_text'];
            }
            if( $settings['filter']['menu'] == 1 ){
                if( $settings['filter']['status'] > 0 ) $q['status'] = $filter_status[$settings['filter']['status']];
                if( $settings['filter']['order'] > 0 ) $q['order'] = $filter_order[$settings['filter']['order']];
                if( $settings['filter']['kind'] > 0 ) $q['kind'] = $filter_kind[$settings['filter']['kind']];
            }
            $q['limit'] = 10;

            //sendMethod ( 'sendMessage', ['chat_id' => $user_id, 'text' => http_build_query($q), 'parse_mode' => 'HTML'] );

            $search = json_decode( sendGet ( "https://shikimori.org/api/animes?" . http_build_query($q) ), true );
            if( count($search) > 0 ){
                $result = "<b>Вот что что я нашла для тебя:</b>\n";
                foreach ($search as &$anime) {
                    if( !empty( $anime['russian'] ) && $settings['name_lng'] == 1 ) $name = $anime['russian'];
                    else $name = $anime['name'];
                    $result .= "📺 " . $name . " (" . date( 'Y', strtotime( $anime['aired_on'] ) ) . ") - /a_" . $anime[id] . "\n";
                }
            }else{
                $result = "У меня не получилось ничего найти по твоему запросу 😞";
            }

            filterMenu();
            
            sendMethod ( 'editMessageText', ['chat_id' => $user_id, 'message_id' => $message_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML'] );
        break;

        case 4:
            
            switch($query['s']){
                case 1:
                    if($settings['notification']['on'] == 0){
                        $answerCallbackText = 'Все уведомления выключены';
                        $settings['notification']['on'] = 1;
                    }else{
                        $settings['notification']['on'] = 0;
                        $answerCallbackText = 'Все уведомления включены';                    
                    }
                break;

                case 2:
                    $kindType = [ 1 => 'fandub', 2 => 'subtitles', 3 => 'raw' ];

                    if($settings['notification']['kind'][$kindType[$query['k']]] == 0){
                        $answerCallbackText = 'Выключены уведомления этого типа';
                        $settings['notification']['kind'][$kindType[$query['k']]] = 1;
                    }else{
                        $settings['notification']['kind'][$kindType[$query['k']]] = 0;
                        $answerCallbackText = 'Включены уведомления этого типа';                    
                    }
                break;

                case 3:
                    if($settings['notification']['video'] == 0){
                        $answerCallbackText = 'Выключены уведомления о появлении новых видео в telegram';
                        $settings['notification']['video'] = 1;
                    }else{
                        $settings['notification']['video'] = 0;
                        $answerCallbackText = 'Включены уведомления о появлении новых видео в telegram';                    
                    }
                break;

                case 4:

                    $author = [
                        'AnimeVost', 'AniLibria', 'AniStar', 'AmazingDubbing', 'AniPlay', 'SHIZAProject', 'Wakanim', 'AniDub', 'AniMaunt', 'Persona99', 'AniRise', 'Animedia', 'StreamSound', 'AniRay', 'OSCProject', 'SekaiProject', 'SovetRomantica', 'AniBand', 'UNRAVEL', 'SchoolDream', 'DNT_Group', 'HaronMedia', 'AniMur', 'BAN', 'Shachiburi', 'Krokozyabl', 'Bamboo', 'AniPlague', 'TAKEOVERProject', 'AniZone', 'KBK', 'IsekaiCamp', 'Molodoy', 'AsuraProject', 'AntonShanteau', 'AniJoker', 'micola777', 'JAM', 'HiromiTV', 'AirMAX', 'Mustadio', 'Amikiri', 'Chyuu', 'AniFuck', 'Leproduction', 'AnimeRip', 'Boston', 'FRONDA', 'TrushkeenDub', 'onibaku', 'AniRus', 'Mewtwo', 'Hekomi', 'Malevich', 'BlackBoardCinema', 'Aletov', 'KitsuneBox', 'ZoneVision', 'RusReanimedia', 'Косолапый', '9йнеизвестный', '2x2', 'AnigaKuDub', 'Diaton', 'Euler', 'shockwave', 'УЖНХ', 'СтудийнаяБанда', 'АнтонШанто', 'HectoR', 'Ados', 'Overlord', 'Lucky4', 'АниТлен', 'Лупа', 'OpenDub',
                    ];
                    
                    if(isset($query['a']) && isset($author[$query['a']]) ){
                        if( !isset($settings['notification']['author']) OR $settings['notification']['author'] == 'all' )
                            $settings['notification']['author'] = [];
                        if(!isset($settings['notification']['author'][$author[$query['a']]]))
                            $settings['notification']['author'][$author[$query['a']]] = 1;
                        else
                            unset($settings['notification']['author'][$author[$query['a']]]);
                    }elseif( $query['a'] == 'all')
                        $settings['notification']['author'] = 'all';

                    if(!is_array($settings['notification']['author'])) $at = "✅ Все";
                    else $at = "Все";
                    keyboard( $at, 0, [ 't' => 4, 's' => 4, 'a' => 'all', 'd' => 1 ] );

                    $t = 1;
                    foreach ($author as $k => $v) {
                        if(isset($settings['notification']['author'][$v])) $v = "✅ $v";
                        keyboard( $v, $t, [ 't' => 4, 's' => 4, 'a' => $k, 'd' => 1 ] );
                        if($k % 2)  $t++;
                    }

                    keyboard( "⚙️ Настройки", 100, [ 't' => 4 ] );
                    $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
                    $result = "<b>Фильтр авторов</b>\n";
                break;

                case 5:
                    $settings['name_lng'] = ($settings['name_lng'] == 1)?0:1;
                break;

                case 100:
                    $settings['dev'] = ($settings['dev'] == 1)?0:1;
                break;
            }
        
        
            if($query['d'] !== 1){
                $result = "<b>Настройки</b>";
                settingsMenu();
            }

            if( $settings['dev'] == 1 ) $result .= "\n<code>".json_encode($settings, JSON_PRETTY_PRINT)."</code>";
            
            sendMethod ( 'editMessageText', ['chat_id' => $user_id, 'message_id' => $message_id, 'text' => $result, 'reply_markup' => json_encode( $keyboard ), 'parse_mode' => 'HTML'] );
        break;
    }

    //sendMethod ( 'sendMessage', ['chat_id' => 66478514, 'text' => json_encode($query), 'parse_mode' => 'HTML'] );
    sendMethod ( 'answerCallbackQuery', [ 'callback_query_id' => $callback_query_id, 'text' => $answerCallbackText ] );
}

mysqli_query ( $_db, ' UPDATE `user` SET `login` = "' . $login . '", `username` = "' . $username . '", `settings` = "' . base64_encode(json_encode($settings)) . '", `time` = "' . time( ) . '"  WHERE `id` = "' . $user_id . '" ' );