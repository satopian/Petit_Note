<?php
//Petit Note (c)さとぴあ @satopian 2021
//1スレッド1ログファイル形式のスレッド式画像掲示板
require_once(__DIR__.'/config.php');	
require_once(__DIR__.'/function.php');

$mode = filter_input(INPUT_POST,'mode');
$mode = $mode ? $mode :filter_input(INPUT_GET,'mode');
$page=filter_input(INPUT_GET,'page');
$resno=filter_input(INPUT_GET,'resno');
$postpage=filter_input(INPUT_POST,'postpage');

$usercode = t(filter_input(INPUT_COOKIE, 'usercode'));//nullならuser-codeを発行
$userip = get_uip();
//user-codeの発行
if(!$usercode){//falseなら発行
	$usercode = substr(crypt(md5($userip.date("Ymd", time())),'id'),-12);
	//念の為にエスケープ文字があればアルファベットに変換
	$usercode = strtr($usercode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~","ABCDEFGHIJKLMNOabcdefghijklmn");
}
setcookie("usercode", $usercode, time()+(86400*365));//1年間

//初期化
init();
deltemp();//テンポラリ自動削除

switch($mode){
	case 'regist':
		return post();
	case 'paint':
		return paint();
	case 'paintcom':
		return paintcom();
	case 'del':
		return del();
	case 'userdel':
		return userdel_mode();
	case 'admin':
		return admin();
	case 'res':
		return res($resno);
	case 'diary':
		return diary();
	case 'aikotoba':
		return aikotoba();
	case 'logout':
		session_sta();
		unset($_SESSION['admin']);
		unset($_SESSION['userdel']);
		if($resno){
			return header('Location: ./?resno='.$resno);	
		}
		$page = $postpage ?? $page; 
		return header('Location: ./?page='.$page);
	default:
		if($resno){
			return res($resno);
		}
		return view($page);
}

//投稿処理
function post(){	
global $max_log,$max_res,$max_kb,$use_aikotoba,$use_upload,$use_diary;
if($use_aikotoba){
	check_aikotoba();
}
check_csrf_token();

//POSTされた内容を取得
$usercode = t(filter_input(INPUT_COOKIE, 'usercode'));
$userip =t(get_uip());
//ホスト取得
$host = t(gethostbyaddr($userip));

$sub = t((string)filter_input(INPUT_POST,'sub'));
$name = t((string)filter_input(INPUT_POST,'name'));
$com = t((string)filter_input(INPUT_POST,'com'));
$resno = t((string)filter_input(INPUT_POST,'resno'));
if($resno&&is_file('./log/'.$resno.'.log')&&(count(file('./log/'.$resno.'.log'))>$max_res)){//レスの時はスレッド別ログに追記
	error('最大レス数を超過しています。');
}
$adminpost='';
if(!$resno && $use_diary){
	$adminpost=isset($_SESSION['diary'])&&($_SESSION['diary']==='admin_post');
	if(!$adminpost){
		error('日記にログインしていません。');
	}
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
if ($tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK &&
$use_upload){
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
$picfile = t(filter_input(INPUT_POST, 'picfile'));
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
	// ワークファイル削除
	safe_unlink(TEMP_DIR.$picfile.".dat");
}


if(!$sub){
	$sub='無題';
}
$sub=str_replace(["\r\n","\r","\n",],'',$sub);
$name=str_replace(["\r\n","\r","\n",],'"\n"',$name);
$com=str_replace(["\r\n","\r","\n",],'"\n"',$com);
$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット


if(!$name){
	$name='anonymous';
}

if(!$upfile&&!$com){
error('何か書いて下さい。');
}

$pwd=t(filter_input(INPUT_POST, 'pwd'));//パスワードを取得
$pwd=$pwd ? $pwd : t(filter_input(INPUT_COOKIE,'pwc'));//未入力ならCookieのパスワード
if(!$pwd){//それでも$pwdが空なら
	srand((double)microtime()*1000000);
	$pwd = substr(rand(), 0, 8);
}
$hash = $pwd ? password_hash($pwd,PASSWORD_BCRYPT,['cost' => 5]) : '';

setcookie("namec",$name,time()+(60*60*24*30),0,"",false,true);
setcookie("pwdc",$pwd,time()+(60*60*24*30),0,"",false,true);


if($upfile){
	if($filesize > 512 * 1024){//指定サイズを超えていたら
		if ($im_jpg = png2jpg($upfile)) {//PNG→JPEG自動変換

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

	//全体ログを開く
	$fp=fopen("./log/alllog.log","r+");
	flock($fp, LOCK_EX);
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		$alllog_arr[]=$_line;	
	}
	$img_md5=md5_file('src/'.$imgfile);

	//同じ画像チェック アップロード画像のみチェックしてお絵かきはチェックしない
	if($pictmp!==2){
		$chk_log_arr=array_reverse($alllog_arr,false);
		$chk_log_arr=array_slice($alllog_arr,0,20,false);
		foreach($chk_log_arr as $chk_log){
			list($chk_resno)=explode("\t",$chk_log);
			if(is_file("./log/{$chk_resno}.log")){
			$cp=fopen("./log/{$chk_resno}.log","r+");
				while($line=fgetcsv($cp,0,"\t")){
					list($no_,$sub_,$name_,$com_,$imgfile_,$w_,$h_,$log_md5,$tool_,$time_,$host_,$hash_,$oya_)=$line;
					if($log_md5 === $img_md5){
					unlink('src/'.$imgfile);
					error('同じ画像がありました。');
					};
				}
			}
		}
	}
$no_arr = [];
$max_no=0;
$md5=[];
foreach($alllog_arr as $i => $_alllog){
	list($log_no,)=explode("\t",$_alllog);
	$no_arr[]=$log_no;
}

if($no_arr){
	$max_no=max($no_arr);
}else{
	$max_no=0;
}
//書き込むログの書式
$line='';
if($resno){//レスの時はスレッド別ログに追記
	$r_line = "$resno\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$img_md5\t$tool\t$time\t$host\t$hash\tres\n";
	if(!is_file('./log/'.$resno.'.log')){
		error('投稿に失敗しました。');
	}
	file_put_contents('./log/'.$resno.'.log',$r_line,FILE_APPEND | LOCK_EX);
	chmod('./log/'.$resno.'.log',0600);	
	foreach($alllog_arr as $i =>$val){
		list($_no)=explode("\t",$val);
		if($resno==$_no){
			$line = $val;//レスが付いたスレッドを$lineに保存。あとから配列に追加して上げる
			unset($alllog_arr[$i]);//レスが付いたスレッドを全体ログからいったん削除
			break;
		}
	}
	
} else{
	//最後の記事ナンバーに+1
	$no=$max_no+1;
	$line = "$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$img_md5\t$tool\t$time\t$host\t$hash\toya\n";
	file_put_contents('./log/'.$no.'.log',$line,FILE_APPEND | LOCK_EX);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
	chmod('./log/'.$no.'.log',0600);
}
	$alllog_arr[]=$line;//全体ログの配列に追加

	if(!$max_log){
		error('最大スレッド数が設定されていません。');
	}

	$countlog=count($alllog_arr);
	for($i=0;$i<$countlog-$max_log;++$i){//$max_logスレッド分残して削除
		if($alllog_arr[$i]===''){
			continue;
		}
		list($_no,,,,,$imgfile,)=explode("\t",$alllog_arr[$i]);
		if(is_file("./log/$_no.log")){
	
			$dp = fopen("./log/$_no.log", "r");//個別スレッドのログを開く
			flock($dp, LOCK_EX);

			while ($line = fgetcsv($fp, 0, "\t")) {
			list(,,,,$imgfile,)=$line;
			safe_unlink('src/'.$imgfile);//画像削除
		}
		closeFile($dp);
		}	
		safe_unlink('./log/'.$_no.'.log');//スレッド個別ログファイル削除
		unset($alllog_arr[$i]);//全体ログ記事削除
	}
	$alllog=implode("",$alllog_arr);
	writeFile ($fp, $alllog);
	closeFile($fp);
	
	chmod('./log/alllog.log',0600);
	//多重送信防止
	return header('Location: ./');

}
//お絵かき画面
function paint(){
$app = filter_input(INPUT_POST,'app');
$picw = filter_input(INPUT_POST,'picw',FILTER_VALIDATE_INT);
$pich = filter_input(INPUT_POST,'pich',FILTER_VALIDATE_INT);
$usercode = t(filter_input(INPUT_COOKIE, 'usercode'));
$resto = t(filter_input(INPUT_POST, 'resto'));

setcookie("appc", $app , time()+(60*60*24*30));//アプレット選択
setcookie("picwc", $picw , time()+(60*60*24*30));//幅
setcookie("pichc", $pich , time()+(60*60*24*30));//高さ

switch($app){
	case 'chi'://ChickenPaint

		$tool='chi';
		// HTML出力
		$templete='paint_chi.html';
		return include __DIR__.'/template/'.$templete;

	case 'neo'://PaintBBS NEO

		$tool='neo';
		$appw = $picw + 150;//PaintBBSの時の幅
		$apph = $pich + 172;//PaintBBSの時の高さ
		if($apph < 560){$apph = 560;}//共通の最低高
		//動的パレット
		$lines = file('palette.txt');//初期パレット
		$initial_palette = 'Palettes[0] = "#000000\n#FFFFFF\n#B47575\n#888888\n#FA9696\n#C096C0\n#FFB6FF\n#8080FF\n#25C7C9\n#E7E58D\n#E7962D\n#99CB7B\n#FCECE2\n#F9DDCF";';
		$pal=[];
		$DynP=[];
		foreach ( $lines as $i => $line ) {
			$line=str_replace(["\r","\n","\t"],"",$line);
			$line=h($line);
			list($pid,$pname,$pal[0],$pal[2],$pal[4],$pal[6],$pal[8],$pal[10],$pal[1],$pal[3],$pal[5],$pal[7],$pal[9],$pal[11],$pal[12],$pal[13]) = explode(",", $line);
			$DynP[]=$pname;
			$p_cnt=$i+1;
			$palettes = 'Palettes['.$p_cnt.'] = "#';
			ksort($pal);
			$palettes.=implode('\n#',$pal);
			$palettes.='";';
			$arr_pal[$i] = $palettes;
		}
		$palettes=$initial_palette.implode('',$arr_pal);
		$palsize = count($DynP) + 1;
		foreach ($DynP as $p){
			$arr_dynp[] = $p;
		}
		// HTML出力
		$templete='paint_neo.html';
		return include __DIR__.'/template/'.$templete;

	default:
		return;
}
			
}
// お絵かきコメント 
function paintcom(){
	global $use_aikotoba;
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
	$aikotoba=isset($_SESSION['aikotoba'])&&($_SESSION['aikotoba']==='aikotoba');
	if(!$use_aikotoba){
		$aikotoba=true;
	}

	// HTML出力
	$templete='paint_com.html';
	return include __DIR__.'/template/'.$templete;
}
//記事削除
function del(){
	$pwd=filter_input(INPUT_POST,'pwd');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	session_sta();
	$adminmode=isset($_SESSION['admin'])&&($_SESSION['admin']==='admin_mode');
	$userdel_mode=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	if(!($adminmode||($userdel_mode&&$pwd))){
		return error('失敗しました。');
	}
	$id_and_no=filter_input(INPUT_POST,'id_and_no');
	$id=$no='';
	if($id_and_no){
		list($id,$no)=explode(",",filter_input(INPUT_POST,'id_and_no'));
		$no=trim($no);
	}
	
	$page=filter_input(INPUT_POST,'postpage');
	
	$fp=fopen("./log/alllog.log","r+");
	flock($fp, LOCK_EX);
	while ($_line = fgets($fp)) {
		$alllog_arr[]=$_line;	
	}

	if(is_file("./log/$no.log")){
		
		$rp=fopen("./log/$no.log","r+");
		flock($rp, LOCK_EX);
		while ($r_line = fgets($rp)) {
			$line[]=$r_line;
		}
		
		foreach($line as $i =>$val){

			list($no,$sub,$name,$com,$imgfile,$w,$h,$log_md5,$tool,$time,$host,$hash,$oya)=explode("\t",$val);
			if($id==$time){
			
				if(!$adminmode){
					if(!password_verify($pwd,$hash)){
					// if(!($pwd=='hoge')){
						return error('失敗しました。');}
				}
				if(trim($oya)=='oya'){//スレッド削除
					while ($line = fgetcsv($rp, 0, "\t")) {
						list(,,,,$imgfile,)=$line;
						safe_unlink('src/'.$imgfile);//画像削除
					}
				
						foreach($alllog_arr as $i =>$val){
							list($_no)=explode("\t",$val);
							if($no==$_no){
								unset($alllog_arr[$i]);
							}
						}
						$alllog=implode("",$alllog_arr);
						writeFile($fp,$alllog);
						closeFile ($rp);
						safe_unlink('./log/'.$no.'.log');
			
				}else{
						unset($line[$i]);
						safe_unlink('src/'.$imgfile);//画像削除
						$line=implode("",$line);
						writeFile ($rp, $line);
						closeFile ($rp);

				}
			}
			
		}
		closeFile ($fp);

	}
	//多重送信防止
	if(filter_input(INPUT_POST,'resmode')){
		return header('Location: ./?mode=res&resno='.filter_input(INPUT_POST,'resno'));
	}
	return header('Location: ./?page='.$page);
}

//通常表示
function view($page=0){
global $use_aikotoba,$use_upload,$home,$pagedef;
global $pagedef,$boardname,$max_res,$pmax_w,$pmax_h,$use_miniform,$use_diary; 

if(!isset($page)||!$page){
	$page=0;
}

$alllog_arr=file('./log/alllog.log');//全体ログを読み込む
$count_alllog=count($alllog_arr);
krsort($alllog_arr);

//ページ番号から1ページ分のスレッド分とりだす
$alllog_arr=array_slice($alllog_arr,(int)$page,$pagedef,false);
//oyaのループ
foreach($alllog_arr as $oya => $alllog){
	
	list($no)=explode("\t",$alllog);
	//個別スレッドのループ
	if(!is_file("./log/$no.log")){
	continue;	
	}
	$res=[];
		$fp = fopen("./log/$no.log", "r");//個別スレッドのログを開く
		while ($line = fgetcsv($fp, 0, "\t")) {
			$res = create_res($line);//$lineから、情報を取り出す
		$out[$oya][]=$res;
		}	
	fclose($fp);

}

//管理者判定処理
session_sta();
$adminmode=isset($_SESSION['admin'])&&($_SESSION['admin']==='admin_mode');
$aikotoba=isset($_SESSION['aikotoba'])&&($_SESSION['aikotoba']==='aikotoba');
$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
$adminpost=isset($_SESSION['diary'])&&($_SESSION['diary']==='admin_post');

if(!$use_aikotoba){
	$aikotoba=true;
}

//Cookie
$namec=(string)filter_input(INPUT_COOKIE,'namec');
$appc=(string)filter_input(INPUT_COOKIE,'appc');
$picwc=(string)filter_input(INPUT_COOKIE,'picwc');
$picwh=(string)filter_input(INPUT_COOKIE,'pichc');

//token
$token=get_csrf_token();

// HTML出力
$templete='main.html';
return include __DIR__.'/template/'.$templete;

}
//レス画面
function res ($resno){
	global $use_aikotoba,$use_upload,$home,$pagedef;
	global $pagedef,$boardname,$max_res,$pmax_w,$pmax_h,$use_diary; 
	$page=0;
	$resno=filter_input(INPUT_GET,'resno');
	if(!is_file("./log/$resno.log")){
		error('スレッドがありません');	
		}
		$res=[];
			$fp = fopen("./log/$resno.log", "r");//個別スレッドのログを開く
			while ($line = fgetcsv($fp, 0, "\t")) {
				$res = create_res($line);//$lineから、情報を取り出す
			$out[0][]=$res;
			}	
		fclose($fp);
		// }
//管理者判定処理
session_sta();
$adminmode=isset($_SESSION['admin'])&&($_SESSION['admin']==='admin_mode');
$aikotoba=isset($_SESSION['aikotoba'])&&($_SESSION['aikotoba']==='aikotoba');
$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
$adminpost=isset($_SESSION['diary'])&&($_SESSION['diary']==='admin_post');
if(!$use_aikotoba){
	$aikotoba=true;
}

//Cookie
$namec=(string)filter_input(INPUT_COOKIE,'namec');
$appc=(string)filter_input(INPUT_COOKIE,'appc');
$picwc=(string)filter_input(INPUT_COOKIE,'picwc');
$picwh=(string)filter_input(INPUT_COOKIE,'pichc');

//token
$token=get_csrf_token();
$templete='res.html';
return include __DIR__.'/template/'.$templete;
	
}
	
	