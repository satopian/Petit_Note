<?php
//Petit Note (c)さとぴあ @satopian 2021-2026 MIT License
//https://paintbbs.sakura.ne.jp/
//APIを使ってお絵かき掲示板からMisskeyにノート

$misskey_note_ver=20260817;

final class MisskeyServerSecurity{
	/** @return string|false */
	public static function normalizeBaseUrl(string $url){
		$url=trim($url);
		if(!$url || filter_var($url,FILTER_VALIDATE_URL)===false){
			return false;
		}

		$parts=parse_url($url);
		if(!is_array($parts)
			|| strtolower((string)($parts['scheme'] ?? ''))!=='https'
			|| empty($parts['host'])
			|| isset($parts['user']) || isset($parts['pass'])
			|| isset($parts['query']) || isset($parts['fragment'])
			|| (isset($parts['port']) && (int)$parts['port']!==443)
			|| !in_array((string)($parts['path'] ?? ''),['','/'],true)){
			return false;
		}

		$host=strtolower(rtrim((string)$parts['host'],'.'));
		if(!$host
			|| filter_var($host,FILTER_VALIDATE_IP)!==false
			|| filter_var($host,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)===false
			|| self::resolvePublicIp($host)===false){
			return false;
		}
		return 'https://'.$host;
	}

	/** @return string|false */
	private static function resolvePublicIp(string $host){
		$addresses=@gethostbynamel($host) ?: [];
		if(function_exists('dns_get_record') && defined('DNS_AAAA')){
			$records=@dns_get_record($host,DNS_AAAA);
			if(is_array($records)){
				foreach($records as $record){
					if(!empty($record['ipv6'])){
						$addresses[]=$record['ipv6'];
					}
				}
			}
		}

		$addresses=array_values(array_unique($addresses));
		if(!$addresses){
			return false;
		}
		foreach($addresses as $address){
			if(!self::isPublicIp($address)){
				return false;
			}
		}
		return $addresses[0];
	}

	private static function isPublicIp(string $ip): bool {
		$public_flag=defined('FILTER_FLAG_GLOBAL_RANGE')
			? constant('FILTER_FLAG_GLOBAL_RANGE')
			: FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
		if(!filter_var($ip,FILTER_VALIDATE_IP,$public_flag)){
			return false;
		}

		// PHP 8.1以前のフィルタで公開IP扱いになる特殊用途範囲と、
		// FILTER_FLAG_GLOBAL_RANGEでも通るマルチキャストを拒否する。
		$blocked_ranges=strpos($ip,':')===false
			? ['100.64.0.0/10','192.0.0.0/24','192.0.2.0/24','192.88.99.0/24',
				'198.18.0.0/15','198.51.100.0/24','203.0.113.0/24','224.0.0.0/4']
			: ['64:ff9b::/96','64:ff9b:1::/48','100::/64','2001::/23',
				'2001:db8::/32','2002::/16','ff00::/8'];
		foreach($blocked_ranges as $range){
			if(self::ipInCidr($ip,$range)){
				return false;
			}
		}
		return true;
	}

	private static function ipInCidr(string $ip,string $cidr): bool {
		[$network,$prefix]=explode('/',$cidr,2);
		$ip_binary=inet_pton($ip);
		$network_binary=inet_pton($network);
		if($ip_binary===false || $network_binary===false || strlen($ip_binary)!==strlen($network_binary)){
			return false;
		}

		$prefix=(int)$prefix;
		$full_bytes=intdiv($prefix,8);
		$remaining_bits=$prefix % 8;
		if(substr($ip_binary,0,$full_bytes)!==substr($network_binary,0,$full_bytes)){
			return false;
		}
		if(!$remaining_bits){
			return true;
		}
		$mask=(0xff << (8-$remaining_bits)) & 0xff;
		return (ord($ip_binary[$full_bytes]) & $mask)===(ord($network_binary[$full_bytes]) & $mask);
	}

	/** @return array<int,mixed>|false */
	public static function curlOptions(string $base_url,int $timeout=15){
		$normalized=self::normalizeBaseUrl($base_url);
		if($normalized===false){
			return false;
		}
		$host=(string)parse_url($normalized,PHP_URL_HOST);
		$ip=self::resolvePublicIp($host);
		if($ip===false){
			return false;
		}
		$resolve_ip=strpos($ip,':')!==false ? '['.$ip.']' : $ip;
		return [
			CURLOPT_FOLLOWLOCATION=>false,
			CURLOPT_CONNECTTIMEOUT=>5,
			CURLOPT_TIMEOUT=>max(5,min(60,$timeout)),
			CURLOPT_SSL_VERIFYPEER=>true,
			CURLOPT_SSL_VERIFYHOST=>2,
			CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
			CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,
			CURLOPT_PROXY=>'',
			CURLOPT_RESOLVE=>[$host.':443:'.$resolve_ip],
		];
	}
}

class misskey_note{

	//投稿済みの記事をMisskeyにノートするための前処理
	public static function before_misskey_note (): void {

		global $boardname,$home,$petit_ver,$petit_lot,$skindir,$set_nsfw,$en,$deny_all_posts,$enable_v1_legacy_template_unsafe_get_login;
		//管理者判定処理
		session_sta();

		if(!$enable_v1_legacy_template_unsafe_get_login){
			check_same_origin();
		}

		aikotoba_required_to_view(true);
		$aikotoba= true;//テンプレート互換性
		$adminpost=adminpost_valid();
		$admindel=admindel_valid();

		$pwdc=(string)filter_input_data('COOKIE','pwdc');
		$id = t(filter_input_data('POST','id'));//intの範囲外
		$no = t(filter_input_data('POST','no',FILTER_VALIDATE_INT));
			//互換設定時はgetでもログインできるようにする
		if($enable_v1_legacy_template_unsafe_get_login){
			$id = $id ?: t(filter_input_data('GET','id'));//intの範囲外
			$no = $no ?: t(filter_input_data('GET','no',FILTER_VALIDATE_INT));
		}
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
		$resid="";
		$first_posted_time ="";
		foreach($r_arr as $i =>$val){
			$_line=explode("\t",trim($val));
			[$_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_hash_img,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya]=$_line;
			if($id===$time && $no===$_no){
				$out[0][]=create_res($_line);
				$resid=$first_posted_time;
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

		$_SESSION['current_resid'] = $first_posted_time;

		set_form_display_time();
		$admin_pass= null;

		$templete='before_misskey_note.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}
	//投稿済みの画像をMisskeyにNoteするための投稿フォーム
	public static function misskey_note_edit_form(): void {

		global  $petit_ver,$petit_lot,$home,$boardname,$skindir,$set_nsfw,$en,$max_kb,$use_upload;

		check_submission_interval();
		check_same_origin();

		$token=get_csrf_token();

		$admindel=admindel_valid();
		$adminpost=adminpost_valid();
		$admin = ($admindel||$adminpost);

		$pwd=(string)filter_input_data('POST','pwd');
		$pwdc=(string)filter_input_data('COOKIE','pwdc');
		$pwd = $pwd ?: $pwdc;
		
		$id_and_no=(string)filter_input_data('POST','id_and_no');

		[$id,$no]=explode(",",trim($id_and_no));

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
		$resid="";
		$line=[];
		$first_posted_time="";
		foreach($r_arr as $val){

			$line=explode("\t",trim($val));

			[$_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_hash_img,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya]=$line;
			if($id===$time && $no===$_no){
			
				if((!$admin || $verified!=='adminpost')&&(!$pwd||!password_verify($pwd,$hash))){
					error($en?'Password is incorrect.':'パスワードが違います。');
				}
				if($admin||check_elapsed_days($time)){
					$flag=true;
					$resid=$first_posted_time;
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

		$_SESSION['current_resid'] = $first_posted_time;

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

		$misskey_server_radio_value=(string)filter_input_data('POST',"misskey_server_radio");
		$misskey_server_direct_input_value=(string)filter_input_data('POST',"misskey_server_direct_input");
		$misskey_server_value=($misskey_server_radio_value==='direct')
			? $misskey_server_direct_input_value : $misskey_server_radio_value;
		$misskey_server_radio=MisskeyServerSecurity::normalizeBaseUrl($misskey_server_value);
		$misskey_server_radio_for_cookie=($misskey_server_radio_value === 'direct') ? 'direct' : $misskey_server_radio;
		$misskey_server_direct_input=($misskey_server_radio_value === 'direct' && $misskey_server_radio)
			? $misskey_server_radio : '';
		setcookie("misskey_server_radio_cookie",$misskey_server_radio_for_cookie, time()+(86400*30),"","",false,true);
		setcookie("misskey_server_direct_input_cookie",$misskey_server_direct_input, time()+(86400*30),"","",false,true);

		if(!$misskey_server_radio){
			error($en ? "Please select a public HTTPS Misskey server.":"公開HTTPSのMisskeyサーバを選択してください。");
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
			$security_options=MisskeyServerSecurity::curlOptions($misskey_server_radio);
			$safe_curl=$postCurl!==false && is_array($security_options)
				&& curl_setopt_array($postCurl,$security_options);
			if($safe_curl){
				curl_setopt($postCurl, CURLOPT_URL, $postUrl);
				curl_setopt($postCurl, CURLOPT_POST, true);
				curl_setopt($postCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				curl_setopt($postCurl, CURLOPT_POSTFIELDS, json_encode($postData));
				curl_setopt($postCurl, CURLOPT_RETURNTRANSFER, true);
			}
			$postResponse = $safe_curl ? curl_exec($postCurl) : false;
			$postStatusCode = $safe_curl ? curl_getinfo($postCurl, CURLINFO_HTTP_CODE) : 0; // HTTPステータスコードを取得

			if(PHP_VERSION_ID < 80000 && $postCurl!==false) {//PHP8.0未満の時は
				curl_close($postCurl);
			}

			// HTTPステータスコードが403の時は、トークン不一致と判断しアプリを認証
			if ($postStatusCode === 403 || $postResponse === false) {
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
		$resid = $_SESSION['current_resid'] ?? '';

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
