<?php
//Petit Note 2021-2025 (c)satopian MIT LICENCE
//https://paintbbs.sakura.ne.jp/
//APIを使ってお絵かき掲示板からMisskeyにノート
$misskey_note_ver=20250326;

class misskey_note{

	//投稿済みの記事をMisskeyにノートするための前処理
	public static function before_misskey_note (): void {

		global $boardname,$home,$petit_ver,$petit_lot,$skindir,$use_aikotoba,$set_nsfw,$en,$deny_all_posts;
		//管理者判定処理
		session_sta();
		$aikotoba = $use_aikotoba ? aikotoba_valid() : true;
		aikotoba_required_to_view(true);
		$adminpost=adminpost_valid();
		$admindel=admindel_valid();

		$pwdc=(string)filter_input_data('COOKIE','pwdc');
		$id = t(filter_input_data('POST','id'));//intの範囲外
		$id = $id ? $id : t(filter_input_data('GET','id'));//intの範囲外
		$no = t(filter_input_data('POST','no',FILTER_VALIDATE_INT));
		$no = $no ? $no : t(filter_input_data('GET','no',FILTER_VALIDATE_INT));
		$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
		$resmode = false;//使っていない
		$page= $_SESSION['current_page_context']["page"] ?? 0;
		$resno= $_SESSION['current_page_context']["resno"] ?? null;//下の行でnull判定
		$resno ?? $no;
		$postpage = $page;//古いテンプレート互換
		$postresno = $resno;//古いテンプレート互換

		check_open_no($no);
		if(!is_file(LOG_DIR."{$no}.log")){
			error($en? 'The article does not exist.':'記事がありません。');
		}
		$rp=fopen(LOG_DIR."{$no}.log","r");
		file_lock($rp, LOCK_EX);

		$r_arr = create_array_from_fp($rp);

		if(empty($r_arr)){
			closeFile($rp);
			error($en?'This operation has failed.':'失敗しました。');
		}
		$find=false;
		foreach($r_arr as $i =>$val){
			$_line=explode("\t",trim($val));
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_hash_img,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$_line;
			if($id===$time && $no===$_no){

				$out[0][]=create_res($_line);
				$find=true;
				break;
				
			}

		}
		if(!$find){
			closeFile ($rp);
			error($en?'The article was not found.':'記事が見つかりません。');
		}

		closeFile ($rp);

		$token=get_csrf_token();

		// nsfw
		$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
		$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

		$count_r_arr=count($r_arr);
		$edit_mode = 'editmode';

		$_SESSION['current_id']	= $id;

		$admin_pass= null;

		$templete='before_misskey_note.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}
	//投稿済みの画像をMisskeyにNoteするための投稿フォーム
	public static function misskey_note_edit_form(): void {

		global  $petit_ver,$petit_lot,$home,$boardname,$skindir,$set_nsfw,$en,$max_kb,$use_upload;

		check_same_origin();

		$token=get_csrf_token();

		$admindel=admindel_valid();
		$adminpost=adminpost_valid();
		$admin = ($admindel||$adminpost);

		$pwd=(string)filter_input_data('POST','pwd');
		$pwdc=(string)filter_input_data('COOKIE','pwdc');
		$pwd = $pwd ? $pwd : $pwdc;
		
		$id_and_no=(string)filter_input_data('POST','id_and_no');

		list($id,$no)=explode(",",trim($id_and_no));

		check_open_no($no);
		if(!is_file(LOG_DIR."{$no}.log")){
			error($en? 'The article does not exist.':'記事がありません。');
		}
		$rp=fopen(LOG_DIR."{$no}.log","r");
		file_lock($rp, LOCK_EX);

		$r_arr = create_array_from_fp($rp);

		if(empty($r_arr)){
			closeFile($rp);
			error($en?'This operation has failed.':'失敗しました。');
		}

		$flag=false;
		foreach($r_arr as $val){

			$line=explode("\t",trim($val));

			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_hash_img,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;
			if($id===$time && $no===$_no){
			
				if((!$admin || $verified!=='adminpost')&&(!$pwd||!password_verify($pwd,$hash))){
					error($en?'Password is incorrect.':'パスワードが違います。');
				}
				if($admin||check_elapsed_days($time)){
					$flag=true;
					break;
				}
			}
		}

		if(!$flag){
			closeFile($rp);
			error($en?'This operation has failed.':'失敗しました。');
		}
		closeFile($rp);

		check_AsyncRequest();//Asyncリクエストの時は処理を中断

		$out[0][]=create_res($line);//$lineから、情報を取り出す;


		$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
		$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

		$page= $_SESSION['current_page_context']["page"] ?? 0;
		$resno= $_SESSION['current_page_context']["resno"] ?? null;//下の行でnull判定
		$resno ?? $no;
		$postpage = $page;//古いテンプレート互換
		$postresno = $resno;//古いテンプレート互換

		$userdel = false;
		$admindel = false;	

		$image_rep=false;

		$_SESSION['current_id']	= $id;

		$admin_pass= null;
		// HTML出力
		$templete='misskey_note_edit_form.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}

	//Misskeyに投稿するSESSIONデータを作成
	public static function create_misskey_note_sessiondata(): void {
		global $en,$usercode,$root_url,$skindir,$petit_lot,$misskey_servers,$boardname;
		
		check_csrf_token();

		$userip =t(get_uip());

		$no = t(filter_input_data('POST','no',FILTER_VALIDATE_INT));
		$src_image = t(filter_input_data('POST','src_image'));
		$com = t(filter_input_data('POST','com'));
		$abbr_toolname = t(filter_input_data('POST','abbr_toolname'));
		$paintsec = (int)filter_input_data('POST','paintsec',FILTER_VALIDATE_INT);
		$hide_thumbnail = (bool)filter_input_data('POST','hide_thumbnail',FILTER_VALIDATE_BOOLEAN);
		$show_painttime = (bool)filter_input_data('POST','show_painttime',FILTER_VALIDATE_BOOLEAN);
		$article_url_link = (bool)filter_input_data('POST','article_url_link',FILTER_VALIDATE_BOOLEAN);
		$hide_content = (bool)filter_input_data('POST','hide_content',FILTER_VALIDATE_BOOLEAN);
		$cw = t(filter_input_data('POST','cw'));
		if($hide_content && !$cw){
			error($en?"Content warning field is empty.":"注釈がありません。");
		}
		check_AsyncRequest();//Asyncリクエストの時は処理を中断

		$cw = $hide_content ? $cw : null;

		$tool=switch_tool($abbr_toolname);
		
		$painttime = calcPtime($paintsec);
		$painttime_en = $painttime ? $painttime['en'] : '';
		$painttime_ja = $painttime ? $painttime['ja'] : '';
		$painttime = $en ? $painttime_en : $painttime_ja;
		$painttime = $show_painttime ? $painttime : '';

		session_sta();

		$src_image=basename($src_image);
		//SESSIONに投稿内容を格納
		$_SESSION['sns_api_val']=[$com,$src_image,$tool,$painttime,$hide_thumbnail,$no,$article_url_link,$cw];

		$misskey_servers= $misskey_servers ?? 
		[
		
			["misskey.io","https://misskey.io"],
			["xissmie.xfolio.jp","https://xissmie.xfolio.jp"],
			["misskey.design","https://misskey.design"],
			["nijimiss.moe","https://nijimiss.moe"],
			["misskey.art","https://misskey.art"],
			["oekakiskey.com","https://oekakiskey.com"],
			["misskey.gamelore.fun","https://misskey.gamelore.fun"],
			["novelskey.tarbin.net","https://novelskey.tarbin.net"],
			["tyazzkey.work","https://tyazzkey.work"],
			["sushi.ski","https://sushi.ski"],
			["misskey.delmulin.com","https://misskey.delmulin.com"],
			["side.misskey.productions","https://side.misskey.productions"],
			["mk.shrimpia.network","https://mk.shrimpia.network"],

		];
		$misskey_servers[]=[($en?"Direct input":"直接入力"),"direct"];//直接入力の箇所はそのまま。

		$misskey_server_radio_cookie=(string)filter_input_data('COOKIE',"misskey_server_radio_cookie");
		$misskey_server_direct_input_cookie=(string)filter_input_data('COOKIE',"misskey_server_direct_input_cookie");

		$admin_pass= null;
		// HTML出力
		$templete='misskey_server_selection.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}

	public static function create_misskey_authrequesturl(): void {
		global $root_url;
		global $en;

		check_same_origin();

		$misskey_server_radio=(string)filter_input_data('POST',"misskey_server_radio",FILTER_VALIDATE_URL);
		$misskey_server_radio_for_cookie=(string)filter_input_data('POST',"misskey_server_radio");//directを判定するためurlでバリデーションしていない
		$misskey_server_radio_for_cookie=($misskey_server_radio_for_cookie === 'direct') ? 'direct' : $misskey_server_radio;
		$misskey_server_direct_input=(string)filter_input_data('POST',"misskey_server_direct_input",FILTER_VALIDATE_URL);
		setcookie("misskey_server_radio_cookie",$misskey_server_radio_for_cookie, time()+(86400*30),"","",false,true);
		setcookie("misskey_server_direct_input_cookie",$misskey_server_direct_input, time()+(86400*30),"","",false,true);

		if(!$misskey_server_radio && !$misskey_server_direct_input){
			error($en ? "Please select an misskey server.":"Misskeyサーバを選択してください。");
		}

		if(!$misskey_server_radio && $misskey_server_direct_input){
			$misskey_server_radio = $misskey_server_direct_input;
		}

		session_sta();
		// セッションIDとユニークIDを結合
		$sns_api_session_id = session_id() . random_bytes(16);

		// SHA256ハッシュ化
		$sns_api_session_id=hash('sha256', $sns_api_session_id);

		$_SESSION['sns_api_session_id']=$sns_api_session_id;

		$encoded_root_url = urlencode($root_url);

		//別のサーバを選択した時はトークンをクリア
		if(!isset($_SESSION['misskey_server_radio']) ||
		$_SESSION['misskey_server_radio']!==$misskey_server_radio){
			unset($_SESSION['accessToken']);//トークンをクリア
		}
		//投稿完了画面に表示するサーバのURl
		$_SESSION['misskey_server_radio']=$misskey_server_radio;

		//アプリを認証するためのURL
		$Location = "{$misskey_server_radio}/miauth/{$sns_api_session_id}?name=Petit%20Note&callback={$encoded_root_url}connect_misskey_api.php&permission=write:notes,write:drive";

		if(isset($_SESSION['accessToken'])){//SESSIONのトークンが有効か確認

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
				unset($_SESSION['accessToken']);//トークンをクリア
			} else {
				//アプリの認証をスキップするURL
				$Location = "{$root_url}connect_misskey_api.php?skip_auth_check=on&s_id={$sns_api_session_id}";
			}
		}

		redirect($Location);

	}
	// Misskeyへの投稿が成功した事を知らせる画面
	public static function misskey_success(): void {
		global $en,$skindir,$boardname,$petit_lot;
		$no = (string)filter_input_data('GET', 'no',FILTER_VALIDATE_INT);
		
		session_sta();
		
		$misskey_server_url = $_SESSION['misskey_server_radio'] ?? "";
		if(!$misskey_server_url || !filter_var($misskey_server_url,FILTER_VALIDATE_URL) || !$no){
			redirect('./');
		}
		$admin_pass= null;
		$templete='misskey_success.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}
}

