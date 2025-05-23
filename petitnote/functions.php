<?php
$functions_ver=20250522;
//編集モードログアウト
function logout(): void {
	session_sta();
	unset($_SESSION['admindel']);
	unset($_SESSION['userdel']);

	branch_destination_of_location();
}
//管理者モードログアウト
function logout_admin(): void {
	session_sta();
	unset($_SESSION['admindel']);
	unset($_SESSION['adminpost']);

	branch_destination_of_location();
}

//合言葉認証
function aikotoba(): void {
	global $aikotoba,$en,$keep_aikotoba_login_status;

	check_same_origin();

	session_sta();
	if(!$aikotoba || $aikotoba!==(string)filter_input_data('POST','aikotoba')){
		if(isset($_SESSION['aikotoba'])){
			unset($_SESSION['aikotoba']);
		}
		if((string)filter_input_data('COOKIE','aikotoba')){
			setcookie('aikotoba', '', time() - 3600);//クッキーを削除
		} 
		error($en?'The secret word is wrong':'合言葉が違います。');
	}
	if($keep_aikotoba_login_status){
		setcookie("aikotoba",$aikotoba, time()+(86400*30),"","",false,true);//1ヶ月
	}

	$_SESSION['aikotoba']='aikotoba';

	// 処理が終了したらJavaScriptでリロード

}
//記事の表示に合言葉を必須にする
function aikotoba_required_to_view($required_flag=false): void {

	global $use_aikotoba,$aikotoba_required_to_view,$skindir,$en,$petit_lot,$boardname;

	//不正な値チェック
	$page=(int)filter_input_data('GET','page',FILTER_VALIDATE_INT);
	$resno=(int)filter_input_data('GET','resno',FILTER_VALIDATE_INT);
	if($page<0||$resno<0){//負の値の時はトップページにリダイレクト
		redirect("./");
	}
	//先に年齢確認を行う
	age_check_required_to_view();

	$required_flag=($use_aikotoba && $required_flag);

	if(!$aikotoba_required_to_view && !$required_flag){
	return;
	}

	$admin_pass= null;

	if(!aikotoba_valid()){
		$templete='aikotoba.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();//return include では処理が止まらない。 
	}
}
//ページのコンテキストをセッションに保存
function set_page_context_to_session(){
	session_sta();
	// セッションに保存
	$_SESSION['current_page_context'] = [
		'page' => (int)filter_input_data('GET', 'page', FILTER_VALIDATE_INT),
		'resno' => filter_input_data('GET', 'resno', FILTER_VALIDATE_INT),//未設定時はnull。intでキャストしない事。
		'catalog' => (bool)(filter_input_data('GET', 'mode')==='catalog'),
		'res_catalog' => (bool)filter_input_data('GET', 'res_catalog', FILTER_VALIDATE_BOOLEAN),
		'misskey_note' => (bool)filter_input_data('GET', 'misskey_note', FILTER_VALIDATE_BOOLEAN),
		'search' => (bool)(filter_input_data('GET', 'mode')==='search'),
		'radio' => (int)filter_input_data('GET', 'radio', FILTER_VALIDATE_INT),
		'imgsearch' => (bool)filter_input_data('GET', 'imgsearch', FILTER_VALIDATE_BOOLEAN),
		'q' => (string)filter_input_data('GET', 'q'),
	];
	$_SESSION['current_id'] = null;
}
// 年齢確認ボタン押下でCookieを発行
function age_check(): void {

	check_same_origin();

	$agecheck_passed = (bool)filter_input_data('POST','agecheck_passed',FILTER_VALIDATE_BOOLEAN);
	if($agecheck_passed){
		setcookie("p_n_agecheck_passed","1", time()+(86400*30),"","",false,true);//1ヶ月
	}
	// 処理が終了したらJavaScriptでリロード
}
//記事の表示に年齢確認を必須にする
function age_check_required_to_view(): void {
	global $underage_submit_url;
	global $age_check_required_to_view,$skindir,$en,$petit_lot,$boardname;
	$age_check_required_to_view = $age_check_required_to_view ?? false;
	$underage_submit_url = $underage_submit_url ?? 'https://www.google.com/';

	if(!$age_check_required_to_view){
		setcookie("p_n_agecheck_passed","0", time()+(86400*30),"","",false,true);//1ヶ月
	return;
	}

	$admin_pass= null;
	$agecheck_passed = (bool)filter_input_data('COOKIE','p_n_agecheck_passed');
	if(!$agecheck_passed){
		$templete='age_check.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();//return include では処理が止まらない。 
	}
}

//管理者パスワードを確認
function is_adminpass($pwd): bool {
	global $admin_pass,$second_pass;
	$pwd=(string)$pwd;
	return ($pwd && $admin_pass && $second_pass && !hash_equals($admin_pass,$second_pass) && hash_equals($admin_pass,$pwd));
}

function admin_in(): void {

	global $boardname,$use_diary,$use_aikotoba,$petit_lot,$petit_ver,$skindir,$en,$latest_var;

	aikotoba_required_to_view();

	//古いテンプレート用の使用しない変数
	$page = $resno = $catalog = $res_catalog = $search= $radio= $imgsearch= $q ="";

	session_sta();

	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();
	if(!$use_aikotoba){
		$aikotoba=true;
	}

	$page= $_SESSION['current_page_context']["page"] ?? 0;
	$resno= $_SESSION['current_page_context']["resno"] ?? 0;
	$id = $_SESSION['current_id']	?? "";

	$admin_pass= null;
	// HTML出力
	$templete='admin_in.html';
	include __DIR__.'/'.$skindir.$templete;
}
//合言葉を再確認	
function check_aikotoba(): bool {
	global $en;
	if(!aikotoba_valid()){
		error($en?'The secret word is wrong.':'合言葉が違います。');
	}
	return true;
}
//管理者投稿モード
function adminpost(): void {
	global $second_pass,$en;

	check_same_origin();
	check_password_input_error_count();
	session_sta();
	if(!is_adminpass(filter_input_data('POST','adminpass'))){
		if(isset($_SESSION['adminpost'])){
			unset($_SESSION['adminpost']);
		} 
		error($en?'password is wrong.':'パスワードが違います。');
	}
	session_regenerate_id(true);

	$_SESSION['aikotoba']='aikotoba';
	$_SESSION['adminpost']=$second_pass;

	branch_destination_of_location();
}

//管理者削除モード
function admin_del(): void {
	global $second_pass,$en;

	check_same_origin();
	check_password_input_error_count();

	session_sta();
	if(!is_adminpass(filter_input_data('POST','adminpass'))){
		if(isset($_SESSION['admindel'])){
			unset($_SESSION['admindel']);
		} 
		error($en?'password is wrong.':'パスワードが違います。');
	}
	session_regenerate_id(true);

	$_SESSION['aikotoba']='aikotoba';
	$_SESSION['admindel']=$second_pass;

	branch_destination_of_location();
}
//ユーザー削除モード
function userdel_mode(): void {

	session_sta();
	$_SESSION['userdel']='userdel_mode';

	branch_destination_of_location();
}

//sessionの確認
function adminpost_valid(): bool {
	global $second_pass;
	session_sta();
	return isset($_SESSION['adminpost']) && ($second_pass && hash_equals($second_pass,$_SESSION['adminpost']));
}
function admindel_valid(): bool {
	global $second_pass;
	session_sta();
	return isset($_SESSION['admindel']) && ($second_pass && hash_equals($second_pass,$_SESSION['admindel']));
}
function userdel_valid(): bool {
	session_sta();
	return isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
}
//合言葉の確認
function aikotoba_valid(): bool {
	global $keep_aikotoba_login_status,$aikotoba;
	session_sta();
	$keep=$keep_aikotoba_login_status ? ($aikotoba && ($aikotoba===(string)filter_input_data('COOKIE','aikotoba'))
	) : false;
	return ($keep||isset($_SESSION['aikotoba'])&&($_SESSION['aikotoba']==='aikotoba'));
}

//センシティブコンテンツ
function view_nsfw(): void {

	$view=(bool)filter_input_data('POST','view_nsfw',FILTER_VALIDATE_BOOLEAN);
	if($view){
		setcookie("nsfwc",'on',time()+(60*60*24*30),"","",false,true);
	}

	branch_destination_of_location();
}

//閲覧注意画像を隠す隠さない
function set_nsfw_show_hide(): void {

	$view=(bool)filter_input_data('POST','set_nsfw_show_hide');
	if($view){
		setcookie("p_n_set_nsfw_show_hide","1",time()+(60*60*24*365),"","",false,true);
	}else{
		setcookie("p_n_set_nsfw_show_hide","0",time()+(60*60*24*365),"","",false,true);
	}
}
function set_darkmode(): void {

	$darkmode=(bool)filter_input_data('POST','darkmode');
	if($darkmode){
		setcookie("p_n_set_darkmode","1",time()+(60*60*24*365),"","",false,true);
	}else{
		setcookie("p_n_set_darkmode","0",time()+(60*60*24*365),"","",false,true);
	}
}

//ログイン・ログアウト時のLocationを分岐
function branch_destination_of_location(): void {
	$paintcom=(bool)filter_input_data('POST','paintcom',FILTER_VALIDATE_BOOLEAN);
	if($paintcom){
		location_paintcom();
	}

	session_sta();
	// セッションの値を変数に展開（安全な方法）
	$page_contexts = $_SESSION['current_page_context'] ?? [];
	foreach ($page_contexts as $key => $value) {
		if (in_array($key, ['page', 'resno', 'catalog', 'res_catalog', 'misskey_note' , 'search', 'radio', 'imgsearch', 'q'])) {
			$$key = $value; // 変数の動的作成
		}
	}

	$page = $page ?? 0;
	$resno = $resno ?? 0;
	$catalog = $catalog ?? false;
	$res_catalog = $res_catalog ?? false;
	$misskey_note = $misskey_note ?? false;
	$search = $search ??	false;
	$radio = $radio ?? 0;
	$imgsearch = $imgsearch ?? false;
	$q = $q ?? '';

	if($resno){
		if(!is_file(LOG_DIR.$resno.'.log')){
			redirect('./');
		}
		$id = $_SESSION['current_id'] ?? "";//intの範囲外
		$id = ctype_digit($id) ? $id : "";
		$res_param = $res_catalog ? '&res_catalog=on' : ($misskey_note ? '&misskey_note=on' : '');
		$res_param .= $id ? "&resid={$id}#{$id}" : '';
		
		redirect('./?resno='.h($resno).$res_param);
	}
	if($catalog){
		redirect('./?mode=catalog&page='.h($page));
	}
	if($search){
		
		redirect('./?mode=search&page='.h($page).'&imgsearch='.h($imgsearch).'&q='.h($q).'&radio='.h($radio));
	}
	//ここまでに別処理がなければ通常ページ
	if($page<0){//負の値の時はトップページにリダイレクト
		redirect('./');
	}
	redirect('./?page='.h($page));
}
//非同期通信の時にpaintcom()を呼び出すためのリダイレクト
function location_paintcom(): void {
	redirect('./?mode=paintcom');
}
//リダイレクト
function redirect($url): void {
	header("Location: {$url}");
	exit();
}
// コンティニュー認証
function check_cont_pass(): void {

	global $en;

	check_same_origin();

	$adminmode = adminpost_valid() || admindel_valid(); 

	$no = (string)filter_input_data('POST', 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input_data('POST', 'time');//intの範囲外
	$pwd=t(filter_input_data('POST', 'pwd'));//パスワードを取得
	$pwd=$pwd ? $pwd : t(filter_input_data('COOKIE','pwdc'));//未入力ならCookieのパスワード
	$flag = false;
	if(!is_file(LOG_DIR."$no.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."$no.log","r");
	if(!$rp){
		error($en?'This operation has failed.':'失敗しました。');
	}
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		if(strpos($line,"\t".$id."\t")!==false){
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no && ($adminmode && $verified ==='adminpost' || $pwd && password_verify($pwd,$hash))){
				$flag = true;
				break;
			}
			break;
		}
	}
	closeFile ($rp);
	if(!$flag){
		error($en?'password is wrong.':'パスワードが違います。');
	}
}

//コンティニュー前画面のペイントツールを選択可能に
function set_app_select_enabled_session() : void {
	session_sta();
	$_SESSION['enableappselect'] = true;
}

//設定済みのペイントツール名かどうか調べる
function is_paint_tool_name($tool): string {
	return in_array($tool,['neo','chi','klecks','tegaki','axnos']) ? $tool : '???';
}

//ログ出力の前処理 行から情報を取り出す
function create_res($line,$options=[]): array {
	global $root_url,$boardname,$do_not_change_posts_time,$en,$mark_sensitive_image,$set_all_images_to_nsfw;
	list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$paintsec,$log_hash_img,$abbr_toolname,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;

	$time = basename($time);

	$isset_catalog = isset($options['catalog']);
	$isset_search = isset($options['search']);
	$res=[];

	$continue = true;
	$upload_image = false;
	$tool=switch_tool($abbr_toolname);

	if($abbr_toolname==='upload'){
		$continue = false;
		$upload_image = true;
	}

	$anime = $pchext ? in_array($pchext,['.pch','.tgkr']) : false; 
	$hide_thumbnail = $mark_sensitive_image ? (strpos($thumbnail,'hide_')!==false) :'';
	$hide_thumbnail = $set_all_images_to_nsfw ? $set_all_images_to_nsfw : $hide_thumbnail;

	$_w=$w;
	$_h=$h;
	if($hide_thumbnail){
		list($w,$h)=image_reduction_display($w,$h,300,300);
	}
	$thumbnail_webp = ((strpos($thumbnail,'thumbnail_webp')!==false)) ? $time.'s.webp' : false; 
	$thumbnail_jpg = (!$thumbnail_webp && strpos($thumbnail,'thumbnail')!==false) ? $time.'s.jpg' : false; 

	$thumbnail_img = $thumbnail_webp ? $thumbnail_webp : $thumbnail_jpg;

	$link_thumbnail= ($thumbnail_img || $hide_thumbnail); 
	$painttime = !$isset_catalog ? calcPtime($paintsec) : false;

	$datetime = $do_not_change_posts_time ? microtime2time($first_posted_time) : microtime2time($time);
	$date=$datetime ? date('y/m/d',(int)$datetime):'';

	$check_elapsed_days = !$isset_catalog ? check_elapsed_days($time) : true;//念のためtrueに
	$verified = ($verified==='adminpost');
	$three_point_sub = ($isset_catalog && (mb_strlen($sub)>15)) ? '…' :'';
	$webpimg = $imgfile ? is_file('webp/'.$time.'t.webp') : false;
	$com = (!$isset_catalog || $isset_search) ? $com : '';
	$com = $com ? (!$isset_search ? str_replace('"\n"',"\n",$com) : str_replace('"\n"'," ",$com)) : '';

	$res=[
		'no' => $no,
		'sub' => $sub,
		'substr_sub' => $isset_catalog ? mb_substr($sub,0,15).$three_point_sub : $sub,
		'name' => $name,
		'verified' => $verified,
		'com' => $com,
		'url' => $url ? filter_var($url,FILTER_VALIDATE_URL) : '',
		'img' => $imgfile,
		'thumbnail' => $thumbnail_img,//webp or jpegのサムネイルのファイル名
		'painttime' => $painttime ? $painttime['ja'] : '',
		'painttime_en' => $painttime ? $painttime['en'] : '',
		'paintsec' => $paintsec,
		'w' => ($w && is_numeric($w)) ? $w :'',
		'h' => ($h && is_numeric($h)) ? $h :'',
		'_w' => ($_w && is_numeric($_w)) ? $_w :'',
		'_h' => ($_h && is_numeric($_h)) ? $_h :'',
		'tool' => $tool,
		'abbr_toolname' => $abbr_toolname,
		'upload_image' => $upload_image,
		'pchext' => $pchext,
		'anime' => $anime,
		'continue' => $check_elapsed_days ? $continue : (adminpost_valid() ? $continue : false),
		'time' => $time,
		'date' => $date,
		'datetime' => $datetime,
		'host' => admindel_valid() ? $host : '',
		'userid' => $userid,
		'check_elapsed_days' => $check_elapsed_days,
		'encoded_boardname' => $isset_catalog ? urlencode($boardname) : '',
		'encoded_name' => (!$isset_catalog || $isset_search) ? urlencode($name) : '',
		'encoded_no' => !$isset_catalog ? urlencode('['.$no.']') : '',
		'encoded_sub' => !$isset_catalog ? urlencode($sub) : '',
		'encoded_u' => !$isset_catalog ? urlencode($root_url.'?resno='.$no) : '',//tweet
		'encoded_t' => !$isset_catalog ? urlencode('['.$no.']'.$sub.($name ? ' by '.$name : '').' - '.$boardname) : '',
		'oya' => $oya,
		'webpimg' => $webpimg ? 'webp/'.$time.'t.webp' :false,
		'hide_thumbnail' => $hide_thumbnail, //サムネイルにぼかしをかける時
		'link_thumbnail' => $link_thumbnail, //サムネイルにリンクがある時
		'not_deleted' => !(!$name && !$com && !$url&& !$imgfile && !$userid), //表示する記事がある親
	];

	foreach($res as $key=>$val){
		$res[$key]=h($val);
	}

	return $res;
}

function switch_tool($tool): string {
	global $en;
	switch($tool){
		case 'neo':
			$tool='PaintBBS NEO';
			break;
		case 'PaintBBS':
			$tool='PaintBBS';
			break;
		case 'shi-Painter':
			$tool='Shi-Painter';
			break;
		case 'chi':
			$tool='ChickenPaint';
			break;
		case 'klecks';
			$tool='Klecks';
			break;
		case 'tegaki';
			$tool='Tegaki';
			break;
		case 'axnos';
			$tool='Axnos Paint';
			break;
		case 'upload':
			$tool=$en?'Upload':'アップロード';
			break;
		default:
			$tool='';
			break;
	}
	return $tool;
}

//重複チェックのための配列を全体ログを元に作成
function create_chk_lins($chk_log_arr,$resno): array {

	$chk_resnos=[];
	foreach($chk_log_arr as $chk_log){
		list($chk_resno)=explode("\t",$chk_log,2);
		$chk_resnos[]=$chk_resno;
	}
	$chk_lines=[];
	//条件分岐で新規投稿に変更になった時のエラー回避
	foreach($chk_resnos as $chk_resno){
		//$resnoのログファイルは開かない
		if(($chk_resno!==$resno)&&is_file(LOG_DIR."{$chk_resno}.log")){
			check_open_no($chk_resno);
			$cp=fopen(LOG_DIR."{$chk_resno}.log","r");
			while($line=fgets($cp)){
				if(!trim($line)){
					continue;
				}
				$chk_lines[]=$line;
			}
			closefile($cp);
		}
	}
	return $chk_lines;
}

//ファイル名が重複しない投稿時刻を作成
function create_post_time(): string {
	$time = (string)(time().substr(microtime(),2,6));	//投稿時刻
	//画像重複チェック
	$testexts=['.gif','.jpg','.png','.webp'];
	foreach($testexts as $testext){
		if(is_file(IMG_DIR.$time.$testext)){
			$time=(string)(substr($time,0,-6)+1).(string)substr($time,-6);
		break;	
		}
	}
	//一時ファイル重複チェック
	$time= is_file(TEMP_DIR.$time.'.tmp') ?	(string)(substr($time,0,-6)+1).(string)substr($time,-6) : $time;
	$time=basename($time);
	return $time;
}

//ログファイルを1行ずつ読み込んで配列に入れる
function create_array_from_fp($fp): array {
	global $en;
	if(!$fp){
		error($en?'This operation has failed.':'失敗しました。');
	}
	// ファイルポインタを先頭に移動
	rewind($fp);
	$arr=[];
	while ($lines = fgets($fp)) {
		if(!trim($lines)){
			continue;
		}
		$arr[]=$lines;
	}
	return $arr;
}

//ページング
function calc_pagination_range($page,$pagedef): array {

	$start_page=$page-$pagedef*8;
	$end_page=$page+($pagedef*8);
	if($page<$pagedef*17){
		$start_page=0;
		$end_page=$pagedef*17;
	}
	return [$start_page,$end_page];	
}	

//ユーザーip
function get_uip(): string {
	$ip = $_SERVER["HTTP_CLIENT_IP"] ?? '';
	$ip = $ip ? $ip : ($_SERVER["HTTP_INCAP_CLIENT_IP"] ?? '');
	$ip = $ip ? $ip : ($_SERVER["HTTP_X_FORWARDED_FOR"] ?? '');
	$ip = $ip ? $ip : ($_SERVER["REMOTE_ADDR"] ?? '');
	if (strstr($ip, ', ')) {
		$ips = explode(', ', $ip);
		$ip = $ips[0];
	}
	return $ip;
}

//タブ除去
function t($str): string {
	if(zero_check($str)){
		return '0';
	}
	if(!$str){
		return '';
	}
	return str_replace("\t","",(string)$str);
}
//タグ除去
function s($str): string {
	if(zero_check($str)){
		return '0';
	}
	if(!$str){
		return '';
	}
	return strip_tags((string)$str);
}
//エスケープ
function h($str) :string{
	if(zero_check($str)){
		return '0';
	}
	if(!$str){
		return '';
	}
	return htmlspecialchars($str,ENT_QUOTES,"utf-8",false);
}
// 0 または "0" かどうか
function zero_check($str): bool {
	return($str === 0 || $str === '0');
}
//コメント出力
function com($str,$verified=false): string {
	global $use_autolink;

	if(!$str){
		return '';
	}

	if($use_autolink){
	$str=md_link($str,$verified);
	$str=auto_link($str,$verified);
	}
	return nl2br($str,false);
}

//マークダウン記法のリンクをHTMLに変換
function md_link($str, $verified = false): string {
	$rel = $verified ? 'rel="noopener noreferrer"' : 'rel="nofollow noopener noreferrer"';

	// 正規表現パターンを使用してマークダウンリンクを検出
	$pattern = "{\[((?:[^\[\]\\\\]|\\\\.)+?)\]\((https?://[^\s\)]+)\)}";

	// 変換処理
	$str = preg_replace_callback($pattern, function($matches) use ($rel) {
			// エスケープされたバックスラッシュを特定の文字だけ解除
			$text = str_replace(['\\[', '\\]', '\\(', '\\)'], ['[', ']', '(', ')'], $matches[1]);
			$url = filter_var($matches[2], FILTER_VALIDATE_URL) ? $matches[2] : '';
			// 変換されたHTMLリンクを返す
			if(!$url){
				 // URLが無効ならテキストだけ返す
				return $text;
			}
			// URLが有効ならHTMLリンクを返す
			return '<a href="'.$url.'" target="_blank" '.$rel.'>'.$text.'</a>';
	}, $str);

	return $str;
}

// 自動リンク
function auto_link($str, $verified = false): string {
	if(strpos($str, '<a') === false){ // マークダウン記法がなかった時
		if($verified){
			$rel = 'rel="noopener noreferrer"';
		}else{
			$rel = 'rel="nofollow noopener noreferrer"';
		}
		$str= preg_replace("{(https?://[\w!\?/\+\-_~=;:\.,\*&@#\$%\(\)'\[\]]+)}",'<a href="$1" target="_blank" '.$rel.'>$1</a>',$str);
	}
		return $str;
}

//mime typeを取得して拡張子を返す
function get_image_type ($img_file): string {

	$img_type = mime_content_type($img_file);

	switch ($img_type) {
		case "image/gif" : return ".gif";
		case "image/jpeg" : return ".jpg";
		case "image/png" : return ".png";
		case "image/webp" : return ".webp";
		default : return '';
	}
}
//ファイルがあれば削除
function safe_unlink ($path): void {
	if ($path && is_file($path)) {
		unlink($path);
	}
}
/**
 * 一連の画像ファイルを削除（元画像、サムネ、動画）
 * @param $path
 * @param $filename
 * @param $ext
 */
function delete_files ($imgfile, $time): void {

	$imgfile=basename($imgfile);
	$time=basename($time);
	safe_unlink(IMG_DIR.$imgfile);
	safe_unlink(THUMB_DIR.$time.'s.jpg');
	safe_unlink(THUMB_DIR.$time.'s.webp');
	safe_unlink('webp/'.$time.'t.webp');
	safe_unlink(IMG_DIR.$time.'.pch');
	safe_unlink(IMG_DIR.$time.'.spch');
	safe_unlink(IMG_DIR.$time.'.chi');
	safe_unlink(IMG_DIR.$time.'.psd');
	safe_unlink(IMG_DIR.$time.'.tgkr');
	delete_res_cache();
}

function delete_res_cache (): void {
	safe_unlink(__DIR__.'/template/cache/index_cache.json');
}

//pngをwebpに変換してみてファイル容量が小さくなっていたら元のファイルを上書き
function convert_andsave_if_smaller_png2webp($is_upload_img,$fname,$time): void {
	global $max_kb,$max_file_size_in_png_format_paint,$max_file_size_in_png_format_upload;
	$upfile=TEMP_DIR.basename($fname);

	clearstatcache();
	$filesize=filesize($upfile);
	$max_kb_size_over = ($filesize > ($max_kb * 1024));
	if(mime_content_type($upfile)!=="image/png" && !$max_kb_size_over){
		return;//ファイルサイズが$max_kbを超えている時は形式にかかわらず処理続行
	}
	if(((!$is_upload_img && $filesize < ($max_file_size_in_png_format_paint * 1024))||	
	($is_upload_img && $filesize < ($max_file_size_in_png_format_upload * 1024))) && !$max_kb_size_over){
		return;
	}
	//webp作成が可能ならwebpに、でなければjpegに変換する。
	$im_webp = thumbnail_gd::thumb(TEMP_DIR,$fname,$time,null,null,['png2webp'=>true]);

	if($im_webp){
		clearstatcache();
		if(filesize($im_webp)<$filesize){//webpのほうが小さい時だけ
			rename($im_webp,$upfile);//webpで保存
			chmod($upfile,0606);
		} else{//pngよりファイルサイズが大きくなる時は
			unlink($im_webp);//作成したwebp画像を削除
		}
	}
}

//Exifをチェックして画像が回転している時と位置情報が付いている時は上書き保存
function check_jpeg_exif($upfile): void {
	global $max_px;

	if((exif_imagetype($upfile) !== IMAGETYPE_JPEG ) || !function_exists("imagecreatefromjpeg")){
		return;
	}

	//画像回転の検出
	$exif = exif_read_data($upfile);
	$orientation = $exif["Orientation"] ?? 1;
	//位置情報はあるか?
	$gpsdata_exists =(isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])); 

	if ($orientation === 1 && !$gpsdata_exists) {
		//画像が回転していない、位置情報も存在しない
		return;
	}

	list($w,$h) = getimagesize($upfile);

	$im_in = imagecreatefromjpeg($upfile);
	if(!$im_in){
		return;
	}
	switch ($orientation) {
		case 3:
			$im_in = imagerotate($im_in, 180, 0);
			break;
		case 6:
			$im_in = imagerotate($im_in, -90, 0);
			break;
		case 8:
			$im_in = imagerotate($im_in, 90, 0);
			break;
		default:
			break;
	}
	if(!$im_in){
		return;
	}
	if ($orientation === 6 || $orientation === 8) {
		// 90度または270度回転の場合、幅と高さを入れ替える
		list($w, $h) = [$h, $w];
	}
	$w_ratio = $max_px / $w;
	$h_ratio = $max_px / $h;
	$ratio = min($w_ratio, $h_ratio);
	$out_w = ceil($w * $ratio);//端数の切り上げ
	$out_h = ceil($h * $ratio);
	$im_out = $im_in;//縮小しない時
	//JPEG形式で何度も保存しなおすのを回避するため、
	//指定範囲内にリサイズしておく。
	if(function_exists("ImageCreateTrueColor") && function_exists("ImageCopyResampled")){
		$im_out = ImageCreateTrueColor($out_w, $out_h);
		ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $w, $h);
	}
	// 画像を保存
	imagejpeg($im_out, $upfile,98);
	// 画像のメモリを解放
	imagedestroy($im_in);
	imagedestroy($im_out);
}

//サムネイル作成
function make_thumbnail($imgfile,$time,$max_w,$max_h): string {
	global $use_thumb; 
	$thumbnail='';
	if($use_thumb){//スレッドの画像のサムネイルを使う時
		if(thumbnail_gd::thumb(IMG_DIR,$imgfile,$time,$max_w,$max_h,['thumbnail_webp'=>true])){
			$thumbnail='thumbnail_webp';
		}
		//webpのサムネイルが作成できなかった時はjpegのサムネイルを作る
		if(!$thumbnail && thumbnail_gd::thumb(IMG_DIR,$imgfile,$time,$max_w,$max_h)){
			$thumbnail='thumbnail';
		}
	}
	//カタログ用webpサムネイル 
	thumbnail_gd::thumb(IMG_DIR,$imgfile,$time,300,800,['webp'=>true]);

	return $thumbnail;
}

//アップロード画像のファイルサイズが大きすぎる時は削除
function delete_file_if_sizeexceeds($upfile,$fp,$rp): void {
	global $max_kb,$en;
	clearstatcache();
	if(filesize($upfile) > $max_kb*1024){
		closeFile($fp);
		closeFile($rp);
		safe_unlink($upfile);
		error($en? "Upload failed.\nFile size exceeds {$max_kb}kb.":"アップロードに失敗しました。\nファイル容量が{$max_kb}kbを超えています。");
	}
}

function error($str,$historyback=true): void {

	global $boardname,$skindir,$en,$aikotoba_required_to_view,$petit_lot;

	$petit_lot = $petit_lot ?? time();

	$asyncflag = (bool)filter_input_data('POST','asyncflag',FILTER_VALIDATE_BOOLEAN);
	$http_x_requested_with= (bool)(isset($_SERVER['HTTP_X_REQUESTED_WITH']));
	if($http_x_requested_with||$asyncflag){
		header('Content-type: text/plain');
		die(h("error\n{$str}"));
	}
	$boardname = ($aikotoba_required_to_view && !aikotoba_valid()) ? '' : $boardname; 

	$admin_pass= null;
	$templete='error.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}
//csrfトークンを作成
function get_csrf_token(): string {
	session_sta();
	$token=hash('sha256', session_id(), false);
	$_SESSION['token']=$token;

	return $token;
}
//csrfトークンをチェック	
function check_csrf_token(): void {
	global $en;

	if(($_SERVER["REQUEST_METHOD"]) !== "POST"){
		error($en?'This operation has failed.':'失敗しました。');
	} 
	check_same_origin();
	session_sta();
	$token=(string)filter_input_data('POST','token');
	$session_token=isset($_SESSION['token']) ? (string)$_SESSION['token'] : '';

	if(!$token||!$session_token||!hash_equals($session_token,$token)){//タイミング攻撃対策としてhash_equals()を使用
		error($en?"CSRF token mismatch.\nPlease reload.":"CSRFトークンが一致しません。\nリロードしてください。");
	}
}
//session開始
function session_sta(): void {
	global $session_name;

	$session_name = $session_name ?? 'session_petit';
	$httpsonly = (bool)($_SERVER['HTTPS'] ?? '');

	if(!isset($_SESSION)){
		ini_set('session.use_strict_mode', 1);
		session_set_cookie_params(
			0,"","",$httpsonly,true
		);
		session_name($session_name);
		session_start();
		header('Expires:');
		header('Cache-Control:');
		header('Pragma:');
	}
}

function check_same_origin(): void {
	global $en,$usercode;

	session_sta();
	$c_usercode = t(filter_input_data('COOKIE', 'usercode'));//user-codeを取得
	$session_usercode = isset($_SESSION['usercode']) ? t($_SESSION['usercode']) : "";
	if(!$c_usercode){
		error($en?'Cookie check failed.':'Cookieが確認できません。');
	}
	if(!$usercode || ($usercode!==$c_usercode)&&($usercode!==$session_usercode)){
		error($en?"User code mismatch.":"ユーザーコードが一致しません。");
	}
	if(!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_HOST'])){
		error($en?'Your browser is not supported. ':'お使いのブラウザはサポートされていません。');
	}
	if(parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']){
		error($en?"The post has been rejected.":'拒絶されました。');
	}
}

function check_open_no($no): void {
	global $en;
	$no=(string)$no;
	if(!ctype_digit($no)||$no !== basename($no)){
		error($en?'This operation has failed.':'失敗しました。');
	}
}

function getId ($userip): string {

	session_sta();
	return 
	(isset($_SESSION['userid'])&&$_SESSION['userid']) ?
	$_SESSION['userid'] :
	substr(hash('sha256', $userip, false),-8);

}

//Asyncリクエストの時は処理を中断
function check_AsyncRequest($upfile=''): void {
	//ヘッダーが確認できなかった時の保険
	$asyncflag = (bool)filter_input_data('POST','asyncflag',FILTER_VALIDATE_BOOLEAN);
	$paint_picrep = (bool)filter_input_data('POST','paint_picrep',FILTER_VALIDATE_BOOLEAN);
	$http_x_requested_with= (bool)(isset($_SERVER['HTTP_X_REQUESTED_WITH']));
	//Paintの画像差し換えの時はAsyncリクエストを継続
	if(!$paint_picrep && ($http_x_requested_with || $asyncflag)){//非同期通信ならエラーチェックだけすませて処理中断。通常フォームでやりなおし。
		safe_unlink($upfile);
		exit();
	}
}

//POSTがJavaScript経由かチェック
function check_post_via_javascript(): void {
	global $en;
	//JavaScriptが無効な時はエラー
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH'])){//asyncリクエストでなければ
		error($en?'Please enable JavaScript.':'JavaScriptを有効にしてください。');
	}
}

// テンポラリ内のゴミ除去 
function deltemp(): void {
	global $check_password_input_error_count;
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir(TEMP_DIR.$file) && is_file(TEMP_DIR.$file)){
			$file=basename($file);
			//pchアップロードペイントファイル削除
			//仮差し換えアップロードファイル削除
			$lapse = time() - filemtime(TEMP_DIR.$file);
			if(strpos($file,'pchup-')===0){
				if($lapse > (300)){//5分
					safe_unlink(TEMP_DIR.$file);
				}
			}else{
				if($lapse > (3*24*3600)){//3日
					safe_unlink(TEMP_DIR.$file);
				}
			}
		}
	}
	closedir($handle);
	$_file=__DIR__.'/template/errorlog/error.log';
	if(!$check_password_input_error_count){
		safe_unlink($_file);
	}
	if(is_file($_file)){
		$lapse = time() - filemtime($_file);
		if($lapse > (3*24*3600)){//3日
			safe_unlink($_file);
		}
	}
}

// NGワードがあれば拒絶
function Reject_if_NGword_exists_in_the_post(): void {
	global $use_japanesefilter,$badstring,$badname,$badurl,$badstr_A,$badstr_B,$allow_comments_url,$max_com,$en;

	$admin =(adminpost_valid()||admindel_valid());

	$name = t(filter_input_data('POST','name'));
	$sub = t(filter_input_data('POST','sub'));
	$url = t(filter_input_data('POST','url',FILTER_VALIDATE_URL));
	$com = t(filter_input_data('POST','com'));
	$pwd = t(filter_input_data('POST','pwd'));

	if($admin || is_adminpass($pwd)){
		return;
	}
	if(is_badhost()){
		error($en?'Post was rejected.':'拒絶されました。');
	}

	$com_len=strlen((string)$com);
	$name_len=strlen((string)$name);
	$sub_len=strlen((string)$sub);
	$url_len=strlen((string)$url);
	$pwd_len=strlen((string)$pwd);

	if($name_len > 30) error($en?'Name is too long':'名前が長すぎます。');
	if($sub_len > 80) error($en? 'Subject is too long.':'題名が長すぎます。');
	if($url_len > 100) error($en? 'URL is too long.':'URLが長すぎます。');
	if($com_len > $max_com) error($en? 'Comment is too long.':'本文が長すぎます。');
	if($pwd_len > 100) error($en? 'Password is too long.':'パスワードが長すぎます。');

	//チェックする項目から改行・スペース・タブを消す
	$chk_name = $name_len ? preg_replace("/\s/u", "", $name ) : '';
	$chk_sub = $sub_len ? preg_replace("/\s/u", "", $sub ) : '';
	$chk_url = $url_len ? preg_replace("/\s/u", "", $url ) : '';
	$chk_com  = $com_len ? preg_replace("/\s/u", "", $com ) : '';

	//本文に日本語がなければ拒絶
	if ($use_japanesefilter) {
		mb_regex_encoding("UTF-8");
		if ($com_len && !preg_match("/[ぁ-んァ-ヶｧ-ﾝー一-龠]+/u",$chk_com)) error($en?'Comment should have at least some Japanese characters.':'日本語で何か書いてください。');
	}

	//本文へのURLの書き込みを禁止
	if(!$allow_comments_url){
		if($com_len && preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) error($en?'This URL can not be used in text.':'URLの記入はできません。');
	}

	// 使えない文字チェック
	if (is_ngword($badstring, [$chk_name,$chk_sub,$chk_url,$chk_com])) {
		error($en?'There is an inappropriate string.':'不適切な表現があります。');
	}

	// 使えない名前チェック
	if (is_ngword($badname, $chk_name)) {
		error($en?'This name cannot be used.':'この名前は使えません。');
	}
	// 使えないurlチェック
	if (is_ngword($badurl, $chk_url)) {
		error($en?'There is an inappropriate URL.':'不適切なURLがあります。');
	}

	//指定文字列が2つあると拒絶
	$bstr_A_find = is_ngword($badstr_A, [$chk_com, $chk_sub, $chk_name]);
	$bstr_B_find = is_ngword($badstr_B, [$chk_com, $chk_sub, $chk_name]);
	if($bstr_A_find && $bstr_B_find){
		error($en?'There is an inappropriate string.':'不適切な表現があります。');
	}
}
/**
 * NGワードチェック
 * @param $ngwords
 * @param string|array $strs
 * @return bool
 */
function is_ngword ($ngwords, $strs): bool {
	if (empty($ngwords)||empty($strs)) {
		return false;
	}
	$strs = (array)$strs;//配列に変換
	foreach($ngwords as $i => $ngword){//拒絶する文字列
		$ngwords[$i]  = str_replace([" ", "　"], "", $ngword);
		$ngwords[$i]  = str_replace("/", "\/", $ngwords[$i]);
	}
	foreach ($strs as $str) {
		foreach($ngwords as $ngword){//拒絶する文字列
			if ($ngword && preg_match("/{$ngword}/ui", $str)){
				return true;
			}
		}
	}
	return false;
}

/* 禁止ホストチェック */
function is_badhost(): bool {
	global $badhost;
	//ホスト取得
	$userip = get_uip();
	$host = $userip ? gethostbyaddr($userip) :'';

	if($host === $userip){//ホスト名がipアドレスになる場合は
		foreach($badhost as $value){
			if (preg_match("/\A$value/i",$host)) {//前方一致
				return true;
			}
		}
		return false;
	}else{
		foreach($badhost as $value){
			if (preg_match("/$value\z/i",$host)) {
				return true;
			}
		}
		return false;
	}
}

//初期化
function init(): void {
	
	check_dir(__DIR__."/src");
	check_dir(__DIR__."/temp");
	check_dir(__DIR__."/thumbnail");
	check_dir(__DIR__."/log");
	check_dir(__DIR__."/webp");
	check_dir(__DIR__."/template/cache");
	if(!is_file(LOG_DIR.'alllog.log')){
	file_put_contents(LOG_DIR.'alllog.log','',FILE_APPEND|LOCK_EX);
	chmod(LOG_DIR.'alllog.log',0600);	
	}
}

//ディレクトリ作成
function check_dir ($path): void {

	$msg=initial_error_message();

	if (!is_dir($path)) {
			mkdir($path, 0707);
			chmod($path, 0707);
	}
	if (!is_readable($path) || !is_writable($path)){
		chmod($path, 0707);
	}
	if (!is_dir($path)){
		die(h($path) . $msg['001']);
	}
	if (!is_readable($path)){
		die(h($path) . $msg['002']);
	} 
	if (!is_writable($path)){
		die(h($path) . $msg['003']);
	} 
}

// ファイル存在チェック
function check_file ($path): void {
	$msg=initial_error_message();

	if (!is_file($path)){
		die(h($path) . $msg['001']);
	} 
	if (!is_readable($path)){
		die(h($path) . $msg['002']);
	} 
}
function initial_error_message(): array {
	global $en;
	$msg['001']=$en ? ' does not exist.':'がありません。'; 
	$msg['002']=$en ? ' is not readable.':'を読めません。'; 
	$msg['003']=$en ? ' is not writable.':'を書けません。'; 
return $msg;	
}

// 一括書き込み（上書き）
function writeFile ($fp, $data): void {
	global $en;
	if($data === ''){
		closeFile($fp);
		error($en ? 'Log write failed.' : 'ログの書き込みに失敗しました。');
	}
	ftruncate($fp,0);
	rewind($fp);
	stream_set_write_buffer($fp, 0);
	fwrite($fp, $data);
}
//fpクローズ
function closeFile ($fp): void {
	if($fp){
		fflush($fp);
		file_lock($fp, LOCK_UN);
		fclose($fp);
	}
}

//縮小表示
function image_reduction_display($w,$h,$max_w,$max_h): array {
	if(!is_numeric($w)||!is_numeric($h)){
		return ['',''];
	}

	if ($w > $max_w || $h > $max_h) {
		$w_ratio = $max_w / $w;
		$h_ratio = $max_h / $h;
		$ratio = min($w_ratio, $h_ratio);
		$w = ceil($w * $ratio);
		$h = ceil($h * $ratio);
	}
	$reduced_size = [$w,$h];
	return $reduced_size;
}
/**
 * 描画時間を計算
 * @param $psec
 * @return array
 */
function calcPtime ($psec): ?array {

	if(!is_numeric($psec)){
		return null;
	}

	$D = floor($psec / 86400);
	$H = floor($psec % 86400 / 3600);
	$M = floor($psec % 3600 / 60);
	$S = $psec % 60;

	$en_day = ($D>1) ? ' days ' : ' day ';
	$en_hour = ($H>1) ? ' hours ' : ' hour '; 
	$result=[
		'ja'=>
			($D ? $D.'日' : '')
			. (($D||$H) ? $H.'時間' : '')
			. (($D||$H||$M) ? $M.'分' : '')
			. $S.'秒'
			,
		'en'=>
			($D ? $D.$en_day : '')
			. (($D||$H) ? $H.$en_hour : '')
			. (($D||$H||$M) ? $M.' min ' : '')
			. $S.' sec'
		];
	return $result;
}
/**
 * 残り時間を計算
 * @param $sec
 * @return string
 */
function calc_remaining_time_to_close_thread ($sec): string {
	global $en;

	$D = floor($sec / 86400);
	$H = floor($sec % 86400 / 3600);
	$M = floor($sec % 3600 / 60);

	if($D){
		$day = ($D>1) ? ' days' : ' day';
		$day = $en ? $day : '日';

		return (int)$D.$day;
	}
	if($H){
		$hour = ($H>1) ? ' hours' : ' hour'; 
		$hour = $en ? $hour : '時間';

		return  (int)$H.$hour;
	}
	$min = $en ? ' min' : '分';

	return  (int)$M.$min;
}

/**
 * pchかtgkrかchiかpsdか、それともファイルが存在しないかチェック
 * @param $filepath
 * @return string
 */
function check_pch_ext ($filepath,$options = []): string {
	
	$exts=[".pch",".tgkr",".chi",".psd"];

	foreach($exts as $i => $ext){

		if (is_file($filepath . $ext)) {
			if(!in_array(mime_content_type($filepath . $ext),["application/octet-stream","image/vnd.adobe.photoshop"])){
				return '';
			}
			return $ext;
		}
		if(!isset($options['upload']) && $i === 1){
			return '';
		}
	}
	return '';
}

// 古いスレッドへの投稿を許可するかどうか
function check_elapsed_days ($postedtime): bool {
	global $elapsed_days;
	$postedtime=microtime2time($postedtime);//マイクロ秒を秒に戻す
	return $elapsed_days //古いスレッドのフォームを閉じる日数が設定されていたら
		? ((time() - (int)$postedtime) <= ((int)$elapsed_days * 86400)) // 指定日数以内なら許可
		: true; // フォームを閉じる日数が未設定なら許可
}
// スレッドを閉じるまでの残り時間
function time_left_to_close_the_thread ($postedtime): string {
	global $elapsed_days;
	if(!$elapsed_days){
		return '';
	}
	$postedtime=microtime2time($postedtime);//マイクロ秒を秒に戻す
	$timeleft=((int)$elapsed_days * 86400)-(time() - (int)$postedtime);
	//残り時間が60日を切ったら表示
	return ($timeleft<(60 * 86400)) ? 
	calc_remaining_time_to_close_thread($timeleft) : '';
}	
// マイクロ秒を秒に戻す
function microtime2time($microtime): int {
	$microtime=(string)$microtime;
	$time=(strlen($microtime)>15) ? substr($microtime,0,-6) : substr($microtime,0,-3);
	return (int)$time;
}

//POSTされた値をログファイルに格納する書式にフォーマット
function create_formatted_text_from_post($name,$sub,$url,$com): array {
	global $en,$name_input_required,$subject_input_required,$comment_input_required;

	if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
	if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
	if(!$url||!(string)filter_var($url,FILTER_VALIDATE_URL)||!preg_match('{\Ahttps?://}', $url)||preg_match("/\A\s*\z/u",$url)) $url="";
	if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
	$com=str_replace(["\r\n","\r"],"\n",$com);
	$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット
	$com=str_replace("\n",'"\n"',$com);
	if(!$name){
		if($name_input_required){
			error($en?'Please enter your name.':'名前がありません。');
		}else{
			$name='anonymous';
		}
	}
	if(!$sub){
		if($subject_input_required){
			error($en?'Please enter subject.':'題名がありません。');
		}else{
			$sub= $en ? 'No subject':'無題';
		}
	}
	if(!$com && $comment_input_required){
		error($en?'Please enter your comment.':'何か書いてください。');
	}
	$formatted_post=[
		'name'=>$name,
		'sub'=>$sub,
		'url'=>$url,
		'com'=>$com,
	];
	foreach($formatted_post as $key => $val){
		$formatted_post[$key]=str_replace(["\r\n","\n","\r","\t"],"",$val);//改行コード一括除去
	}
	return $formatted_post;

}

//PaintBBS NEOのpchかどうか調べる
function is_neo($src): bool {
	$fp = fopen("$src", "rb");
	$is_neo=(fread($fp,3)==="NEO");
	fclose($fp);
	return $is_neo;
}
//pchデータから幅と高さを取得
function get_pch_size($src): ?array {
	if(!$src){
		return null;
	}
	$fp = fopen("$src", "rb");
	$is_neo=(fread($fp,3)==="NEO");//ファイルポインタが3byte移動
	$pch_data=(string)bin2hex(fread($fp,5));
	fclose($fp);
	if(!$is_neo || !$pch_data){
		return null;
	}
	$width=null;
	$height=null;
	$w0=hexdec(substr($pch_data,2,2));
	$w1=hexdec(substr($pch_data,4,2));
	$h0=hexdec(substr($pch_data,6,2));
	$h1=hexdec(substr($pch_data,8,2));
	if(!is_numeric($w0)||!is_numeric($w1)||!is_numeric($h0)||!is_numeric($h1)){
		return null;
	}
	$width=(int)$w0+((int)$w1*256);
	$height=(int)$h0+((int)$h1*256);
	if(!$width||!$height){
		return null;
	}
	return[(int)$width,(int)$height];
}

//使用するペイントアプリの配列化
function app_to_use(): array {
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$use_tegaki,$use_axnos;
		$arr_apps=[];
		if($use_paintbbs_neo){
			$arr_apps[]='neo';
		}
		if($use_chickenpaint){
			$arr_apps[]='chi';
		}
		if($use_klecs){
			$arr_apps[]='klecks';
		}
		if($use_tegaki){
			$arr_apps[]='tegaki';
		}
		if($use_axnos){
			$arr_apps[]='axnos';
		}
		return $arr_apps;
	}

//パスワードを5回連続して間違えた時は拒絶
function check_password_input_error_count(): void {
	global $second_pass,$en,$check_password_input_error_count;
	if(!$check_password_input_error_count){
		return;
	}
	$file=__DIR__.'/template/errorlog/error.log';
	$userip = get_uip();
	check_dir(__DIR__.'/template/errorlog/');
	$arr_err=is_file($file) ? file($file):[];
	if(count($arr_err)>=5){
		error($en?'Rejected.':'拒絶されました。');
	}
	if(!is_adminpass(filter_input_data('POST','adminpass'))){

		$errlog=$userip."\n";
		file_put_contents($file,$errlog,FILE_APPEND);
		chmod($file,0600);
	}else{
			safe_unlink($file);
	}
}

// 優先言語のリストをチェックして対応する言語があればその翻訳されたレイヤー名を返す
function getTranslatedLayerName(): string {
	$acceptedLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
	$languageList = explode(',', $acceptedLanguages);

	foreach ($languageList as $language) {
		$language = strtolower(trim($language));
		if (strpos($language, 'ja') === 0) {
			return "レイヤー";
		}
		if (strpos($language, 'en') === 0) {
			return "Layer";
		}
		if (strpos($language, 'zh-tw') === 0) {
			return "圖層";
		}
		if (strpos($language, 'zh-cn') === 0) {
			return "图层";
		}
		if (strpos($language, 'ko') === 0) {
			return "레이어";
		}
		if (strpos($language, 'fr') === 0) {
			return "Calque";
		}
		if (strpos($language, 'de') === 0) {
			return "Ebene";
		}
	}

	return "Layer";
}
//SNSへ共有リンクを送信
function post_share_server(): void {
	global $en;

	$sns_server_radio=(string)filter_input_data('POST',"sns_server_radio",FILTER_VALIDATE_URL);
	$sns_server_radio_for_cookie=(string)filter_input_data('POST',"sns_server_radio");//directを判定するためurlでバリデーションしていない
	$sns_server_radio_for_cookie=($sns_server_radio_for_cookie === 'direct') ? 'direct' : $sns_server_radio;
	$sns_server_direct_input=(string)filter_input_data('POST',"sns_server_direct_input",FILTER_VALIDATE_URL);
	$encoded_t=(string)filter_input_data('POST',"encoded_t");
	$encoded_t=urlencode($encoded_t);
	$encoded_u=(string)filter_input_data('POST',"encoded_u");
	$encoded_u=urlencode($encoded_u);
	setcookie("sns_server_radio_cookie",$sns_server_radio_for_cookie, time()+(86400*30),"","",false,true);
	setcookie("sns_server_direct_input_cookie",$sns_server_direct_input, time()+(86400*30),"","",false,true);
	$share_url='';
	if($sns_server_radio){
		$share_url=$sns_server_radio."/share?text=";
	} elseif($sns_server_direct_input){//直接入力時
		$share_url=$sns_server_direct_input."/share?text=";
		if($sns_server_direct_input==="https://bsky.app"){
			$share_url="https://bsky.app/intent/compose?text=";
		} elseif($sns_server_direct_input==="https://www.threads.net"){
			$share_url="https://www.threads.net/intent/post?text=";
		}
	}
	if(in_array($sns_server_radio,["https://x.com","https://twitter.com"])){
		// $share_url="https://x.com/intent/post?text=";
		$share_url="https://twitter.com/intent/tweet?text=";
	} elseif($sns_server_radio === "https://bsky.app"){
		$share_url="https://bsky.app/intent/compose?text=";
	}	elseif($sns_server_radio === "https://www.threads.net"){
		$share_url="https://www.threads.net/intent/post?text=";
	}
	$share_url.=$encoded_t.'%20'.$encoded_u;
	$share_url = filter_var($share_url, FILTER_VALIDATE_URL) ? $share_url : ''; 
	if(!$share_url){
		error($en ? "Please select an SNS sharing destination.":"SNSの共有先を選択してください。");
	}
	redirect($share_url);
}
//flockのラッパー関数
function file_lock($fp, int $lock, array $options=[]): void {
	global $en;
	$flock=flock($fp, $lock);
	if (!$flock) {
			if($lock !== LOCK_UN){
				if(isset($options['paintcom'])){
					location_paintcom();//未投稿画像の投稿フォームへ
				}
				error($en ? 'Failed to lock the file.' : 'ファイルのロックに失敗しました。');
		}
	}
}
//filter_input のラッパー関数
function filter_input_data(string $input, string $key, int $filter=0) {
	// $_GETまたは$_POSTからデータを取得
	$value = null;
	if ($input === 'GET') {
			$value = $_GET[$key] ?? null;
	} elseif ($input === 'POST') {
			$value = $_POST[$key] ?? null;
	} elseif ($input === 'COOKIE') {
			$value = $_COOKIE[$key] ?? null;
	}

	// データが存在しない場合はnullを返す
	if ($value === null) {
			return null;
	}

	// フィルタリング処理
	switch ($filter) {
		case FILTER_VALIDATE_BOOLEAN:
			return  filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		case FILTER_VALIDATE_INT:
			return filter_var($value, FILTER_VALIDATE_INT);
		case FILTER_VALIDATE_URL:
			return filter_var($value, FILTER_VALIDATE_URL);
		default:
			return $value;  // 他のフィルタはそのまま返す
	}
}
