<?php
//Petit Note 2021-2023 (c)satopian MIT LICENCE
//https://paintbbs.sakura.ne.jp/
//APIを使ってお絵かき掲示板からMisskeyにノート
$misskey_note_ver=20230718;
class misskey_note{

	//投稿済みの記事をMisskeyにノートするための前処理
	public static function before_misskey_note (){

		global $boardname,$home,$petit_ver,$petit_lot,$skindir,$use_aikotoba,$set_nsfw,$en;
		//管理者判定処理
		check_same_origin();
		session_sta();
		$aikotoba = $use_aikotoba ? aikotoba_valid() : true;
		aikotoba_required_to_view();

		$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
		$id = t((string)filter_input(INPUT_POST,'id'));//intの範囲外
		$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));

		if(!is_file(LOG_DIR."{$no}.log")){
			return error($en? 'The article does not exist.':'記事がありません。');
		}
		check_open_no($no);
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
		$edit_mode=false;
		$admindel=false; 
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
			
				if(!$pwd||!password_verify($pwd,$hash)){
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

		$hide_thumb_checkd = true;
		// HTML出力
		$templete='misskey_note_edit_form.html';
		return include __DIR__.'/'.$skindir.$templete;
	}

	//Misskeyに投稿するSESSIONデータを作成
	public static function create_misskey_post_sessiondata(){
		global $en,$usercode,$root_url,$mark_sensitive_image,$skindir,$petit_lot,$misskey_servers,$boardname;
		
		$userip =t(get_uip());

		$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));
		$src_image = t((string)filter_input(INPUT_POST,'src_image'));
		$com = t((string)filter_input(INPUT_POST,'com'));
		$abbr_toolname = t((string)filter_input(INPUT_POST,'abbr_toolname'));
		$paintsec = filter_input(INPUT_POST,'paintsec',FILTER_VALIDATE_INT);
		$hide_thumbnail = (bool)filter_input(INPUT_POST,'hide_thumbnail',FILTER_VALIDATE_BOOLEAN);
		$article_url_link = (bool)filter_input(INPUT_POST,'article_url_link',FILTER_VALIDATE_BOOLEAN);
		
		$tool=switch_tool($abbr_toolname);
		
		$painttime=calcPtime($paintsec);
		$painttime = $en ? $painttime['en'] : $painttime['ja'];
		session_sta();
		
		//SESSIONに投稿内容を格納
		$_SESSION['sns_api_val']=[$com,$src_image,$tool,$painttime,$hide_thumbnail,$no,$article_url_link];

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
			$servers[]=[($en?"Direct input":"直接入力"),"direct"];//直接入力の箇所はそのまま。

		$misskey_server_radio_cookie=(string)filter_input(INPUT_COOKIE,"misskey_server_radio_cookie");
		$misskey_server_direct_input_cookie=(string)filter_input(INPUT_COOKIE,"misskey_server_direct_input_cookie");

		// HTML出力
		$templete='misskey_server_selection.html';
		return include __DIR__.'/'.$skindir.$templete;
	}

	public static function create_misskey_authrequesturl(){
		global $root_url;
		global $en;

		$misskey_server_radio=(string)filter_input(INPUT_POST,"misskey_server_radio",FILTER_VALIDATE_URL);
		$misskey_server_radio_for_cookie=(string)filter_input(INPUT_POST,"misskey_server_radio");//directを判定するためurlでバリデーションしていない
		$misskey_server_radio_for_cookie=($misskey_server_radio_for_cookie === 'direct') ? 'direct' : $misskey_server_radio;
		$misskey_server_direct_input=(string)filter_input(INPUT_POST,"misskey_server_direct_input",FILTER_VALIDATE_URL);
		setcookie("misskey_server_radio_cookie",$misskey_server_radio_for_cookie, time()+(86400*30),"","",false,true);
		setcookie("misskey_server_direct_input_cookie",$misskey_server_direct_input, time()+(86400*30),"","",false,true);
		$share_url='';

		if(!$misskey_server_radio){
			error($en ? "Please select an SNS sharing destination.":"SNSの共有先を選択してください。");
		}

		session_sta();
		// セッションIDとユニークIDを結合
		$sns_api_session_id = session_id() . uniqid();
		// SHA256ハッシュ化
		$sns_api_session_id=hash('sha256', $sns_api_session_id);

		$_SESSION['sns_api_session_id']=$sns_api_session_id;

		$root_url = urlencode($root_url);

		return header("Location: {$misskey_server_radio}/miauth/{$sns_api_session_id}?name=MyApp&callback={$root_url}&mode=connect_misskey_api&permission=write:notes,write:following,read:drive,write:drive");
	}

	//Misskey APIに送信
	public static function connect_misskey_api(){
		
		global $en,$root_url;

		session_sta();
		
		if((!isset($_SESSION['sns_api_session_id']))||(!isset($_SESSION['sns_api_val']))){
			return header( "Location: ./ ") ;
		};

		$baseUrl = isset($_SESSION['misskey_server_radio']) ? $_SESSION['misskey_server_radio'] : "https://misskey.io";
		// 認証チェック
		$sns_api_session_id = $_SESSION['sns_api_session_id'];
		list($com,$src_image,$tool,$painttime,$hide_thumbnail,$no,$article_url_link) = $_SESSION['sns_api_val'];
		$src_image=basename($src_image);

		if((!$src_image)||!is_file(__DIR__.'/src/'.$src_image)){
			 error($en ? "Image does not exist." : "画像がありません。");
		};

		$checkUrl = $baseUrl . "/api/miauth/{$sns_api_session_id}/check";
		
		$checkCurl = curl_init();
		curl_setopt($checkCurl, CURLOPT_URL, $checkUrl);
		curl_setopt($checkCurl, CURLOPT_POST, true);
		curl_setopt($checkCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($checkCurl, CURLOPT_POSTFIELDS, json_encode([]));//空のData
		curl_setopt($checkCurl, CURLOPT_RETURNTRANSFER, true);
		
		$checkResponse = curl_exec($checkCurl);
		curl_close($checkCurl);
		
		if (!$checkResponse) {
			error($en ? "Authentication failed." :"認証に失敗しました。");	
		}
		
		$responseData = json_decode($checkResponse, true);
		$accessToken = $responseData['token'];
		$user = $responseData['user'];
		
		// 画像のアップロード
		$imagePath = __DIR__.'/src/'.$src_image;
		$uploadUrl = $baseUrl . "/api/drive/files/create";
		$uploadHeaders = array(
			'Authorization: Bearer ' . $accessToken,
			'Content-Type: multipart/form-data'
		);
		$uploadFields = array(
			'i' => $accessToken,
			'file' => new CURLFile($imagePath),
		);
		// var_dump($uploadFields);
		$uploadCurl = curl_init();
		curl_setopt($uploadCurl, CURLOPT_URL, $uploadUrl);
		curl_setopt($uploadCurl, CURLOPT_POST, true);
		curl_setopt($uploadCurl, CURLOPT_HTTPHEADER, $uploadHeaders);
		curl_setopt($uploadCurl, CURLOPT_POSTFIELDS, $uploadFields);
		curl_setopt($uploadCurl, CURLOPT_RETURNTRANSFER, true);
		$uploadResponse = curl_exec($uploadCurl);
		$uploadStatusCode = curl_getinfo($uploadCurl, CURLINFO_HTTP_CODE);
		curl_close($uploadCurl);
		// var_dump($uploadResponse);
		if (!$uploadResponse) {
			// var_dump($uploadResponse);
			error($en ? "Failed to upload the image." : "画像のアップロードに失敗しました。" );
		}
		
		// アップロードしたファイルのIDを取得
		
		$responseData = json_decode($uploadResponse, true);
		$fileId = isset($responseData['id']) ? $responseData['id']:'';
		
		if(!$fileId){
			// var_dump($responseData);
			error($en ? "Failed to upload the image." : "画像のアップロードに失敗しました。" );
		}
		
		// ファイルの更新
		$updateUrl = $baseUrl . "/api/drive/files/update";
		$updateHeaders = array(
			'Authorization: Bearer ' . $accessToken,
			'Content-Type: application/json'
		);
		$updateData = array(
			'i' => $accessToken,
			'fileId' => $fileId,
			'isSensitive' => (bool)($hide_thumbnail), // isSensitiveフィールドを更新する場合はここで指定
			// 他に更新したいパラメータがあればここに追加
		);
		
		$updateCurl = curl_init();
		curl_setopt($updateCurl, CURLOPT_URL, $updateUrl);
		curl_setopt($updateCurl, CURLOPT_POST, true);
		curl_setopt($updateCurl, CURLOPT_HTTPHEADER, $updateHeaders);
		curl_setopt($updateCurl, CURLOPT_POSTFIELDS, json_encode($updateData));
		curl_setopt($updateCurl, CURLOPT_RETURNTRANSFER, true);
		$updateResponse = curl_exec($updateCurl);
		$updateStatusCode = curl_getinfo($updateCurl, CURLINFO_HTTP_CODE);
		curl_close($updateCurl);
		
		if (!$updateResponse) {
			error($en ? "Failed to update the file." : "ファイルの更新に失敗しました。");
		}
		// var_dump($updateResponse);
		
		$uploadResult = json_decode($uploadResponse, true);
		
		if ($fileId) {
			
			sleep(10);
			// 投稿
			$tool= $tool ? 'Tool:'.$tool.' ' :'';
			$painttime= $painttime ? 'Paint time:'.$painttime.' ' :'';
			$url=$root_url.'?resno='.$no;
			$status = $tool.$painttime.$com;
			
			$postUrl = $baseUrl . "/api/notes/create";
			$postHeaders = array(
				'Authorization: Bearer ' . $accessToken,
				'Content-Type: application/json'
			);
			$postData = array(
				'i' => $accessToken,
				'text' => $status,
				'fileIds' => array($fileId),
			);
		
			$postCurl = curl_init();
			curl_setopt($postCurl, CURLOPT_URL, $postUrl);
			curl_setopt($postCurl, CURLOPT_POST, true);
			curl_setopt($postCurl, CURLOPT_HTTPHEADER, $postHeaders);
			curl_setopt($postCurl, CURLOPT_POSTFIELDS, json_encode($postData));
			curl_setopt($postCurl, CURLOPT_RETURNTRANSFER, true);
			$postResponse = curl_exec($postCurl);
			$postStatusCode = curl_getinfo($postCurl, CURLINFO_HTTP_CODE);
			curl_close($postCurl);
		// var_dump($postResponse);
			if ($postResponse) {
				$postResult = json_decode($postResponse, true);
				if (!empty($postResult['createdNote']["fileIds"])) {
		
					unset($_SESSION['sns_api_session_id']);
					unset($_SESSION['sns_api_val']);
										
					// var_dump($uploadResponse,$postResponse,$uploadResult,$postResult);
					return header('Location: '.$baseUrl);
				} 
				else {
		error($en ? "Failed to post the content." : "投稿に失敗しました。");
					}
			} else {
				error($en ? "Failed to post the content." : "投稿に失敗しました。");
			}
		} 
				// var_dump($uploadResponse);
						// unset($_SESSION['sns_api_val']);
		// var_dump($uploadResponse,$postResponse,$uploadResult,$postResult, $accessToken );
		// var_dump($postResult['createdNote']["fileIds"],array($mediaId),$uploadStatusCode,$postStatusCode,$postResult,$uploadResponse, $accessToken );
		}
}
