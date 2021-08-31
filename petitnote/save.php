<?php

//設定
include(__DIR__.'/config.php');
require_once(__DIR__.'/function.php');


defined('PERMISSION_FOR_LOG') or define('PERMISSION_FOR_LOG', 0600); //config.phpで未定義なら0600
defined('PERMISSION_FOR_DEST') or define('PERMISSION_FOR_DEST', 0606); //config.phpで未定義なら0606

//タイムゾーン config.phpで未定義ならAsia/Tokyo
defined('DEFAULT_TIMEZONE') or define('DEFAULT_TIMEZONE','Asia/Tokyo');
date_default_timezone_set(DEFAULT_TIMEZONE);

$time = time();
$imgfile = $time.substr(microtime(),2,3);	//画像ファイル名


function chibi_die($message) {
	die("CHIBIERROR $message");
}

if (!isset ($_FILES["picture"]) || $_FILES['picture']['error'] != UPLOAD_ERR_OK
		|| isset($_FILES['chibifile']) && $_FILES['chibifile']['error'] != UPLOAD_ERR_OK) {
	chibi_die("Your picture upload failed! Please try again!");
}

header('Content-type: text/plain');

$rotation = isset($_POST['rotation']) && ((int) $_POST['rotation']) > 0 ? ((int) $_POST['rotation']) : 0;

$success = TRUE;

$success = $success && move_uploaded_file($_FILES['picture']['tmp_name'], TEMP_DIR.$imgfile.'.png');

if (isset($_FILES["chibifile"])) {
	$success = $success && move_uploaded_file($_FILES['chibifile']['tmp_name'], TEMP_DIR.$imgfile.'.chi');
}

// if (isset($_FILES['swatches'])) {
//     $success = $success && move_uploaded_file($_FILES['swatches']['tmp_name'], TEMP_DIR.$imgfile.'.aco');
// }

if (!$success) {
    chibi_die("Couldn't move uploaded files");
}

$u_ip = getenv("HTTP_CLIENT_IP");
if(!$u_ip) $u_ip = getenv("HTTP_X_FORWARDED_FOR");
if(!$u_ip) $u_ip = getenv("REMOTE_ADDR");
$u_host = gethostbyaddr($u_ip);
$u_agent = getenv("HTTP_USER_AGENT");
$u_agent = str_replace("\t", "", $u_agent);
$imgext='.png';
/* ---------- 投稿者情報記録 ---------- */
$userdata = "$u_ip\t$u_host\t$u_agent\t$imgext";
// 拡張ヘッダーを取り出す

$tool = (string)filter_input(INPUT_GET, 'tool');

$usercode = (string)filter_input(INPUT_GET, 'usercode');
// 	$repcode = (string)filter_input(INPUT_GET, 'repcode');
// 	$stime = (string)filter_input(INPUT_GET, 'stime');
// 	$resto = (string)filter_input(INPUT_GET, 'resto');

// 	//usercode 差し換え認識コード 描画開始 完了時間 レス先 を追加
// 	$userdata .= "\t$usercode\t$repcode\t$stime\t$time\t$resto";
// $userdata .= "\n";
// 情報データをファイルに書き込む
$alllog_arr=file('./log/alllog.txt');
$alllog=end($alllog_arr);
$line='';
list($w,$h)=getimagesize(TEMP_DIR.$imgfile.'.png');

//書き込まれるログの書式
	list($no)=explode("\t",$alllog);
	//最後の記事ナンバーに+1

	$no=trim($no)+1;
	$line = "$no\t\t\t\t{$imgfile}{$imgext}\t$w\t$h\t$imgfile\t$tool\t'oya'\n";

	file_put_contents('./log/'.$no.'.txt',$line);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
	chmod('./log/'.$no.'.txt',0600);

	$alllog_arr[]=$line;//全体ログの配列に追加
	Delete_old_thread($alllog_arr);

file_put_contents('./log/alllog.txt',$alllog_arr,LOCK_EX);//全体ログに書き込む
chmod('./log/alllog.txt',0600);

// header('Location: ./');


die("CHIBIOK\n");



