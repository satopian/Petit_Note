<?php
//Petit-board (c)さとぴあ @satopian 2020-2021
//1スレッド1ログファイル形式のスレッド式掲示板

//設定項目
// 最大スレッド数
$max_log=30;
$max_res=10;
$max_kb=2048;

$mode = filter_input(INPUT_POST,'mode');
if($mode==='regist'){
	post();
}
$page=filter_input(INPUT_GET,'page');
//投稿処理
function post(){	
global $max_log,$max_res,$max_kb;
//POSTされた内容を取得
check_csrf_token();
$sub = t((string)filter_input(INPUT_POST,'sub'));
$name = t((string)filter_input(INPUT_POST,'name'));
$com = t((string)filter_input(INPUT_POST,'com'));
$resno = t((string)filter_input(INPUT_POST,'resno'));
if($resno&&is_file('./log/'.$resno.'.txt')&&(count(file('./log/'.$resno.'.txt'))>$max_res)){//レスの時はスレッド別ログに追記
	error('最大レス数を超過しています。');
}

if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
if(strlen($sub) > 80) error('題名が長すぎます。');
if(strlen($name) > 30) error('名前が長すぎます。');
if(strlen($com) > 1000) error('本文が長すぎます。');


$tempfile = $_FILES['imgfile']['tmp_name'] ?? ''; // 一時ファイル名
$filesize = $_FILES['imgfile']['size'];
if($filesize > $max_kb*1024){
	error("アップロードに失敗しました。ファイル容量が{$max_kb}kbを越えています。");
}
$imgfile='';
$w='';
$h='';

if ($tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK){
	$img_type = $_FILES['imgfile']['type'] ?? '';

	if (!in_array($img_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])) {
		error('対応していないフォーマットです。');
	}
	

	$time = time();
	$time = $time.substr(microtime(),2,3);	//画像ファイル名
	$upfile='src/'.$time.'.tmp';
		move_uploaded_file($tempfile,$upfile);

	if($filesize > 512 * 1024){//指定サイズを超えていたら
		if ($im_jpg = png2jpg($upfile)) {

			if(filesize($im_jpg)<$filesize){//JPEGのほうが小さい時だけ
				rename($im_jpg,$upfile);//JPEGで保存
				chmod($upfile,0606);
			} else{//PNGよりファイルサイズが大きくなる時は
				unlink($im_jpg);//作成したJPEG画像を削除
			}
		}
	}
	list($w,$h)=getimagesize($upfile);
	$_img_type=mime_content_type($upfile);
	$ext=getImgType ($_img_type);
	$imgfile=$time.$ext;
	rename($upfile,'./src/'.$imgfile);
}

if(!$sub){
	$sub='無題';
}
if(!$name){
	$name='anonymous';
}
$sub=str_replace(["\r\n","\r","\n",],'',$sub);
$name=str_replace(["\r\n","\r","\n",],'"\n"',$name);
$com=str_replace(["\r\n","\r","\n",],'"\n"',$com);
$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット

setcookie("namec",$name,time()+(60*60*24*30),0,"",false,true);

if(!$imgfile&&!$com){
error('何か書いて下さい。');
}

//全体ログを開く
$alllog_arr=file('./log/alllog.txt');
$alllog=end($alllog_arr);
$line='';
//書き込まれるログの書式
if($resno){//レスの時はスレッド別ログに追記
	$r_line = "$resno\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$resno\n";
	file_put_contents('./log/'.$resno.'.txt',$r_line,FILE_APPEND);
	chmod('./log/'.$resno.'.txt',0600);	
	foreach($alllog_arr as $i =>$val){
		list($_no)=explode("\t",$alllog_arr[$i]);
		if($resno==$_no){
			$line = $val;//レスが付いたスレッドを$lineに保存。あとから配列に追加して上げる
			unset($alllog_arr[$i]);//レスが付いたスレッドを全体ログからいったん削除
			break;
		}
	}
	
} else{
	list($no)=explode("\t",$alllog);
	//最後の記事ナンバーに+1
	$no=trim($no)+1;
	$line = "$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$resno\n";
	file_put_contents('./log/'.$no.'.txt',$line);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
	chmod('./log/'.$no.'.txt',0600);
}
	$alllog_arr[]=$line;//全体ログの配列に追加
//スレッド数オーバー
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

file_put_contents('./log/alllog.txt',$alllog_arr,LOCK_EX);//全体ログに書き込む
chmod('./log/alllog.txt',0600);

header('Location: ./');

}
$token=get_csrf_token();

$alllog_arr=file('./log/alllog.txt');//全体ログを読み込む
$count_alllog=count($alllog_arr);
krsort($alllog_arr);

//ページ番号から10スレッド分とりだす
$alllog_arr=array_slice($alllog_arr,$page,10,false);
//oyaのループ
foreach($alllog_arr as $oya => $alllog){
	
		list($no)=explode("\t",$alllog);
		if(is_file("./log/$no.txt")){

		$fp = fopen("./log/$no.txt", "r");//個別スレッドのログを開く
		while ($line = fgetcsv($fp, 0, "\t")) {
		list($no,$sub,$name,$com,$imgfile,$w,$h,$resno)=$line;
		$res=[];
		$res=[
			'no' => $no,
			'sub' => $sub,
			'name' => $name,
			'com' => $com,
			'img' => $imgfile,
			'w' => $w,
			'h' => $h,
			'resno' => $resno,
		];
		$res['com']=str_replace('"\n"',"\n",$res['com']);
		$out[$oya][]=$res;
		}	
	fclose($fp);
	}

}

//Cookie
$namec=(string)filter_input(INPUT_COOKIE,'namec');
$templete='main.html';
// HTML出力
include __DIR__.'/template/'.$templete;

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
