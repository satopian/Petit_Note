<?php
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
	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);	
	}
	$page=filter_input(INPUT_POST,'page',FILTER_VALIDATE_INT);
	$page = $page ? $page : 0;

	return header('Location: ./?page='.$page);
}

//合言葉認証
function aikotoba(){
	global $aikotoba;

	session_sta();
	if(!$aikotoba || $aikotoba!==filter_input(INPUT_POST,'aikotoba')){
		if(isset($_SESSION['aikotoba'])){
			unset($_SESSION['aikotoba']);
		} 
		return 	error('合言葉が違います。');
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

	global $boardname,$use_diary,$use_aikotoba,$petit_lot,$petit_ver,$skindir;
	$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$resno=filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);

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
	include __DIR__.'/'.$skindir.$templete;
	
}
//合言葉を再確認	
function check_aikotoba(){
	if(!aikotoba_valid()){
		return error('合言葉が違います');
	}
	return true;
}
//管理者投稿モード
function adminpost(){
	global $admin_pass,$second_pass;
	session_sta();
	if(!$admin_pass || !$second_pass || $admin_pass === $second_pass || $admin_pass!==filter_input(INPUT_POST,'adminpass')){
		if(isset($_SESSION['adminpost'])){
			unset($_SESSION['adminpost']);
		} 
		return 	error('パスワードが違います。');
	}
	session_regenerate_id(true);
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);

	$page = isset($page) ? $page : 0;

	$_SESSION['aikotoba']='aikotoba';
	$_SESSION['adminpost']=$second_pass;

	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	
	return header('Location: ./?page='.$page);
}

//管理者削除モード
function admin_del(){
	global $admin_pass,$second_pass;
	session_sta();
	if(!$admin_pass || !$second_pass || $admin_pass === $second_pass || $admin_pass!==filter_input(INPUT_POST,'adminpass')){
		if(isset($_SESSION['admindel'])){
			unset($_SESSION['admindel']);
		} 
		return 	error('パスワードが違います。');
	}
	session_regenerate_id(true);
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$page = isset($page) ? $page : 0;

	$_SESSION['aikotoba']='aikotoba';
	
	$_SESSION['admindel']=$second_pass;

	$resno=filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);

	if($resno){
		return header('Location: ./?resno='.$resno);
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
	return isset($_SESSION['adminpost'])&&($_SESSION['adminpost']===$second_pass);
}
function admindel_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['admindel'])&&($_SESSION['admindel']===$second_pass);
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

	$no = (string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input(INPUT_POST, 'time',FILTER_VALIDATE_INT);
	$pwd = filter_input(INPUT_POST, 'pwd');

	$pwd=t(filter_input(INPUT_POST, 'pwd'));//パスワードを取得
	$pwd=$pwd ? $pwd : t(filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード


	if(is_file(LOG_DIR."$no.log")){
		
		$rp=fopen(LOG_DIR."$no.log","r");
		while ($line = fgets($rp)) {
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no && $pwd && password_verify($pwd,$hash)){
				closeFile ($rp);
				return true;
			}
		}
		closeFile ($rp);
	}

	error('パスワードが違います。');
}

//ログ出力の前処理 行から情報を取り出す
function create_res($line){
	global $max_w,$max_h,$root_url,$boardname,$do_not_change_posts_time;
	list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;
	$res=[];

	$continue = false;

	switch($tool){
		case 'neo':
			$tool='PaintBBS NEO';
			$continue = true;
			break;
		case 'chi':
			$tool='ChickenPaint';
			$continue = true;
			break;
		case 'upload':
			$tool='アップロード';
			break;
		default:
			'';
	}

	$anime = ($pchext==='.pch') ? true : false; 
	$thumbnail = ($thumbnail==='thumbnail') ? $time.'s.jpg' : false; 
	$painttime = is_numeric($painttime) ? calcPtime($painttime) : false;  

	// list($w,$h) = image_reduction_display($w,$h,$max_w,$max_h);
	$datetime = $do_not_change_posts_time ? (int)substr($first_posted_time,0,-3) : (int)substr($time,0,-3);
	$date=date('y/m/d',$datetime);

	$check_elapsed_days = check_elapsed_days($time);
	if(!$url||!filter_var($url,FILTER_VALIDATE_URL)||!preg_match('{\Ahttps?://}', $url)) $url="";

	$verified = ($verified==='adminpost') ? true : false;

	$res=[
		'no' => $no,
		'sub' => $sub,
		'name' => $name,
		'verified' => $verified,
		'com' => $com,
		'descriptioncom' => s(mb_strcut(str_replace('"\n"'," ",$com),0,300)),
		'url' => $url,
		'img' => $imgfile,
		'thumbnail' => $thumbnail,
		'painttime' => $painttime,
		'w' => $w,
		'h' => $h,
		'tool' => $tool,
		'pchext' => $pchext,
		'anime' => $anime,
		'continue' => $continue,
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
	];

	$res['com']=str_replace('"\n"',"\n",$res['com']);

	foreach($res as $key=>$val){
		$res[$key]=h($val);
	}
	
	return $res;
}

//ユーザーip
function get_uip(){
	if ($ip = getenv("HTTP_CLIENT_IP")) {
		return $ip;
	} elseif ($ip = getenv("HTTP_X_FORWARDED_FOR")) {
		return $ip;
	}
	return getenv("REMOTE_ADDR");
}

//タブ除去
function t($str){
	return str_replace("\t","",$str);
}
//タグ除去
function s($str){
	return strip_tags($str);
}
//エスケープ
function h($str){
	return htmlspecialchars($str,ENT_QUOTES,"utf-8",false);
}
//コメント出力
function com($str){
	
	global $use_autolink;

	if($use_autolink){
	$str=md_link($str);
	$str=auto_link($str);
	}
	return nl2br($str,false);

}
//マークダウン記法のリンクをHTMLに変換
function md_link($str){
	$str= preg_replace("{\[([^\[\]\(\)]+?)\]\((https?://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)\)}","<a href=\"\\2\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">\\1</a>",$str);
	return $str;
}

// 自動リンク
function auto_link($str){
	if(strpos($str,'<a')===false){//マークダウン記法がなかった時
		$str= preg_replace("{(https?://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)}","<a href=\"\\1\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">\\1</a>",$str);
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
	safe_unlink(IMG_DIR.$imgfile);
	safe_unlink(THUMB_DIR.$time.'s.jpg');
	safe_unlink(IMG_DIR.$time.'.pch');
	safe_unlink(IMG_DIR.$time.'.spch');
	safe_unlink(IMG_DIR.$time.'.chi');
}

//png2jpg
function png2jpg ($src) {
	global $path;
	if(mime_content_type($src)==="image/png" && function_exists("ImageCreateFromPNG")){//pngならJPEGに変換
		if($im_in=ImageCreateFromPNG($src)){
			$dst = $path.pathinfo($src, PATHINFO_FILENAME ).'.jpg.tmp';
			ImageJPEG($im_in,$dst,98);
			ImageDestroy($im_in);// 作成したイメージを破棄
			chmod($dst,0606);
			return $dst;
		}
	}
	return false;
}

function error($str){
	global $boardname,$skindir;
	$templete='error.html';
	include __DIR__.'/'.$skindir.$templete;
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
	session_sta();
	$token=filter_input(INPUT_POST,'token');
	$session_token=isset($_SESSION['token']) ? $_SESSION['token'] : '';
	if(!$session_token||$token!==$session_token){
		error('不正な投稿をしないでください。');
	}
}
//session開始
function session_sta(){
	if(!isset($_SESSION)){
		ini_set('session.use_strict_mode', 1);
		session_set_cookie_params(
			0,"","",null,true
		);
		session_start();
		header('Expires:');
		header('Cache-Control:');
		header('Pragma:');
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
			$lapse = time() - filemtime(TEMP_DIR.$file);
			if($lapse > (3*24*3600)){//3日
				unlink(TEMP_DIR.$file);
			}
			//pchアップロードペイントファイル削除
			if(preg_match("/\A(pchup-.*-tmp\.(s?pch|chi))\z/i",$file)) {
				$lapse = time() - filemtime(TEMP_DIR.$file);
				if($lapse > (300)){//5分
					unlink(TEMP_DIR.$file);
				}
			}
		}
	}
	
	closedir($handle);
}

// NGワードがあれば拒絶
function Reject_if_NGword_exists_in_the_post(){
	global $use_japanesefilter,$badstring,$badname,$badstr_A,$badstr_B,$allow_comments_url,$admin_pass;

	$adminpost=adminpost_valid();

	$sub = t((string)filter_input(INPUT_POST,'sub'));
	$name = t((string)filter_input(INPUT_POST,'name'));
	$com = t((string)filter_input(INPUT_POST,'com'));
	$pwd = t((string)filter_input(INPUT_POST,'pwd'));

	//チェックする項目から改行・スペース・タブを消す
	$chk_com  = preg_replace("/\s/u", "", $com );
	$chk_name = preg_replace("/\s/u", "", $name );
	$chk_sub = preg_replace("/\s/u", "", $sub );

	//本文に日本語がなければ拒絶
	if ($use_japanesefilter) {
		mb_regex_encoding("UTF-8");
		if (strlen($com) > 0 && !preg_match("/[ぁ-んァ-ヶー一-龠]+/u",$chk_com)) error('日本語で何か書いてください。');
	}

	//本文へのURLの書き込みを禁止
	if(!$allow_comments_url && !$adminpost && (!$admin_pass||$pwd !== $admin_pass)){
		if(preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) error('URLの記入はできません。');
	}

	// 使えない文字チェック
	if (is_ngword($badstring, [$chk_com, $chk_sub, $chk_name])) {
		error('不適切な表現があります。');
	}

	// 使えない名前チェック
	if (is_ngword($badname, $chk_name)) {
		error('その名前は使えません。');
	}

	//指定文字列が2つあると拒絶
	$bstr_A_find = is_ngword($badstr_A, [$chk_com, $chk_sub, $chk_name]);
	$bstr_B_find = is_ngword($badstr_B, [$chk_com, $chk_sub, $chk_name]);
	if($bstr_A_find && $bstr_B_find){
		error('不適切な表現があります。');
	}

}
/**
 * NGワードチェック
 * @param $ngwords
 * @param string|array $strs
 * @return bool
 */
function is_ngword ($ngwords, $strs) {
	if (empty($ngwords)) {
		return false;
	}
	if (!is_array($strs)) {
		$strs = [$strs];
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
	check_dir("src");
	check_dir("temp");
	check_dir("thumbnail");
	check_dir("log");
	if(!is_file(LOG_DIR.'alllog.log')){
	file_put_contents(LOG_DIR.'alllog.log','',FILE_APPEND|LOCK_EX);
	chmod(LOG_DIR.'alllog.log',0600);	
	}
}

//ディレクトリ作成
function check_dir ($path) {

	if (!is_dir($path)) {
			mkdir($path, 0707);
			chmod($path, 0707);
	}
	if (!is_dir($path)) return "{$path}がありません。<br>";
	if (!is_readable($path)) return "{$path}を読めません。<br>";
	if (!is_writable($path)) return "{$path}を書けません。<br>";
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
	fflush($fp);
	flock($fp, LOCK_UN);
	fclose($fp);
}

//縮小表示
function image_reduction_display($w,$h,$max_w,$max_h){
	if(!is_numeric($w)||!is_numeric($h)){
		return;
	}
	$reduced_size=[];

	if($w > $max_w || $h > $max_h){
		$key_w = $max_w / $w;
		$key_h = $max_h / $h;
		($key_w < $key_h) ? ($keys = $key_w) : ($keys = $key_h);
		$w=ceil($w * $keys);
		$h=ceil($h * $keys);
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

	$D = floor($psec / 86400);
	$H = floor($psec % 86400 / 3600);
	$M = floor($psec % 3600 / 60);
	$S = $psec % 60;

	return
		($D ? $D.'日'  : '')
		. ($H ? $H.'時間' : '')
		. ($M ? $M.'分' : '')
		. ($S ? $S.'秒' : '');
}

/**
 * pchかspchか、それともファイルが存在しないかチェック
 * @param $filepath
 * @return string
 */
function check_pch_ext ($filepath) {
	if (is_file($filepath . ".pch")) {
		return ".pch";
	} elseif (is_file($filepath . ".spch")) {
		return ".spch";
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
	$phpinfo=$gdver["GD Version"];
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


