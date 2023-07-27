<?php
//Petit Note 2021-2023 (c)satopian MIT LICENCE
//https://paintbbs.sakura.ne.jp/
//APIを使ってお絵かき掲示板からMisskeyにノート
$misskey_note_ver=20230727;

class misskey_note{

	//投稿済みの記事をMisskeyにノートするための前処理
	public static function before_misskey_note (){

		global $boardname,$home,$petit_ver,$petit_lot,$skindir,$use_aikotoba,$set_nsfw,$en,$deny_all_posts;
		//管理者判定処理
		session_sta();
		$aikotoba = $use_aikotoba ? aikotoba_valid() : true;
		aikotoba_required_to_view();
		$adminpost=adminpost_valid();
		$admindel=admindel_valid();

		$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
		$id = t((string)filter_input(INPUT_POST,'id'));//intの範囲外
		$id = $id ? $id : t((string)filter_input(INPUT_GET,'id'));//intの範囲外
		$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));
		$no = $no ? $no : t((string)filter_input(INPUT_GET,'no',FILTER_VALIDATE_INT));
		$misskey_note = (bool)filter_input(INPUT_GET,'misskey_note',FILTER_VALIDATE_BOOLEAN);
		$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
		$postresno = (int)$no;
	
		check_open_no($no);
		if(!is_file(LOG_DIR."{$no}.log")){
			return error($en? 'The article does not exist.':'記事がありません。');
		}
		$rp=fopen(LOG_DIR."{$no}.log","r");
		flock($rp, LOCK_EX);

		$r_arr = create_array_from_fp($rp);

		if(empty($r_arr)){
			closeFile($rp);
			return error($en?'This operation has failed.':'失敗しました。');
		}
		$find=false;
		foreach($r_arr as $i =>$val){
			$_line=explode("\t",trim($val));
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$_line;
			if($id===$time && $no===$_no){

				$out[0][]=create_res($_line);
				$find=true;
				break;
				
			}

		}
		if(!$find){
			closeFile ($rp);
			return error($en?'The article was not found.':'記事が見つかりません。');
		}

		closeFile ($rp);

		$token=get_csrf_token();

		// nsfw
		$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);
		$set_nsfw_show_hide=(bool)filter_input(INPUT_COOKIE,'p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

		$count_r_arr=count($r_arr);
		$edit_mode = 'editmode';

		$templete='before_misskey_note.html';
		return include __DIR__.'/'.$skindir.$templete;
	}
	//投稿済みの画像をMisskeyにNoteするための投稿フォーム
	public static function misskey_note_edit_form(){

		global  $petit_ver,$petit_lot,$home,$boardname,$skindir,$set_nsfw,$en,$max_kb,$use_upload,$mark_sensitive_image;

		check_same_origin();

		$token=get_csrf_token();

		$admindel=admindel_valid();
		$adminpost=adminpost_valid();
		$admin = ($admindel||$adminpost);

		$pwd=(string)filter_input(INPUT_POST,'pwd');
		$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
		$pwd = $pwd ? $pwd : $pwdc;
		
		$id_and_no=(string)filter_input(INPUT_POST,'id_and_no');

		list($id,$no)=explode(",",trim($id_and_no));

		check_open_no($no);
		if(!is_file(LOG_DIR."{$no}.log")){
			return error($en? 'The article does not exist.':'記事がありません。');
		}
		$rp=fopen(LOG_DIR."{$no}.log","r");
		flock($rp, LOCK_EX);

		$r_arr = create_array_from_fp($rp);

		if(empty($r_arr)){
			closeFile($rp);
			return error($en?'This operation has failed.':'失敗しました。');
		}

		$flag=false;
		foreach($r_arr as $val){

			$line=explode("\t",trim($val));

			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;
			if($id===$time && $no===$_no){
			
				if((!$admin || $verified!=='adminpost')&&(!$pwd||!password_verify($pwd,$hash))){
					return error($en?'Password is incorrect.':'パスワードが違います。');
				}
				if($admin||check_elapsed_days($time)){
					$flag=true;
					break;
				}
			}
		}

		if(!$flag){
			closeFile($rp);
			return error($en?'This operation has failed.':'失敗しました。');
		}
		closeFile($rp);

		check_AsyncRequest();//Asyncリクエストの時は処理を中断

		$out[0][]=create_res($line);//$lineから、情報を取り出す;

		$resno=(int)filter_input(INPUT_POST,'postresno',FILTER_VALIDATE_INT);//古いバージョンで使用
		$page=(int)filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);

		foreach($line as $i => $val){
			$line[$i]=h($val);
		}
		list($_no,$sub,$name,$verified,$_com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;

		$com=h(str_replace('"\n"',"\n",$com));

		$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);
		// HTML出力
		$templete='misskey_note_edit_form.html';
		return include __DIR__.'/'.$skindir.$templete;
	}

	//Misskeyに投稿するSESSIONデータを作成
	public static function create_misskey_note_sessiondata(){
		global $en,$usercode,$root_url,$mark_sensitive_image,$skindir,$petit_lot,$misskey_servers,$boardname;
		
		check_csrf_token();

		$userip =t(get_uip());

		$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));
		$src_image = t((string)filter_input(INPUT_POST,'src_image'));
		$com = t((string)filter_input(INPUT_POST,'com'));
		$abbr_toolname = t((string)filter_input(INPUT_POST,'abbr_toolname'));
		$paintsec = filter_input(INPUT_POST,'paintsec',FILTER_VALIDATE_INT);
		$hide_thumbnail = (bool)filter_input(INPUT_POST,'hide_thumbnail',FILTER_VALIDATE_BOOLEAN);
		$article_url_link = (bool)filter_input(INPUT_POST,'article_url_link',FILTER_VALIDATE_BOOLEAN);
		$hide_content = (bool)filter_input(INPUT_POST,'hide_content',FILTER_VALIDATE_BOOLEAN);
		$cw = t((string)filter_input(INPUT_POST,'cw'));
			
		$cw = $hide_content ? $cw : null;

		$tool=switch_tool($abbr_toolname);
		
		$painttime=calcPtime($paintsec);
		$painttime = $en ? $painttime['en'] : $painttime['ja'];
		session_sta();

		$src_image=basename($src_image);
		//SESSIONに投稿内容を格納
		$_SESSION['sns_api_val']=[$com,$src_image,$tool,$painttime,$hide_thumbnail,$no,$article_url_link,$cw];

		$misskey_servers=isset($misskey_servers)?$misskey_servers:
		[
		
			["misskey.io","https://misskey.io"],
			["misskey.design","https://misskey.design"],
			["nijimiss.moe","https://nijimiss.moe"],
			["sushi.ski","https://sushi.ski"],
			["misskey.art","https://misskey.art"],
			["misskey.gamelore.fun","https://misskey.gamelore.fun"],
			["novelskey.tarbin.net","https://novelskey.tarbin.net"],
			["tyazzkey.work","https://tyazzkey.work"],
			["misskey.delmulin.com","https://misskey.delmulin.com"],
		
		];
		$misskey_servers[]=[($en?"Direct input":"直接入力"),"direct"];//直接入力の箇所はそのまま。

		$misskey_server_radio_cookie=(string)filter_input(INPUT_COOKIE,"misskey_server_radio_cookie");
		$misskey_server_direct_input_cookie=(string)filter_input(INPUT_COOKIE,"misskey_server_direct_input_cookie");

		// HTML出力
		$templete='misskey_server_selection.html';
		return include __DIR__.'/'.$skindir.$templete;
	}

	public static function create_misskey_authrequesturl(){
		global $root_url;
		global $en;

		check_same_origin();

		$misskey_server_radio=(string)filter_input(INPUT_POST,"misskey_server_radio",FILTER_VALIDATE_URL);
		$misskey_server_radio_for_cookie=(string)filter_input(INPUT_POST,"misskey_server_radio");//directを判定するためurlでバリデーションしていない
		$misskey_server_radio_for_cookie=($misskey_server_radio_for_cookie === 'direct') ? 'direct' : $misskey_server_radio;
		$misskey_server_direct_input=(string)filter_input(INPUT_POST,"misskey_server_direct_input",FILTER_VALIDATE_URL);
		setcookie("misskey_server_radio_cookie",$misskey_server_radio_for_cookie, time()+(86400*30),"","",false,true);
		setcookie("misskey_server_direct_input_cookie",$misskey_server_direct_input, time()+(86400*30),"","",false,true);
		$share_url='';

		if(!$misskey_server_radio && !$misskey_server_direct_input){
			error($en ? "Please select an SNS sharing destination.":"SNSの共有先を選択してください。");
		}

		if(!$misskey_server_radio && $misskey_server_direct_input){
			$misskey_server_radio = $misskey_server_direct_input;
		}

		session_sta();
		// セッションIDとユニークIDを結合
		$sns_api_session_id = session_id() . uniqid() . mt_rand();
		// SHA256ハッシュ化
		$sns_api_session_id=hash('sha256', $sns_api_session_id);

		$_SESSION['sns_api_session_id']=$sns_api_session_id;
		$_SESSION['misskey_server_radio']=$misskey_server_radio;

		$encoded_root_url = urlencode($root_url);

		if(isset($_SESSION['accessToken'])){

			// ダミーの投稿を試みる（textフィールドを空にする）
			$postUrl = "{$misskey_server_radio}/api/notes/create";
			$postData = array(
				'i' => $_SESSION['accessToken'],
				'text' => '', // 投稿を成功させないようにするためtextフィールドを空にする
			);
	
			$postCurl = curl_init();
			curl_setopt($postCurl, CURLOPT_URL, $postUrl);
			curl_setopt($postCurl, CURLOPT_POST, true);
			curl_setopt($postCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($postCurl, CURLOPT_POSTFIELDS, json_encode($postData));
			curl_setopt($postCurl, CURLOPT_RETURNTRANSFER, true);
			$postResponse = curl_exec($postCurl);
			$postStatusCode = curl_getinfo($postCurl, CURLINFO_HTTP_CODE); // HTTPステータスコードを取得
			curl_close($postCurl);
	
			// HTTPステータスコードが403の時は、トークン不一致と判断しアプリを認証
			if ($postStatusCode === 403) {
				$Location = "{$misskey_server_radio}/miauth/{$sns_api_session_id}?name=Petit%20Note&callback={$encoded_root_url}connect_misskey_api.php&permission=write:notes,write:drive";
			} else {
				$Location = "{$root_url}connect_misskey_api.php?noauth=on&s_id={$sns_api_session_id}";
			}
	
		}else{//SESSIONにトークンがセットされていない時はアプリを認証
			$Location = "{$misskey_server_radio}/miauth/{$sns_api_session_id}?name=Petit%20Note&callback={$encoded_root_url}connect_misskey_api.php&permission=write:notes,write:drive";
	
		}
		return header('Location:'.$Location);
		
	}
	// Misskeyへの投稿が成功した事を知らせる画面
	public static function misskey_success(){
		global $en,$skindir,$boardname,$petit_lot;
		$no = (string)filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
		
		session_sta();
		
		$misskey_server_url = isset($_SESSION['misskey_server_radio']) ? $_SESSION['misskey_server_radio'] : "";
		if(!$misskey_server_url || !filter_var($misskey_server_url,FILTER_VALIDATE_URL) || !$no){
			return header('Location: ./');
		}
		$templete='misskey_success.html';
		return include __DIR__.'/'.$skindir.$templete;
	}
}

