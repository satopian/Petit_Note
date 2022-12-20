<?php
$functions_ver=20221220;
//編集モードログアウト
function logout(){
	$resno=filter_input(INPUT_GET,'resno');
	session_sta();
	unset($_SESSION['admindel']);
	unset($_SESSION['userdel']);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	$page=filter_input(INPUT_POST,'page',FILTER_VALIDATE_INT);
	$page= $page ? $page : filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$page = $page ? $page : 0;

	return header('Location: ./?page='.$page);
}
//管理者モードログアウト
function logout_admin(){
	session_sta();
	unset($_SESSION['admindel']);
	unset($_SESSION['adminpost']);
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$page = $page ? $page : 0;
	$catalog=filter_input(INPUT_POST,'catalog',FILTER_VALIDATE_BOOLEAN);
	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);	
	}
	if($catalog){
		return header('Location: ./?mode=catalog&page='.$page);
	}

	return header('Location: ./?page='.$page);
}

//合言葉認証
function aikotoba(){
	global $aikotoba,$en;

	check_same_origin();

	session_sta();
	if(!$aikotoba || $aikotoba!==filter_input(INPUT_POST,'aikotoba')){
		if(isset($_SESSION['aikotoba'])){
			unset($_SESSION['aikotoba']);
		} 
		return error($en?'The secret words is wrong':'合言葉が違います。');
	}

	$_SESSION['aikotoba']='aikotoba';
	if(filter_input(INPUT_POST,'paintcom')){
		return header('Location: ./?mode=paintcom');
	}
	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$page = $page ? $page : 0;

	return header('Location: ./?page='.$page);
	
}
function admin_in(){

	global $boardname,$use_diary,$use_aikotoba,$petit_lot,$petit_ver,$skindir,$en,$latest_var;
	$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$resno=filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	$catalog=filter_input(INPUT_GET,'catalog',FILTER_VALIDATE_BOOLEAN);
	$catalog=$catalog ? 'on' : '';
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();
	if(!$use_aikotoba){
		$aikotoba=true;
	}
	// HTML出力
	$templete='admin_in.html';
	return include __DIR__.'/'.$skindir.$templete;
	
}
//合言葉を再確認	
function check_aikotoba(){
	global $en;
	if(!aikotoba_valid()){
		return error($en?'The secret words is wrong.':'合言葉が違います。');
	}
	return true;
}
//管理者投稿モード
function adminpost(){
	global $admin_pass,$second_pass,$en;

	check_same_origin();
	check_password_input_error_count();
	session_sta();
	if(!$admin_pass || !$second_pass || $admin_pass === $second_pass || $admin_pass!==filter_input(INPUT_POST,'adminpass')){
		if(isset($_SESSION['adminpost'])){
			unset($_SESSION['adminpost']);
		} 
		return error($en?'password is wrong.':'パスワードが違います。');
	}
	session_regenerate_id(true);
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	
	$page = isset($page) ? $page : 0;
	$catalog=filter_input(INPUT_POST,'catalog',FILTER_VALIDATE_BOOLEAN);

	$_SESSION['aikotoba']='aikotoba';
	$_SESSION['adminpost']=$second_pass;

	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	if($catalog){
		return header('Location: ./?mode=catalog&page='.$page);
	}
	
	return header('Location: ./?page='.$page);
}

//管理者削除モード
function admin_del(){
	global $admin_pass,$second_pass,$en;

	check_same_origin();
	check_password_input_error_count();

	session_sta();
	if(!$admin_pass || !$second_pass || $admin_pass === $second_pass || $admin_pass!==filter_input(INPUT_POST,'adminpass')){
		if(isset($_SESSION['admindel'])){
			unset($_SESSION['admindel']);
		} 
		return error($en?'password is wrong.':'パスワードが違います。');
	}
	session_regenerate_id(true);
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$page = isset($page) ? $page : 0;
	$catalog=filter_input(INPUT_POST,'catalog',FILTER_VALIDATE_BOOLEAN);

	$_SESSION['aikotoba']='aikotoba';
	
	$_SESSION['admindel']=$second_pass;

	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);

	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	if($catalog){
		return header('Location: ./?mode=catalog&page='.$page);
	}

	return header('Location: ./?page='.$page);
}
//ユーザー削除モード
function userdel_mode(){

	session_sta();

	$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$page = $page ? $page : 0;
	$_SESSION['userdel']='userdel_mode';
	$resno=filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	return header('Location: ./?page='.$page);
}

//sessionの確認
function adminpost_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['adminpost'])&&($second_pass && $_SESSION['adminpost']===$second_pass);
}
function admindel_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['admindel'])&&($second_pass && $_SESSION['admindel']===$second_pass);
}
function aikotoba_valid(){
	session_sta();
	return isset($_SESSION['aikotoba'])&&($_SESSION['aikotoba']==='aikotoba');
}

//センシティブコンテンツ
function view_nsfw(){

	$view=filter_input(INPUT_POST,'view_nsfw',FILTER_VALIDATE_BOOLEAN);
	if($view){
		setcookie("nsfwc",'on',time()+(60*60*24*30),"","",false,true);
	}

	$page=filter_input(INPUT_POST,'page',FILTER_VALIDATE_INT);
	$page = isset($page) ? $page : 0;
	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	$catalogpage=filter_input(INPUT_POST,'catalogpage',FILTER_VALIDATE_INT);
	if($catalogpage){
		return header('Location: ./?mode=catalog&page='.$catalogpage);
	}
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	return header('Location: ./?page='.$page);
}

// コンティニュー認証
function check_cont_pass(){

	global $en;

	check_same_origin();

	$no = (string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input(INPUT_POST, 'time');//intの範囲外
	$pwd=t(filter_input(INPUT_POST, 'pwd'));//パスワードを取得
	$pwd=$pwd ? $pwd : t(filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード


	if(is_file(LOG_DIR."$no.log")){
		check_open_no($no);
		$rp=fopen(LOG_DIR."$no.log","r");
		while ($line = fgets($rp)) {
			if(!trim($line)){
				continue;
			}
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no && $pwd && password_verify($pwd,$hash)){
				closeFile ($rp);
				return true;
			}
		}
		closeFile ($rp);
	}

	return error($en?'password is wrong.':'パスワードが違います。');
}

//ログ出力の前処理 行から情報を取り出す
function create_res($line){
	global $root_url,$boardname,$do_not_change_posts_time,$en,$mark_sensitive_image;
	list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;
	$res=[];

	$continue = true;
	$upload_image = false;

	switch($tool){
		case 'neo':
			$tool='PaintBBS NEO';
			break;
		case 'shi-Painter':
			$tool='shi-Painter';
			break;
		case 'chi':
			$tool='ChickenPaint';
			break;
		case 'klecks';
			$tool='Klecks';
			break;
		case 'upload':
			$tool=$en?'Upload':'アップロード';
			$continue = false;
			$upload_image = true;
			break;
		default:
			$tool='???';
			$continue = false;
			break;
	}

	$anime = ($pchext==='.pch') ? true : false; 
	$hide_thumbnail = $mark_sensitive_image ? ($thumbnail==='hide_thumbnail'||$thumbnail==='hide_') :'';

	$_w=$w;
	$_h=$h;
	if($hide_thumbnail){
	list($w,$h)=image_reduction_display($w,$h,300,300);
	}

	$thumbnail = ($thumbnail==='thumbnail'||$thumbnail==='hide_thumbnail') ? $time.'s.jpg' : false; 
	$link_thumbnail= ($thumbnail || $hide_thumbnail);  
	$painttime = is_numeric($painttime) ? calcPtime($painttime) : false;  
	$_time=(strlen($time)>15) ? substr($time,0,-6) : substr($time,0,-3);
	$first_posted_time=(strlen($first_posted_time)>15) ? substr($first_posted_time,0,-6) : substr($first_posted_time,0,-3);
	$datetime = $do_not_change_posts_time ? $first_posted_time : $_time;
	$date=$datetime ? date('y/m/d',(int)$datetime):'';

	$check_elapsed_days = check_elapsed_days($time);

	$verified = ($verified==='adminpost') ? true : false;
	$three_point_sub=(mb_strlen($sub)>15) ? '…' :'';

	$res=[
		'no' => $no,
		'sub' => $sub,
		'substr_sub' => mb_substr($sub,0,15).$three_point_sub,
		'name' => $name,
		'verified' => $verified,
		'com' => $com,
		'descriptioncom' => $com ? s(mb_strcut(str_replace('"\n"'," ",$com),0,300)) : '',
		'url' => $url ? filter_var($url,FILTER_VALIDATE_URL) : '',
		'img' => $imgfile,
		'thumbnail' => $thumbnail,
		'painttime' => $painttime,
		'w' => ($w && is_numeric($w)) ? $w :'',
		'h' => ($h && is_numeric($h)) ? $h :'',
		'_w' => ($w && is_numeric($w)) ? $_w :'',
		'_h' => ($h && is_numeric($h)) ? $_h :'',
		'tool' => $tool,
		'upload_image' => $upload_image,
		'pchext' => $pchext,
		'anime' => $anime,
		'continue' => $check_elapsed_days ? $continue : ((adminpost_valid()||admindel_valid()) ? $continue :''),
		'time' => $time,
		'date' => $date,
		'host' => $host,
		'userid' => $userid,
		'check_elapsed_days' => $check_elapsed_days,
		'encoded_boardname' => urlencode($boardname),
		'encoded_name' => urlencode($name),
		'encoded_no' => urlencode('['.$no.']'),
		'encoded_sub' => urlencode($sub),
		'encoded_u' => urlencode($root_url.'?resno='.$no),//tweet
		'encoded_t' => urlencode('['.$no.']'.$sub.' by '.$name.' - '.$boardname),
		'oya' => $oya,
		'hide_thumbnail' => $hide_thumbnail, //サムネイルにぼかしをかける時
		'link_thumbnail' => $link_thumbnail, //サムネイルにリンクがある時
	];

	$res['com']=str_replace('"\n"',"\n",$res['com']);

	foreach($res as $key=>$val){
		$res[$key]=h($val);
	}
	
	return $res;
}

//ユーザーip
function get_uip(){
	$ip = isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] :'';
	$ip = $ip ? $ip : (isset($_SERVER["HTTP_INCAP_CLIENT_IP"]) ? $_SERVER["HTTP_INCAP_CLIENT_IP"] : '');
	$ip = $ip ? $ip : (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : '');
	$ip = $ip ? $ip : (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '');
	if (strstr($ip, ', ')) {
		$ips = explode(', ', $ip);
		$ip = $ips[0];
	}
	return $ip;
}

//タブ除去
function t($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	return str_replace("\t","",$str);
}
//タグ除去
function s($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	return strip_tags($str);
}
//エスケープ
function h($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	return htmlspecialchars($str,ENT_QUOTES,"utf-8",false);
}
//コメント出力
function com($str){
	global $use_autolink;

	if(!$str){
		return '';
	}

	if($use_autolink){
	$str=md_link($str);
	$str=auto_link($str);
	}
	return nl2br($str,false);

}
//マークダウン記法のリンクをHTMLに変換
function md_link($str){
	$str= preg_replace('{\[([^\[\]\(\)]+?)\]\((https?://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)\)}','<a href="$2" target="_blank" rel="nofollow noopener noreferrer">$1</a>',$str);
	return $str;
}

// 自動リンク
function auto_link($str){
	if(strpos($str,'<a')===false){//マークダウン記法がなかった時
		$str= preg_replace('{(https?://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)}','<a href="$1" target="_blank" rel="nofollow noopener noreferrer">$1</a>',$str);
	}
	return $str;
}

//mimeから拡張子
function getImgType ($img_type) {

	switch ($img_type) {
		case "image/gif" : return ".gif";
		case "image/jpeg" : return ".jpg";
		case "image/png" : return ".png";
		case "image/webp" : return ".webp";
		default : return '';
	}
	
}
//ファイルがあれば削除
function safe_unlink ($path) {
	if ($path && is_file($path)) {
		return unlink($path);
	}
	return false;
}
/**
 * 一連の画像ファイルを削除（元画像、サムネ、動画）
 * @param $path
 * @param $filename
 * @param $ext
 */
function delete_files ($imgfile, $time) {

	$imgfile=basename($imgfile);
	$time=basename($time);
	safe_unlink(IMG_DIR.$imgfile);
	safe_unlink(THUMB_DIR.$time.'s.jpg');
	safe_unlink(IMG_DIR.$time.'.pch');
	safe_unlink(IMG_DIR.$time.'.spch');
	safe_unlink(IMG_DIR.$time.'.chi');
	safe_unlink(IMG_DIR.$time.'.psd');
}

//png2jpg
function png2jpg ($src) {

	if(mime_content_type($src)!=="image/png" || !function_exists("ImageCreateFromPNG")){
		return;
	}
	//pngならJPEGに変換
	if($im_in=ImageCreateFromPNG($src)){
		if(function_exists("ImageCreateTrueColor") && function_exists("ImageColorAlLocate") &&
		function_exists("imagefill") && function_exists("ImageCopyResampled")){
			list($out_w, $out_h)=getimagesize($src);
			$im_out = ImageCreateTrueColor($out_w, $out_h);
			$background = imagecolorallocate($im_out, 0xFF, 0xFF, 0xFF);//背景色を白に
			imagefill($im_out, 0, 0, $background);
			ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $out_w, $out_h);
		}else{
			$im_out=$im_in;
		}
		$dst = TEMP_DIR.pathinfo($src, PATHINFO_FILENAME ).'.jpg.tmp';
		ImageJPEG($im_out,$dst,98);
		ImageDestroy($im_in);// 作成したイメージを破棄
		ImageDestroy($im_out);// 作成したイメージを破棄
		chmod($dst,0606);
		if(is_file($dst)){
			return $dst;//jpegファイル生成に成功
		}
		return false;
	}
	return false;
}

function error($str){
	global $boardname,$skindir,$en;
	$templete='error.html';
	return include __DIR__.'/'.$skindir.$templete;
	exit;
}
//csrfトークンを作成
function get_csrf_token(){
	session_sta();
	$token=hash('sha256', session_id(), false);
	$_SESSION['token']=$token;

	return $token;
}
//csrfトークンをチェック	
function check_csrf_token(){
	global $en;

	if(($_SERVER["REQUEST_METHOD"]) !== "POST"){
		return error($en?'This operation has failed.':'失敗しました。');
	} 
	check_same_origin();
	session_sta();
	$token=filter_input(INPUT_POST,'token');
	$session_token=isset($_SESSION['token']) ? $_SESSION['token'] : '';
	if(!$session_token||$token!==$session_token){
		return error($en?'CSRF token mismatch.':'CSRFトークンが一致しません。');
	}
}
//session開始
function session_sta(){
	if(!isset($_SESSION)){
		ini_set('session.use_strict_mode', 1);
		session_set_cookie_params(
			0,"","",false,true
		);
		session_start();
		header('Expires:');
		header('Cache-Control:');
		header('Pragma:');
	}
}

function check_same_origin(){
	global $en;

	$usercode = filter_input(INPUT_COOKIE, 'usercode');//user-codeを取得

	if(!$usercode){
		return error($en?'Cookie check failed.':'Cookieが確認できません。');
	} 

	if(!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_HOST'])){
		return error($en?'Your browser is not supported. ':'お使いのブラウザはサポートされていません。');
	}
	$url_scheme=parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_SCHEME).'://';
	if(str_replace($url_scheme,'',$_SERVER['HTTP_ORIGIN']) !== $_SERVER['HTTP_HOST']){
		return error($en?"The post has been rejected.":'拒絶されました。');
	}
}

function check_open_no($no){
	global $en;
	if($no && !is_numeric($no)){
		return error($en?'This operation has failed.':'失敗しました。');
	}
}

function getId ($userip) {
	return substr(hash('sha256', $userip, false),-8);
}

// テンポラリ内のゴミ除去 
function deltemp(){
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file)) {
			$file=basename($file);
			//pchアップロードペイントファイル削除
			//仮差し換えアップロードファイル削除
			$lapse = time() - filemtime(TEMP_DIR.$file);
			if(strpos($file,'pchup-')===0) {
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
}

// NGワードがあれば拒絶
function Reject_if_NGword_exists_in_the_post(){
	global $use_japanesefilter,$badstring,$badname,$badurl,$badstr_A,$badstr_B,$allow_comments_url,$admin_pass,$max_com,$en,$badhost;

	//ホスト取得
	$userip = get_uip();
	$host = $userip ? gethostbyaddr($userip) :'';
	//拒絶ホスト
	foreach($badhost as $value){
		if (preg_match("/$value\z/i",$host)) {
			return error($en?'Post was rejected.':'拒絶されました。');
		}
	}
	
	$adminpost=adminpost_valid();

	$name = t((string)filter_input(INPUT_POST,'name'));
	$sub = t((string)filter_input(INPUT_POST,'sub'));
	$url = t((string)filter_input(INPUT_POST,'url',FILTER_VALIDATE_URL));
	$com = t((string)filter_input(INPUT_POST,'com'));
	$pwd = t((string)filter_input(INPUT_POST,'pwd'));

	$com_len=strlen((string)$com);
	$name_len=strlen((string)$name);
	$sub_len=strlen((string)$sub);
	$url_len=strlen((string)$url);
	$pwd_len=strlen((string)$pwd);

	if($name_len && ($name_len > 30)) return error($en?'Name is too long':'名前が長すぎます。');
	if($sub_len && ($sub_len > 80)) return error($en? 'Subject is too long.':'題名が長すぎます。');
	if($url_len && ($url_len > 100)) return error($en? 'URL is too long.':'URLが長すぎます。');
	if($com_len && ($com_len > $max_com)) return error($en? 'Comment is too long.':'本文が長すぎます。');
	if($pwd_len && ($pwd_len > 100)) return error($en? 'Password is too long.':'パスワードが長すぎます。');

	//チェックする項目から改行・スペース・タブを消す
	$chk_name = $name_len ? preg_replace("/\s/u", "", $name ) : '';
	$chk_sub = $sub_len ? preg_replace("/\s/u", "", $sub ) : '';
	$chk_url = $url_len ? preg_replace("/\s/u", "", $url ) : '';
	$chk_com  = $com_len ? preg_replace("/\s/u", "", $com ) : '';

	//本文に日本語がなければ拒絶
	if ($use_japanesefilter) {
		mb_regex_encoding("UTF-8");
		if ($com_len && !preg_match("/[ぁ-んァ-ヶー一-龠]+/u",$chk_com)) return error($en?'Comment should have at least some Japanese characters.':'日本語で何か書いてください。');
	}

	//本文へのURLの書き込みを禁止
	if(!$allow_comments_url && !$adminpost && (!$admin_pass||$pwd !== $admin_pass)){
		if($com_len && preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) return error($en?'This URL can not be used in text.':'URLの記入はできません。');
	}

	// 使えない文字チェック
	if (is_ngword($badstring, [$chk_name,$chk_sub,$chk_url,$chk_com])) {
		return error($en?'There is an inappropriate string.':'不適切な表現があります。');
	}

	// 使えない名前チェック
	if (is_ngword($badname, $chk_name)) {
		return error($en?'This name cannot be used.':'その名前は使えません。');
	}
	// 使えないurlチェック
	if (is_ngword($badurl, $chk_url)) {
		return error($en?'There is an inappropriate url.':'不適切なurlがあります。');
	}

	//指定文字列が2つあると拒絶
	$bstr_A_find = is_ngword($badstr_A, [$chk_com, $chk_sub, $chk_name]);
	$bstr_B_find = is_ngword($badstr_B, [$chk_com, $chk_sub, $chk_name]);
	if($bstr_A_find && $bstr_B_find){
		return error($en?'There is an inappropriate string.':'不適切な表現があります。');
	}

}
/**
 * NGワードチェック
 * @param $ngwords
 * @param string|array $strs
 * @return bool
 */
function is_ngword ($ngwords, $strs) {
	if (empty($ngwords)||empty($strs)) {
		return false;
	}
	if (!is_array($strs)) {
		$strs = [$strs];
	}
	foreach($ngwords as $i => $ngword){//拒絶する文字列
		$ngwords[$i]  = str_replace([" ", "　"], "", $ngword);
		$ngwords[$i]  = str_replace("/", "\/", $ngwords[$i]);
	}
	foreach ($strs as $str) {
		foreach($ngwords as $ngword){//拒絶する文字列
			if ($ngword !== '' && preg_match("/{$ngword}/ui", $str)){
				return true;
			}
		}
	}
	return false;
}

//初期化
function init(){
	
	check_dir(__DIR__."/src");
	check_dir(__DIR__."/temp");
	check_dir(__DIR__."/thumbnail");
	check_dir(__DIR__."/log");
	if(!is_file(LOG_DIR.'alllog.log')){
	file_put_contents(LOG_DIR.'alllog.log','',FILE_APPEND|LOCK_EX);
	chmod(LOG_DIR.'alllog.log',0600);	
	}
}

//ディレクトリ作成
function check_dir ($path) {

	$msg=initial_error_message();

	if (!is_dir($path)) {
			mkdir($path, 0707);
			chmod($path, 0707);
	}
	if (!is_dir($path)) return die(h($path) . $msg['001']);
	if (!is_readable($path)) return die(h($path) . $msg['002']);
	if (!is_writable($path)) return die(h($path) . $msg['003']);
}

// ファイル存在チェック
function check_file ($path) {
	$msg=initial_error_message();

	if (!is_file($path)) return die(h($path) . $msg['001']);
	if (!is_readable($path)) return die(h($path) . $msg['002']);
}
function initial_error_message(){
	global $en;
	$msg['001']=$en ? ' does not exist.':'がありません。'; 
	$msg['002']=$en ? ' is not readable.':'を読めません。'; 
	$msg['003']=$en ? ' is not writable.':'を書けません。'; 
return $msg;	
}

// 一括書き込み（上書き）
function writeFile ($fp, $data) {
	ftruncate($fp,0);
	set_file_buffer($fp, 0);
	rewind($fp);
	fwrite($fp, $data);
}
//fpクローズ
function closeFile ($fp) {
	if($fp){
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}

//縮小表示
function image_reduction_display($w,$h,$max_w,$max_h){
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
 * @param $starttime
 * @return string
 */
function calcPtime ($psec) {
	global $en;

	$D = floor($psec / 86400);
	$H = floor($psec % 86400 / 3600);
	$M = floor($psec % 3600 / 60);
	$S = $psec % 60;

	if($en){
		return
			($D ? $D.'day '  : '')
			. ($H ? $H.'hr ' : '')
			. ($M ? $M.'min ' : '')
			. ($S ? $S.'sec' : '');
	}
		return
			($D ? $D.'日'  : '')
			. ($H ? $H.'時間' : '')
			. ($M ? $M.'分' : '')
			. ($S ? $S.'秒' : '');
}

/**
 * pchか、それ以外の固有形式のファイルか、それともファイルが存在しないかチェック
 * @param $filepath
 * @return string
 */
function check_pch_ext ($filepath,$option=[]) {
	if (is_file($filepath . ".pch")) {
		return ".pch";
	} elseif (!isset($option['upload'])) {
		return "";
	} elseif (is_file($filepath . ".chi")) {
		return ".chi";
	} elseif (is_file($filepath . ".psd")) {
		return ".psd";
	}
	return '';
}

//GD版が使えるかチェック
function gd_check(){
	$check = array("ImageCreate","ImageCopyResized","ImageCreateFromJPEG","ImageJPEG","ImageDestroy");

	//最低限のGD関数が使えるかチェック
	if(!(get_gd_ver() && (ImageTypes() & IMG_JPG))){
		return false;
	}
	foreach ( $check as $cmd ) {
		if(!function_exists($cmd)){
			return false;
		}
	}
	return true;
}

//gdのバージョンを調べる
function get_gd_ver(){
	if(function_exists("gd_info")){
	$gdver=gd_info();
	$phpinfo=(string)$gdver["GD Version"];
	$end=strpos($phpinfo,".");
	$phpinfo=substr($phpinfo,0,$end);
	$length = strlen($phpinfo)-1;
	$phpinfo=substr($phpinfo,$length);
	return $phpinfo;
	} 
	return false;
}
// 古いスレッドへの投稿を許可するかどうか
function check_elapsed_days ($postedtime) {
	global $elapsed_days;
	return $elapsed_days //古いスレッドのフォームを閉じる日数が設定されていたら
		? ((time() - (int)(substr($postedtime, 0, -3))) <= ((int)$elapsed_days * 86400)) // 指定日数以内なら許可
		: true; // フォームを閉じる日数が未設定なら許可
}
//POSTされた値をログファイルに格納する書式にフォーマット
function create_formatted_text_from_post($name,$sub,$url,$com){
global $en;

if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
if(!$url||!filter_var($url,FILTER_VALIDATE_URL)||!preg_match('{\Ahttps?://}', $url)||preg_match("/\A\s*\z/u",$url)) $url="";
if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
$sub=(!$sub) ? ($en? 'No subject':'無題') : $sub;
$com=str_replace(["\r\n","\r"],"\n",$com);
$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット
$com=str_replace("\n",'"\n"',$com);
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
function is_neo($src) {
	$fp = fopen("$src", "rb");
	$is_neo=(fread($fp,3)==="NEO");
	fclose($fp);
	return $is_neo;
}
//パスワードを5回連続して間違えた時は拒絶
function check_password_input_error_count(){
	global $admin_pass,$second_pass,$en,$check_password_input_error_count;
	if(!$check_password_input_error_count){
		return;
	}
	$userip = get_uip();
	check_dir(__DIR__.'/template/errorlog/');
	$arr_err=is_file(__DIR__.'/template/errorlog/error.log') ? file(__DIR__.'/template/errorlog/error.log'):[];
	if(count($arr_err)>=5){
		error($en?'Rejected.':'拒絶されました。');
	}
if(!$admin_pass || !$second_pass || $admin_pass === $second_pass || $admin_pass!==filter_input(INPUT_POST,'adminpass')){
		$errlog=$userip."\n";
		file_put_contents(__DIR__.'/template/errorlog/error.log',$errlog,FILE_APPEND);
		chmod(__DIR__.'/template/errorlog/error.log',0600);
		}else{
			safe_unlink(__DIR__.'/template/errorlog/error.log');
		}
}