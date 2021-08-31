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
	if($admin_pass==filter_input(INPUT_POST,'adminpass')){
		if(!isset($_SESSION)){
			session_start();
		}
		header('Expires:');
		header('Cache-Control:');
		header('Pragma:');
		return $_SESSION['admin']='admin_mode';
	}
	return false;
	
}

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
	if(!isset($_SESSION)){
		session_start();
	}
	header('Expires:');
	header('Cache-Control:');
	header('Pragma:');
	$token=hash('sha256', session_id(), false);
	$_SESSION['token']=$token;

	return $token;
}
//csrfトークンをチェック	
function check_csrf_token(){
	session_start();
	$token=filter_input(INPUT_POST,'token');
	$session_token=isset($_SESSION['token']) ? $_SESSION['token'] : '';
	if(!$session_token||$token!==$session_token){
		error('不正な投稿をしないでください。');
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
			//pchアップロードペイントファイル削除
			if(preg_match("/\A(pchup-.*-tmp\.s?pch)\z/i",$file)) {
				$lapse = time() - filemtime(TEMP_DIR.$file);
				if($lapse > (300)){//5分
					unlink(TEMP_DIR.$file);
				}
			}
		}
	}
	
	closedir($handle);
}
