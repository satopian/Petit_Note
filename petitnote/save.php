<?php
//Petit Note 2021-2023 (c)satopian MIT Licence
//https://paintbbs.sakura.ne.jp/

if(($_SERVER["REQUEST_METHOD"]) !== "POST"){
	return header( "Location: ./ ") ;
}

//設定
include(__DIR__.'/config.php');
include(__DIR__.'/functions.php');

$security_timer = isset($security_timer) ? $security_timer : 0;
$lang = ($http_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
  ? explode( ',', $http_langs )[0] : '';
$en= (stripos($lang,'ja')!==0);

//容量違反チェックをする する:1 しない:0
define('SIZE_CHECK', '1');
//PNG画像データ投稿容量制限KB(chiは含まない)
define('PICTURE_MAX_KB', '8192');//8MBまで
define('CHIBI_MAX_KB', '40960');//40MBまで。ただしサーバのPHPの設定によって2MB以下に制限される可能性があります。
defined('PERMISSION_FOR_LOG') or define('PERMISSION_FOR_LOG', 0600); //config.phpで未定義なら0600
defined('PERMISSION_FOR_DEST') or define('PERMISSION_FOR_DEST', 0606); //config.phpで未定義なら0606

$time = time();
$imgfile = time().substr(microtime(),2,6);	//画像ファイル名
$imgfile = is_file(TEMP_DIR.$imgfile.'.png') ? ((time()+1).substr(microtime(),2,6)) : $imgfile;

function chibi_die($message) {
	die("CHIBIERROR $message");
}

header('Content-type: text/plain');
//Sec-Fetch-SiteがSafariに実装されていないので、Orijinと、hostをそれぞれ取得して比較。
//Orijinがhostと異なっていたら投稿を拒絶。
if(!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_HOST'])){
	chibi_die($en ? "Your browser is not supported." : "お使いのブラウザはサポートされていません。");
}
if(parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']){
	chibi_die($en ? "The post has been rejected." : "拒絶されました。");
}

$usercode = (string)filter_input(INPUT_GET, 'usercode');

//csrf
if(!$usercode || $usercode !== (string)filter_input(INPUT_COOKIE, 'usercode')){
	chibi_die($en ? "User code mismatch." : "ユーザーコードが一致しません。");
}

$u_ip = get_uip();
$u_host = $u_ip ? gethostbyaddr($u_ip) : '';
$u_agent = $_SERVER["HTTP_USER_AGENT"];
$u_agent = str_replace("\t", "", $u_agent);
$imgext='.png';
/* ---------- 投稿者情報記録 ---------- */
$userdata = "$u_ip\t$u_host\t$u_agent\t$imgext";
$tool = (string)filter_input(INPUT_GET, 'tool');
$repcode = (string)filter_input(INPUT_GET, 'repcode');
$stime = (string)filter_input(INPUT_GET, 'stime',FILTER_VALIDATE_INT);
$resto = (string)filter_input(INPUT_GET, 'resto',FILTER_VALIDATE_INT);
//usercode 差し換え認識コード 描画開始 完了時間 レス先 を追加
$userdata .= "\t$usercode\t$repcode\t$stime\t$time\t$resto\t$tool";
$userdata .= "\n";

$timer=time()-(int)$stime;
if((bool)$security_timer && !$repcode && !adminpost_valid()  && ((int)$timer<(int)$security_timer)){

	$psec=(int)$security_timer-(int)$timer;
	$waiting_time=calcPtime ($psec);
	if($en){
		die("Please draw for another {$waiting_time['en']}.");
	}else{
		die("描画時間が短すぎます。あと{$waiting_time['ja']}。");
	}
}

if(!isset ($_FILES["picture"]) || $_FILES['picture']['error'] != UPLOAD_ERR_OK) {
	chibi_die($en ? "Your picture upload failed! Please try again!" : "投稿に失敗。時間をおいて再度投稿してみてください。");
}

if(SIZE_CHECK && ($_FILES['picture']['size'] > (PICTURE_MAX_KB * 1024))){
	chibi_die($en ? "The size of the picture is too big. " : "ファイルサイズが大きすぎます。");
}

if(mime_content_type($_FILES['picture']['tmp_name'])!=='image/png'){
	chibi_die($en ? "Your picture upload failed! Please try again!" : "投稿に失敗。時間をおいて再度投稿してみてください。");
}
$success = move_uploaded_file($_FILES['picture']['tmp_name'], TEMP_DIR.$imgfile.'.png');

if(!$success||!is_file(TEMP_DIR.$imgfile.'.png')) {
    chibi_die($en ? "Your picture upload failed! Please try again!" : "投稿に失敗。時間をおいて再度投稿してみてください。");
}
chmod(TEMP_DIR.$imgfile.'.png',PERMISSION_FOR_DEST);
if(isset($_FILES['chibifile']) && ($_FILES['chibifile']['error'] == UPLOAD_ERR_OK)){
	if(!SIZE_CHECK || ($_FILES['chibifile']['size'] < (CHIBI_MAX_KB * 1024))){
		//chiファイルのアップロードができなかった場合はエラーメッセージはださず、画像のみ投稿する。 
	move_uploaded_file($_FILES['chibifile']['tmp_name'], TEMP_DIR.$imgfile.'.chi');
		if(is_file(TEMP_DIR.$imgfile.'.chi')){
			chmod(TEMP_DIR.$imgfile.'.chi',PERMISSION_FOR_DEST);
		}
	}
}
// 情報データをファイルに書き込む
file_put_contents(TEMP_DIR.$imgfile.".dat",$userdata,LOCK_EX);
	
if(!is_file(TEMP_DIR.$imgfile.'.dat')){
	chibi_die($en ? "Your picture upload failed! Please try again!" : "投稿に失敗。時間をおいて再度投稿してみてください。");
}
chmod(TEMP_DIR.$imgfile.'.dat',PERMISSION_FOR_LOG);
	
die("CHIBIOK\n");
