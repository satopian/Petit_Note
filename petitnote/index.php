<?php
//Petit Note (c)さとぴあ @satopian 2021
//1スレッド1ログファイル形式のスレッド式画像掲示板

require_once(__DIR__.'/config.php');	
require_once(__DIR__.'/function.php');

$mode = filter_input(INPUT_POST,'mode');
$mode = $mode ? $mode :filter_input(INPUT_GET,'mode');
$page=filter_input(INPUT_GET,'page');

switch($mode){
	case 'regist':
		return post();
	case 'paint':
		return paint();
	case 'paintcom':
		return paintcom();
	case 'del':
		return del();
	case 'admin':
		return admin();
	case 'logout':
		session_sta();
		unset($_SESSION['admin']);
		return header('Location: ./?page='.$page);
	default:
		return view($page);
}


$usercode = filter_input(INPUT_COOKIE, 'usercode');//nullならuser-codeを発行
$userip = get_uip();
//user-codeの発行
if(!$usercode){//falseなら発行
	$usercode = substr(crypt(md5($userip.date("Ymd", time())),'id'),-12);
	//念の為にエスケープ文字があればアルファベットに変換
	$usercode = strtr($usercode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~","ABCDEFGHIJKLMNOabcdefghijklmn");
}
setcookie("usercode", $usercode, time()+(86400*365));//1年間


//初期化

check_dir("src");
check_dir("temp");
check_dir("log");
if(!is_file('./log/alllog.txt')){
file_put_contents('./log/alllog.txt','',FILE_APPEND|LOCK_EX);
chmod('./log/alllog.txt',0600);	
}

deltemp();//テンポラリ自動削除

//投稿処理
function post(){	
global $max_log,$max_res,$max_kb;
//POSTされた内容を取得
check_csrf_token();

$usercode = filter_input(INPUT_COOKIE, 'usercode');
$userip = get_uip();
//ホスト取得
$host = gethostbyaddr($userip);

$sub = t((string)filter_input(INPUT_POST,'sub'));
$name = t((string)filter_input(INPUT_POST,'name'));
$com = t((string)filter_input(INPUT_POST,'com'));
$resno = t((string)filter_input(INPUT_POST,'resno'));
if($resno&&is_file('./log/'.$resno.'.txt')&&(count(file('./log/'.$resno.'.txt'))>$max_res)){//レスの時はスレッド別ログに追記
	error('最大レス数を超過しています。');
}

//NGワードがあれば拒絶
Reject_if_NGword_exists_in_the_post();


//制限
if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
if(strlen($sub) > 80) error('題名が長すぎます。');
if(strlen($name) > 30) error('名前が長すぎます。');
if(strlen($com) > 1000) error('本文が長すぎます。');

//ファイルアップロード
$tempfile = $_FILES['imgfile']['tmp_name'] ?? ''; // 一時ファイル名
$filesize = $_FILES['imgfile']['size'] ?? '';
if($filesize > $max_kb*1024){
	error("アップロードに失敗しました。ファイル容量が{$max_kb}kbを越えています。");
}

$imgfile='';
$w='';
$h='';
$tool='';
$time = time();
$time = $time.substr(microtime(),2,3);	//投稿時刻
//ファイルアップロード処理
$upfile='';
if ($tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK){
	$img_type = $_FILES['imgfile']['type'] ?? '';

	if (!in_array($img_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])) {
		error('対応していないフォーマットです。');
	}
	$upfile='src/'.$time.'.tmp';
	move_uploaded_file($tempfile,$upfile);
	$tool = 'upload'; 
	
}
//お絵かきアップロード
$pictmp = filter_input(INPUT_POST, 'pictmp',FILTER_VALIDATE_INT);
$picfile = filter_input(INPUT_POST, 'picfile');
if($pictmp==2){
	if(!$picfile) error('投稿に失敗しました。');
	$tempfile = TEMP_DIR.$picfile;
	// $upfile_name = basename($tempfile);
	$picfile=pathinfo($tempfile, PATHINFO_FILENAME );//拡張子除去
	//選択された絵が投稿者の絵か再チェック
	if (!$picfile || !is_file(TEMP_DIR.$picfile.".dat")) {
		error('投稿に失敗しました。');
	}
	//ユーザーデータから情報を取り出す
	$fp = fopen(TEMP_DIR.$picfile.".dat", "r");
	$userdata = fread($fp, 1024);
	fclose($fp);
	list($uip,$uhost,,,$ucode,,$starttime,$postedtime,$uresto,$tool) = explode("\t", rtrim($userdata)."\t");
	if(($ucode != $usercode) && ($uip != $userip)){error('投稿に失敗しました。');}
	$uresno=filter_var($uresto,FILTER_VALIDATE_INT);
	$resno = $uresto ? $uresto : $resno;//変数上書き$userdataのレス先を優先する

	$upfile='src/'.$time.'.tmp';
	rename($tempfile, $upfile);
}

if($upfile){//PNG→JPEG自動変換
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
$sub=str_replace(["\r\n","\r","\n",],'',$sub);
$name=str_replace(["\r\n","\r","\n",],'"\n"',$name);
$com=str_replace(["\r\n","\r","\n",],'"\n"',$com);
$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット

setcookie("namec",$name,time()+(60*60*24*30),0,"",false,true);

if(!$name){
	$name='anonymous';
}

if(!$imgfile&&!$com){
error('何か書いて下さい。');
}

//全体ログを開く
$alllog_arr=file('./log/alllog.txt');
$alllog=end($alllog_arr);
$line='';
//書き込むログの書式
if($resno){//レスの時はスレッド別ログに追記
	$r_line = "$resno\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$tool\t$time\t$host\tres\n";
	if(is_file('./log/'.$resno.'.txt')){
		error('投稿に失敗しました。');
	}
	file_put_contents('./log/'.$resno.'.txt',$r_line,FILE_APPEND | LOCK_EX);
	chmod('./log/'.$resno.'.txt',0600);	
	foreach($alllog_arr as $i =>$val){
		list($_no)=explode("\t",$val);
		if($resno==$_no){
			$line = $val;//レスが付いたスレッドを$lineに保存。あとから配列に追加して上げる
			unset($alllog_arr[$i]);//レスが付いたスレッドを全体ログからいったん削除
			break;
		}
	}
	
} else{
	list($no)=explode("\t",$alllog);
	if(!$no&&$alllog!==''){
		$no=0;
	}
	//最後の記事ナンバーに+1
	$no=trim($no)+1;
	$line = "$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$tool\t$time\t$host\toya\n";
	if(is_file('./log/'.$no.'.txt')){
		error('投稿に失敗しました。');
	}
	file_put_contents('./log/'.$no.'.txt',$line,LOCK_EX);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
	chmod('./log/'.$no.'.txt',0600);
}
	$alllog_arr[]=$line;//全体ログの配列に追加

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

file_put_contents('./log/alllog.txt',$alllog_arr,LOCK_EX);//全体ログに書き込む
chmod('./log/alllog.txt',0600);
//多重送信防止
header('Location: ./');

}
//お絵かき画面
function paint(){
$app = filter_input(INPUT_POST,'app');
$picw = filter_input(INPUT_POST,'picw',FILTER_VALIDATE_INT);
$pich = filter_input(INPUT_POST,'pich',FILTER_VALIDATE_INT);
$usercode = filter_input(INPUT_COOKIE, 'usercode');
setcookie("appc", $app , time()+(60*60*24*30));//アプレット選択
setcookie("picwc", $picw , time()+(60*60*24*30));//幅
setcookie("pichc", $pich , time()+(60*60*24*30));//高さ

switch($app){
		case 'neo':
				$templete='paint_neo.html';
				$tool='neo';
				break;
		
			case 'chi':
				$templete='paint_chi.html';
				$tool='chi';
				break;
			
			default:
					return;
}
			
	include __DIR__.'/template/'.$templete;

}
// お絵かきコメント 
function paintcom(){
	$token=get_csrf_token();
	$userip = get_uip();
	$namec = filter_input(INPUT_COOKIE,'namec');
	$usercode = filter_input(INPUT_COOKIE,'usercode');
	//テンポラリ画像リスト作成
	$tmplist = [];
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
			$fp = fopen(TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,) = explode("\t", rtrim($userdata));
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			if(is_file(TEMP_DIR.$file_name.$imgext)){ //画像があればリストに追加
				$tmplist[] = $ucode."\t".$uip."\t".$file_name.$imgext;
			}
		}
	}
	closedir($handle);
	$tmp = [];
	if(count($tmplist)!==0){
		foreach($tmplist as $tmpimg){
			list($ucode,$uip,$ufilename) = explode("\t", $tmpimg);
			if($ucode == $usercode||$uip == $userip){
				$tmp[] = $ufilename;
			}
		}
	}

	if(count($tmp)!==0){
		$pictmp = 2;
		sort($tmp);
		reset($tmp);
		foreach($tmp as $tmpfile){
			$tmp_img['src'] = TEMP_DIR.$tmpfile;
			$tmp_img['srcname'] = $tmpfile;
			$tmp_img['date'] = date("Y/m/d H:i", filemtime($tmp_img['src']));
			$out['tmp'][] = $tmp_img;
		}
	}
	// HTML出力
	$templete='paint_com.html';
	include __DIR__.'/template/'.$templete;
}

//記事削除
function del(){
	session_sta();
	$adminmode=isset($_SESSION['admin'])&&($_SESSION['admin']==='admin_mode');
	if(!$adminmode){
		return error('失敗しました。');
	}
	$id=filter_input(INPUT_POST,'delid');
	$no=filter_input(INPUT_POST,'delno');
	$page=filter_input(INPUT_POST,'postpage');

	$alllog_arr=file('./log/alllog.txt');
	if(is_file("./log/$no.txt")){
		$line=file("./log/$no.txt");
		foreach($line as $i =>$val){

			list($no,$sub,$name,$com,$imgfile,$w,$h,$tool,$time,$host,$oya)=explode("\t",$val);
			if($id==$time){
				if(trim($oya)=='oya'){//スレッド削除

				//スレッドの画像を削除	
					$fp = fopen("./log/$no.txt", "r");//個別スレッドのログを開く
					while ($line = fgetcsv($fp, 0, "\t")) {
					list(,,,,$imgfile,)=$line;
					safe_unlink('src/'.$imgfile);//画像削除
					}
			
					safe_unlink('./log/'.$no.'.txt');
					foreach($alllog_arr as $i =>$val){
						list($_no)=explode("\t",$val);
						if($no==$_no){
							unset($alllog_arr[$i]);
						}
					}
				}else{
					unset($line[$i]);
					safe_unlink('src/'.$imgfile);//画像削除
					file_put_contents('./log/'.$no.'.txt',$line,LOCK_EX);
				}
			}
		file_put_contents('./log/alllog.txt',$alllog_arr,LOCK_EX);
		//多重送信防止
		header('Location: ./?page='.$page);


		}
	}
}


//表示
function view($page=0){
if(!isset($page)){
	$page=0;
}
global $pagedef,$boardname,$max_res,$pmax_w,$pmax_h; 
 ;


$alllog_arr=file('./log/alllog.txt');//全体ログを読み込む
$count_alllog=count($alllog_arr);
krsort($alllog_arr);

//ページ番号から1ページ分のスレッド分とりだす
$alllog_arr=array_slice($alllog_arr,$page,$pagedef,false);
//oyaのループ
foreach($alllog_arr as $oya => $alllog){
	
	list($no)=explode("\t",$alllog);
	//個別スレッドのループ
	if(is_file("./log/$no.txt")){
		$fp = fopen("./log/$no.txt", "r");//個別スレッドのログを開く
		while ($line = fgetcsv($fp, 0, "\t")) {
		list($no,$sub,$name,$com,$imgfile,$w,$h,$tool,$time,$host)=$line;
		$res=[];
		switch($tool){
			case 'neo':
				$tool='PaintBBS NEO';
				break;
			case 'chi':
				$tool='ChickenPaint';
				break;
			case 'upload':
				$tool='アップロード';
				break;
			default:
				'';
		}
		$res=[
			'no' => $no,
			'sub' => $sub,
			'name' => $name,
			'com' => $com,
			'img' => $imgfile,
			'w' => $w,
			'h' => $h,
			'tool' => $tool,
			'time' => $time,
			'host' => $host,
		];

		$res['com']=str_replace('"\n"',"\n",$res['com']);
		$out[$oya][]=$res;
		}	
	fclose($fp);
	}

}

//管理者判定処理
session_sta();
$adminmode=isset($_SESSION['admin'])&&($_SESSION['admin']==='admin_mode');


//Cookie
$namec=(string)filter_input(INPUT_COOKIE,'namec');
$appc=(string)filter_input(INPUT_COOKIE,'appc');
$picwc=(string)filter_input(INPUT_COOKIE,'picwc');
$picwh=(string)filter_input(INPUT_COOKIE,'pichc');

//token
$token=get_csrf_token();

// HTML出力
$templete='main.html';
include __DIR__.'/template/'.$templete;

}

