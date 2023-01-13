<?php
if(($_SERVER["REQUEST_METHOD"]) !== "POST"){
	return header( "Location: ./ ") ;
}

//----------------------------------------------------------------------
// picpost.php lot.221203 for PetitNote
// by さとぴあ & POTI-board redevelopment team >> https://paintbbs.sakura.ne.jp/poti/ 
// originalscript (c)SakaQ 2005 >> http://www.punyu.net/php/
// しぃからPOSTされたお絵かき画像をTEMPに保存
//
// このスクリプトはPaintBBS（藍珠CGI）のPNG保存ルーチンを参考に
// PHP用に作成したものです。
//----------------------------------------------------------------------
// 2022/12/03 same-originでは無かった時はエラーにする。
// 2022/11/23 ユーザーコード不一致の時のためのエラーメッセージを追加。
// 2022/10/22 '$security_timer''$security_click'で設定された必要な描画時間と描画工程数をチェックする処理を追加。
// 2022/10/14 画像データのmimeタイプのチェックを追加。
// 2022/08/21 PCHデータの書き込みエラーでは停止しないようにした。
// 2022/07/03 同名のファイルが存在する時は秒数に+1して回避。ファイル名の秒数を13桁→16桁へ。
// 2022/06/27 画像とユーザーデータが存在しない時は画面を推移せずエラーのアラートを出す。
// 2021/11/08 CSRF対策にusercodeを使用。cookieが確認できない場合は画像を保存しない。
// 2021/10/31 エラー発生時は、ユーザーのキャンバスにエラー内容が表示されるためシステムログへのエラーログの保存処理を削除した。
// 2021/05/17 エラーが発生した時はお絵かき画面から移動せず、エラーの内容を表示する。
// 2021/02/17 $badfileが未定義の時は拒絶画像の処理をしない。
// 2021/01/30 picpost.systemlogの設定をpicpost.phpに移動。raw POST データ取得処理を整理。
// 2021/01/01 エラーログのパーミッションもconfig.phpで設定できるようにした。
// 2020/12/20 config.phpでパーミッションを設定できるようにした。
// 2020/12/18 php8対応。画像から続きを描くと投稿できなくなる問題を修正。
// 2020/11/16 lot.201110の投稿完了時間が記録されないバグを修正。
// 2020/11/10 レス先の記録に対応。拡張ヘッダの値の取得を可変変数から連想配列に変更。
// 2020/08/28 描画時間の記録に対応
// 2020/05/25 投稿容量制限の設定項目を追加 従来はconfigのMAX_KB
// 2020/02/25 flock()修正タイムゾーンを'Asia/Tokyo'に
// 2020/01/25 REMOTE_ADDRが取得できないサーバに対応
// 2019/12/03 軽微なエラー修正。datファイルのパーミッションを600に
// 2018/07/13 動画が記録できなくなっていたのを修正
// 2018/01/12 php7対応
// 2005/06/04 容量違反・画像サイズ違反・拒絶画像のチェックを追加
// 2005/02/14 差し換え時の認識コードrepcodeを投稿者情報に追加
// 2004/06/22 ユーザーを識別するusercodeを投稿者情報に追加
// 2003/12/22 JPEG対応
// 2003/10/03 しぃペインターに対応
// 2003/09/10 IPアドレス取得方法変更
// 2003/09/09 PCHファイルに対応.投稿者情報の記録機能追加
// 2003/09/01 PHP風(?)に整理
// 2003/08/28 perl -> php 移植  by TakeponG >> https://chomstudio.com/
// 2003/07/11 perl版初公開


//設定
include(__DIR__.'/config.php');
$security_timer = isset($security_timer) ? $security_timer : 0;
$lang = ($http_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
  ? explode( ',', $http_langs )[0] : '';
$en= (stripos($lang,'ja')!==0);

if($en){//ブラウザの言語が日本語以外の時
	$errormsg_1 = "Your picture upload failed! Please try again!";
	$errormsg_2 = "Your browser is not supported.";
	$errormsg_3 = "The post has been rejected.";
	$errormsg_4 = "User code mismatch.";
	$errormsg_5 = "The size of the picture is too big. ";
	$errormsg_6 = "Your browser is not supported.";
}else{//日本語
	$errormsg_1 = "投稿に失敗。時間をおいて再度投稿してみてください。";
	$errormsg_2 = "お使いのブラウザはサポートされていません。";
	$errormsg_3 = "拒絶されました。";
	$errormsg_4 = "ユーザーコードが一致しません。";
	$errormsg_5 = "ファイルサイズが大きすぎます。";
	$errormsg_6 = "お使いのブラウザはサポートされていません。";
}

//容量違反チェックをする する:1 しない:0
define('SIZE_CHECK', '1');
//PNG画像データ投稿容量制限KB(chiは含まない)
define('PICTURE_MAX_KB', '8192');//8MBまで
define('PSD_MAX_KB', '40960');//40MBまで。ただしサーバのPHPの設定によって2MB以下に制限される可能性があります。
defined('PERMISSION_FOR_LOG') or define('PERMISSION_FOR_LOG', 0600); //config.phpで未定義なら0600
defined('PERMISSION_FOR_DEST') or define('PERMISSION_FOR_DEST', 0606); //config.phpで未定義なら0606

$time = time();
$imgfile = time().substr(microtime(),2,6);	//画像ファイル名
$imgfile = is_file(TEMP_DIR.$imgfile.'.png') ? ((time()+1).substr(microtime(),2,6)) : $imgfile;

header('Content-type: text/plain');
//Sec-Fetch-SiteがSafariに実装されていないので、Orijinと、hostをそれぞれ取得して比較。
//Orijinがhostと異なっていたら投稿を拒絶。
if(!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_HOST'])){
	die("error\n{$$errormsg_2}");
}
$url_scheme=parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_SCHEME).'://';
if(str_replace($url_scheme,'',$_SERVER['HTTP_ORIGIN']) !== $_SERVER['HTTP_HOST']){
	die("error\n{$errormsg_3}");
}
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	die("error\n{$errormsg_3}");
}


$u_ip = get_uip();
$u_host = $u_ip ? gethostbyaddr($u_ip) : '';
$u_agent = $_SERVER["HTTP_USER_AGENT"];
$u_agent = str_replace("\t", "", $u_agent);
$imgext='.png';
// 拡張ヘッダーを取り出す
$sendheader = filter_input(INPUT_POST,'header',);
/* ---------- 投稿者情報記録 ---------- */
$userdata = "$u_ip\t$u_host\t$u_agent\t$imgext";
$usercode='';
if($sendheader){
	$sendheader = str_replace("&amp;", "&", $sendheader);
	parse_str($sendheader, $u);
	$tool = 'neo';
	$usercode = isset($u['usercode']) ? $u['usercode'] : '';
	$resto = isset($u['resto']) ? $u['resto'] : '';
	$repcode = isset($u['repcode']) ? $u['repcode'] : '';
	$stime = isset($u['stime']) ? $u['stime'] : '';
	$count = isset($u['count']) ? $u['count'] : 0;
	$timer = isset($u['timer']) ? ($u['timer']/1000) : 0;
	//usercode 差し換え認識コード 描画開始 完了時間 レス先 を追加
	$userdata .= "\t$usercode\t$repcode\t$stime\t$time\t$resto\t$tool";
}

//csrf
if($usercode !== filter_input(INPUT_COOKIE, 'usercode')){
	die("error\n{$errormsg_4}");
}

if((!adminpost_valid() && !$repcode && $timer) && (int)$timer<(int)$security_timer){

	$psec=(int)$security_timer-(int)$timer;
	$waiting_time=calcPtime ($psec);
	if($en){
		die("error\nPlease draw for another {$waiting_time}.");
	}else{
		die("error\n描画時間が短すぎます。あと{$waiting_time}。");
	}
}

if(!isset ($_FILES["picture"]) || $_FILES['picture']['error'] != UPLOAD_ERR_OK){
	die("error\n{$errormsg_1}");
}

if(SIZE_CHECK && ($_FILES['picture']['size'] > (PICTURE_MAX_KB * 1024))){
	die("error\n{$errormsg_5}");
}

if(mime_content_type($_FILES['picture']['tmp_name'])!=='image/png'){
	die("error\n{$errormsg_1}");
}
$success = move_uploaded_file($_FILES['picture']['tmp_name'], TEMP_DIR.$imgfile.'.png');

if(!$success||!is_file(TEMP_DIR.$imgfile.'.png')) {
    die("error\n{$errormsg_1}");
}
chmod(TEMP_DIR.$imgfile.'.png',PERMISSION_FOR_DEST);
if(isset($_FILES['pch']) && ($_FILES['pch']['error'] == UPLOAD_ERR_OK)){
	// if(mime_content_type($_FILES['psd']['tmp_name'])==="image/vnd.adobe.photoshop"){
		if(!SIZE_CHECK || ($_FILES['pch']['size'] < (PSD_MAX_KB * 1024))){
			//PSDファイルのアップロードができなかった場合はエラーメッセージはださず、画像のみ投稿する。 
			move_uploaded_file($_FILES['pch']['tmp_name'], TEMP_DIR.$imgfile.'.pch');
			if(is_file(TEMP_DIR.$imgfile.'.pch')){
				chmod(TEMP_DIR.$imgfile.'.pch',PERMISSION_FOR_DEST);
			}
		}
	// }
}
// 情報データをファイルに書き込む
file_put_contents(TEMP_DIR.$imgfile.".dat",$userdata,LOCK_EX);
if(!is_file(TEMP_DIR.$imgfile.'.dat')){
	die("error\n{$errormsg_1}");
}
chmod(TEMP_DIR.$imgfile.'.dat',PERMISSION_FOR_LOG);

die("ok");
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
			. ($S ? $S.'sec' : '')
			. (!$D&&!$H&&!$M&&!$S) ? '0sec':'';

	}
		return
			($D ? $D.'日'  : '')
			. ($H ? $H.'時間' : '')
			. ($M ? $M.'分' : '')
			. ($S ? $S.'秒' : '')
			. (!$D&&!$H&&!$M&&!$S) ? '0秒':'';

}
//ユーザーip
function get_uip(){
	$ip = isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] :'';
	$ip = $ip ? $ip : (isset($_SERVER["HTTP_INCAP_CLIENT_IP"]) ? $_SERVER["HTTP_INCAP_CLIENT_IP"] : '');
	$ip = $ip ? $ip : (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : '');
	$ip = $ip ? $ip : (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '');
	if(strstr($ip, ', ')) {
		$ips = explode(', ', $ip);
		$ip = $ips[0];
	}
	return $ip;
}
//sessionの確認
function adminpost_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['adminpost'])&&($second_pass && $_SESSION['adminpost']===$second_pass);
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
