<?php
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
//スレッド数オーバー
function Delete_old_thread($alllog_arr){
	global $max_log;
	if(!$max_log){
		error('最大スレッド数が設定されていません。');
	}
	$countlog=count($alllog_arr);
	for($i=0;$i<$countlog-$max_log;++$i){//$max_logスレッド分残して削除
		list($_no,,,,,$imgfile,)=explode("\t",$alllog_arr[$i]);
		if(is_file("./log/$_no.txt")){
	
			$fp = fopen("./log/$_no.txt", "r");//個別スレッドのログを開く
			while ($line = fgetcsv($fp, 0, "\t")) {
			list(,,,,$imgfile,)=$line;
			safe_unlink('src/'.$imgfile);//画像削除
		}
		fclose($fp);
		}	
		safe_unlink('./log/'.$_no.'.txt');//スレッド個別ログファイル削除
		unset($alllog_arr[$i]);//全体ログ記事削除
	}
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
