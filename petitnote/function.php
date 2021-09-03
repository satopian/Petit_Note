<?php
//ユーザーip
function get_uip(){
	if ($ip = getenv("HTTP_CLIENT_IP")) {
		return $ip;
	} elseif ($ip = getenv("HTTP_X_FORWARDED_FOR")) {
		return $ip;
	}
	return getenv("REMOTE_ADDR");
}
//管理者モード
function admin(){
	global $admin_pass;
	if($admin_pass!==filter_input(INPUT_POST,'adminpass')){
		return 	error('パスワードが違います。');
	}
		session_sta();
		$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
		$page = $page ?? 0;
		$_SESSION['admin']='admin_mode';

		return header('Location: ./?page='.$page);
}
	
	

// //管理者モード
// function admin(){
// 	global $admin_pass;
// 	if($admin_pass==filter_input(INPUT_POST,'adminpass')){
// 		session_sta();
// 		$page=filter_input(INPUT_GET,'page');

// 		view($page);
// 		return $_SESSION['admin']='admin_mode';

// 	}
// 	return false;
	
// }

//タブ除去
function t($str){
	return str_replace("\t","",$str);
}
//エスケープと改行
function h($str){
	$str=htmlspecialchars($str,ENT_QUOTES,"utf-8");
	return nl2br($str);
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
	$templete='error.html';
	include __DIR__.'/template/'.$templete;

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
		session_start();
		header('Expires:');
		header('Cache-Control:');
		header('Pragma:');
	}
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
		}
	}
	
	closedir($handle);
}

// NGワードがあれば拒絶
function Reject_if_NGword_exists_in_the_post(){
	global $use_japanesefilter,$badstring,$badname,$badstr_A,$badstr_B;

	$sub = t((string)filter_input(INPUT_POST,'sub'));
	$name = t((string)filter_input(INPUT_POST,'name'));
	$com = t((string)filter_input(INPUT_POST,'com'));

	//チェックする項目から改行・スペース・タブを消す
	$chk_com  = preg_replace("/\s/u", "", $com );
	$chk_name = preg_replace("/\s/u", "", $name );
	$chk_sub = preg_replace("/\s/u", "", $sub );

	//本文に日本語がなければ拒絶
	if ($use_japanesefilter) {
		mb_regex_encoding("UTF-8");
		if (strlen($com) > 0 && !preg_match("/[ぁ-んァ-ヶー一-龠]+/u",$chk_com)) error('半角英数のみの投稿はできません。');
	}

	//本文へのURLの書き込みを禁止
		if(preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) error(MSG036);

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
	check_dir("log");
	if(!is_file('./log/alllog.log')){
	file_put_contents('./log/alllog.log','',FILE_APPEND|LOCK_EX);
	chmod('./log/alllog.log',0600);	
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


