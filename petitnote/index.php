<?php
//Petit Note (c)さとぴあ @satopian 2021
//1スレッド1ログファイル形式のスレッド式画像掲示板
require_once(__DIR__.'/config.php');	
require_once(__DIR__.'/functions.php');
require_once(__DIR__.'/thumbnail_gd.php');
require_once(__DIR__.'/noticemail.inc');

//テンプレート
$skindir='template/'.$skindir;

$petit_ver='v0.9.8.16';
$petit_lot='lot.211107';

if(!$max_log){
	error('最大スレッド数が設定されていません。');
}
if(!isset($thumbnail_gd_ver)||$thumbnail_gd_ver<2){
	error('thumbnail_gd.phpのバージョンが古いため動作しません。');
}

$max_log=($max_log<500) ? 500 : $max_log;//最低500スレッド
$max_com= isset($max_com) ? $max_com : 1000;

$mode = filter_input(INPUT_POST,'mode');
$mode = $mode ? $mode :filter_input(INPUT_GET,'mode');
$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
$page= $page ? $page : 0; 
$resno=filter_input(INPUT_GET,'resno');

$usercode = t(filter_input(INPUT_COOKIE, 'usercode'));//nullならuser-codeを発行
$userip = get_uip();
//user-codeの発行
if(!$usercode){//falseなら発行
	$usercode = substr(crypt(md5($userip.date("Ymd", time())),'id'),-12);
	//念の為にエスケープ文字があればアルファベットに変換
	$usercode = strtr($usercode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~","ABCDEFGHIJKLMNOabcdefghijklmn");
}
setcookie("usercode", $usercode, time()+(86400*365),"","",false,true);//1年間

//初期化
init();
deltemp();//テンポラリ自動削除

switch($mode){
	case 'regist':
		if($denny_all_posts){
			return view();	
		}
		return post();
	case 'paint':
		return paint();
	case 'paintcom':
		return paintcom();
	case 'pchview':
		return pchview();
	case 'to_continue':
		return to_continue();
		case 'contpaint':
			$type = filter_input(INPUT_POST, 'type');
			if($type==='rep') check_cont_pass();
			return paint();
	case 'picrep':
		return img_replace();

	case 'before_del':
		return confirmation_before_deletion();
	case 'edit_form':
		return edit_form();
	case 'edit':
		return edit();
	case 'del':
		return del();
	case 'userdel':
		return userdel_mode();
	case 'adminin':
		return admin_in();
	case 'admin_del':
		return admin_del();
	case 'adminpost':
		return adminpost();
	case 'aikotoba':
		return aikotoba();
	case 'view_nsfw':
		return view_nsfw();
	case 'logout_admin':
		return logout_admin();
	case 'logout':
		return logout();
	case 'catalog':
		return catalog($page);
	default:
		if($resno){
			return res($resno);
		}
		return view($page);
}

//投稿処理
function post(){
	global $max_log,$max_res,$max_kb,$use_aikotoba,$use_upload,$use_res_upload,$use_diary,$max_w,$max_h,$use_thumb;
	global $allow_coments_only,$res_max_w,$res_max_h,$admin_pass,$name_input_required,$max_com,$max_px;

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
	$url = t((string)filter_input(INPUT_POST,'url',FILTER_VALIDATE_URL));
	$resto = t((string)filter_input(INPUT_POST,'resto',FILTER_VALIDATE_INT));
	$pwd=t(filter_input(INPUT_POST, 'pwd'));//パスワードを取得
	$sage = filter_input(INPUT_POST,'sage',FILTER_VALIDATE_BOOLEAN);
	$check_elapsed_days=false;

	//NGワードがあれば拒絶
	Reject_if_NGword_exists_in_the_post();

	//制限
	if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
	if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
	if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
	if(!$url||preg_match("/\A\s*\z/u",$url)) $url="";

	if(strlen($sub) > 80) error('題名が長すぎます。');
	if(strlen($name) > 30) error('名前が長すぎます。');
	if(strlen($com) > $max_com) error('本文が長すぎます。');
	if(strlen($url) > 100) error('urlが長すぎます。');
	if(strlen($pwd) > 100) error('パスワードが長すぎます。');
	$pwd=$pwd ? $pwd : t(filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード
	if(!$pwd){//それでも$pwdが空なら
		srand((double)microtime()*1000000);
		$pwd = substr(md5(uniqid(rand())),2,15);
		$pwd = strtr($pwd,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~","ABCDEFGHIJKLMNOabcdefghijklmn");
	}
	if(strlen($pwd) < 6) error('パスワードが短すぎます。最低6文字。');

	$upfile='';
	$imgfile='';
	$w='';
	$h='';
	$tool='';
	$time = time();
	$time = $time.substr(microtime(),2,3);	//投稿時刻

	$adminpost=adminpost_valid();

	//ファイルアップロード
	$tempfile = isset($_FILES['imgfile']['tmp_name']) ? $_FILES['imgfile']['tmp_name'] : ''; // 一時ファイル名
	$filesize = isset($_FILES['imgfile']['size']) ? $_FILES['imgfile']['size'] :'';
	if($tempfile && in_array($_FILES['imgfile']['error'],[1,2])){//容量オーバー
		error('ファイルサイズが大きすぎます。');
	} 
	if($filesize > $max_kb*1024){
		error("アップロードに失敗しました。ファイル容量が{$max_kb}kbを越えています。");
	}
	if ($tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK && ($use_upload || $adminpost)){

		if($resto && $tempfile && !$use_res_upload && !$adminpost){
			safe_unlink($tempfile);
			error('日記にログインしていません。');
		}

		$img_type = isset($_FILES['imgfile']['type']) ? $_FILES['imgfile']['type'] : '';

		if (!in_array($img_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])) {
			safe_unlink($tempfile);
			error('対応していないフォーマットです。');
		}
		$upfile=IMG_DIR.$time.'.tmp';
		move_uploaded_file($tempfile,$upfile);
		$tool = 'upload'; 
		
	}

	//お絵かきアップロード
	$pictmp = filter_input(INPUT_POST, 'pictmp',FILTER_VALIDATE_INT);
	list($picfile,) = explode(",",filter_input(INPUT_POST, 'picfile'));
	$painttime ='';

	if($pictmp===2){//ユーザーデータを調べる

		if(!$picfile) error('投稿に失敗しました。');
		$tempfile = TEMP_DIR.$picfile;
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
		$uresto=filter_var($uresto,FILTER_VALIDATE_INT);
		$resto = $uresto ? $uresto : $resto;//変数上書き$userdataのレス先を優先する
		//描画時間を$userdataをもとに計算
		if($starttime && is_numeric($starttime)){
			$painttime=(int)$postedtime-(int)$starttime;
		}
		if($resto && $picfile && !$use_res_upload && !$adminpost){
			error('日記にログインしていません。');
		}

	}

	if($resto && !is_file(LOG_DIR."$resto.log")){//エラー処理
		if($pictmp!==2){//お絵かきではない時は
			safe_unlink($upfile);
			error('記事がありません。');
		}
		$resto='';//レス先がないお絵かきは新規投稿扱いにする。
	}

	if($resto && is_file(LOG_DIR."$resto.log")){//エラー処理
			
		$rp=fopen(LOG_DIR."$resto.log","r");
		$line = fgets($rp);
			list($n_,$oyasub,$n_,$v_,$c_,$u_,$img_,$_,$_,$thumb_,$pt_,$md5_,$to_,$pch_,$postedtime,$fp_time_,$h_,$uid_,$h_,$_)=explode("\t",$line);
			$check_elapsed_days = check_elapsed_days($postedtime);
		closeFile ($rp);

		if($pictmp===2){//お絵かきの時は新規投稿にする

			if($resto && !$check_elapsed_days){//お絵かきの時に日数を経過していたら新規投稿。
				$resto='';
			}
			if($resto&&(count(file(LOG_DIR.$resto.'.log'))>$max_res)){//お絵かきの時に最大レス数を超過していたら新規投稿。
				$resto='';
			}
		}
		//お絵かき以外。
		if($resto && !$check_elapsed_days){//指定した日数より古いスレッドには投稿できない。
			safe_unlink($upfile);
			error('このスレッドには投稿できません。');
		}
		if($resto&&(count(file(LOG_DIR.$resto.'.log'))>$max_res)){//最大レス数超過。
			safe_unlink($upfile);
			error('最大レス数を超過しています。');
			}

		$sub='Re: '.$oyasub;

	}

	if(!$resto && $use_diary && !$adminpost){
			safe_unlink($upfile);
			error('日記にログインしていません。');
	}

	//お絵かきアップロード
	if($pictmp===2 && is_file($tempfile)){

		$upfile=IMG_DIR.$time.'.tmp';
			copy($tempfile, $upfile);
			chmod($upfile,0606);
			$filesize=filesize($upfile);
	}

	$sub=(!$sub) ? '無題' : $sub;
	$sub=str_replace(["\r\n","\r","\n","\t"],'',$sub);
	$name=str_replace(["\r\n","\r","\n","\t"],'',$name);
	$url=str_replace(["\r\n","\r","\n","\t"],'',$url);
	$com=str_replace(["\r\n","\r","\n"],"\n",$com);
	$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット
	$com=str_replace("\n",'"\n"',$com);
	$com=str_replace("\t",'',$com);

	if(!$name){
		if($name_input_required){
			error('名前がありません。');
		}else{
			$name='anonymous';
		}
	}

	if(!$upfile&&!$com){
	error('何か書いて下さい。');
	}

	if(!$resto && !$allow_coments_only && !$upfile && !$adminpost){
	error('画像がありません。');
	}

	$hash = $pwd ? password_hash($pwd,PASSWORD_BCRYPT,['cost' => 5]) : '';

	setcookie("namec",$name,time()+(60*60*24*30),"","",false,true);
	setcookie("urlc",$url,time()+(60*60*24*30),"","",false,true);
	setcookie("pwdc",$pwd,time()+(60*60*24*30),"","",false,true);


	//ユーザーid
	$userid = t(getId($userip));

	$verified = ($adminpost||($admin_pass && $pwd===$admin_pass)) ? 'adminpost' : ''; 

	//全体ログを開く
	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		$alllog_arr[]=$_line;	
	}
	$img_md5='';

	$chk_log_arr=array_slice($alllog_arr,0,5,false);
	$chk_com=[];
	foreach($chk_log_arr as $chk_log){
		list($chk_resno)=explode("\t",$chk_log);
		if(is_file(LOG_DIR."{$chk_resno}.log")){
		$cp=fopen(LOG_DIR."{$chk_resno}.log","r");
		while($line=fgets($cp)){
			list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=explode("\t",$line);
			if($host === $host_){
					$chk_com[$time_]=$line;
				};
			}
		}
	}
	foreach($chk_com as $line){
		list($_no_,$_sub_,$_name_,$_verified_,$_com_,$_url_,$_imgfile_,$_w_,$_h_,$_thumbnail_,$_painttime_,$_log_md5_,$_tool_,$_pchext_,$_time_,$_first_posted_time_,$_host_,$_userid_,$_hash_,$_oya_)=explode("\t",$line);
		if($com && ($com === $_com_)){
			safe_unlink($upfile);
			return error('同じコメントがありました。');
		}
		// 画像アップロードの場合
		if($upfile && time()-substr($_time_,0,-3)<30){
			safe_unlink($upfile);
			return error('少し待ってください。');

		}
		//コメントの場合
		if(time()-substr($_time_,0,-3)<15){
			safe_unlink($upfile);
			return error('少し待ってください。');
		}
	}
	if($upfile && is_file($upfile)){

		if($pictmp!==2){//実態データの縮小
		$max_px=isset($max_px) ? $max_px : 1024;
			thumb(IMG_DIR,$time.'.tmp',$time,$max_px,$max_px,['toolarge'=>1]);
		}	
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
		if (!$ext) {
			safe_unlink($upfile);
			error('対応していないフォーマットです。');
		}
	
		$imgfile=$time.$ext;

		rename($upfile,IMG_DIR.$imgfile);
	}

	//同じ画像チェック アップロード画像のみチェックしてお絵かきはチェックしない
	if($pictmp!=2 && $imgfile && is_file(IMG_DIR.$imgfile)){
		$img_md5=md5_file(IMG_DIR.$imgfile);
		$chk_log_arr=array_slice($alllog_arr,0,20,false);
		foreach($chk_log_arr as $chk_log){
			list($chk_resno)=explode("\t",$chk_log);
			if(is_file(LOG_DIR."{$chk_resno}.log")){
			$cp=fopen(LOG_DIR."{$chk_resno}.log","r");
			while($line=fgets($cp)){
				list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=explode("\t",$line);
				
					if($log_md5 === $img_md5){
						safe_unlink(IMG_DIR.$imgfile);
						return error('同じ画像がありました。');
					};
				}
			}
		}
	}
	$src='';
	$pchext = '';
	if($pictmp===2 && $imgfile){
		//PCHファイルアップロード
		if ($pchext = check_pch_ext(TEMP_DIR.$picfile)) {

			$src = TEMP_DIR.$picfile.$pchext;
			$dst = IMG_DIR.$time.$pchext;
			if(copy($src, $dst)){
				chmod($dst,0606);
			}
		}

		//chiファイルアップロード
		if(is_file(TEMP_DIR.$picfile.'.chi')){
			$pchext = '.chi';
			$src = TEMP_DIR.$picfile.'.chi';
			$dst = IMG_DIR.$time.'.chi';
			if(copy($src, $dst)){
				chmod($dst,0606);
			}
		}
	}
	$thumbnail='';
	if($imgfile && is_file(IMG_DIR.$imgfile)){
		
		$max_w = $resto ? $res_max_w : $max_w; 
		$max_h = $resto ? $res_max_h : $max_h; 
		//縮小表示
		list($w,$h)=image_reduction_display($w,$h,$max_w,$max_h);
		//サムネイル
		if($use_thumb){
			if(thumb(IMG_DIR,$imgfile,$time,$max_w,$max_h)){
				$thumbnail='thumbnail';
			}
		}
	}

	$no_arr = [];
	foreach($alllog_arr as $i => $_alllog){
		list($log_no,)=explode("\t",$_alllog);
		$no_arr[]=$log_no;
	}

	$max_no=0;
	if($no_arr){
		$max_no=max($no_arr);
	}
	//書き込むログの書式
	$line='';

	if($resto){//レスの時はスレッド別ログに追記
		$r_line = "$resto\t$sub\t$name\t$verified\t$com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\tres\n";
		file_put_contents(LOG_DIR.$resto.'.log',$r_line,FILE_APPEND | LOCK_EX);
		chmod(LOG_DIR.$resto.'.log',0600);
		if(!$sage){
			foreach($alllog_arr as $i =>$val){
			list($_no)=explode("\t",$val);
			if($resto==$_no){
				$newline = $val;//レスが付いたスレッドを$newlineに保存。あとから全体ログの先頭に追加して上げる
				unset($alllog_arr[$i]);//レスが付いたスレッドを全体ログからいったん削除
				break;
				}
			}
		}

	} else{
		//最後の記事ナンバーに+1
		$no=$max_no+1;
		$newline = "$no\t$sub\t$name\t$verified\t$com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\toya\n";
		file_put_contents(LOG_DIR.$no.'.log',$newline,LOCK_EX);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
		chmod(LOG_DIR.$no.'.log',0600);
	}

	//保存件数超過処理
	$countlog=count($alllog_arr);
	if($max_log<=$countlog){
		for($i=$max_log-1; $i<$countlog;++$i){

		if(isset($alllog_arr[$i]) && $alllog_arr[$i]===''){
			continue;
		}
		list($d_no,)=explode("\t",$alllog_arr[$i]);
		if(is_file(LOG_DIR."$d_no.log")){

			$dp = fopen(LOG_DIR."$d_no.log", "r");//個別スレッドのログを開く
			flock($dp, LOCK_EX);

			while ($line = fgets($dp)) {
				list($d_no,$_sub,$_name,$_verified,$_com,$_url,$d_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$d_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",$line);

			delete_files ($d_imgfile, $d_time);//一連のファイルを削除

			}
		closeFile($dp);
		}	
		safe_unlink(LOG_DIR.$d_no.'.log');//スレッド個別ログファイル削除
		unset($alllog_arr[$i]);//全体ログ記事削除
		}
	}
	$newline.=implode("",$alllog_arr);


	writeFile ($fp, $newline);
	closeFile($fp);

	chmod(LOG_DIR.'alllog.log',0600);

	//ワークファイル削除
	safe_unlink($src);
	safe_unlink($tempfile);
	safe_unlink(TEMP_DIR.$picfile.".dat");

	global $send_email,$to_mail,$root_url,$boardname;

	if($send_email){
	//template_ini.phpで未定義の時の初期値
	//このままでよければ定義不要
	defined('NOTICE_MAIL_TITLE') or define('NOTICE_MAIL_TITLE', '記事題名');
	defined('NOTICE_MAIL_IMG') or define('NOTICE_MAIL_IMG', '投稿画像');
	defined('NOTICE_MAIL_THUMBNAIL') or define('NOTICE_MAIL_THUMBNAIL', 'サムネイル画像');
	defined('NOTICE_MAIL_ANIME') or define('NOTICE_MAIL_ANIME', 'アニメファイル');
	defined('NOTICE_MAIL_URL') or define('NOTICE_MAIL_URL', '記事URL');
	defined('NOTICE_MAIL_REPLY') or define('NOTICE_MAIL_REPLY', 'へのレスがありました');
	defined('NOTICE_MAIL_NEWPOST') or define('NOTICE_MAIL_NEWPOST', '新規投稿がありました');

		$data['to'] = $to_mail;
		$data['name'] = $name;
		$data['option'][] = 'URL,'.$url;
		$data['option'][] = NOTICE_MAIL_TITLE.','.$sub;
		if($imgfile) $data['option'][] = NOTICE_MAIL_IMG.','.$root_url.IMG_DIR.$imgfile;//拡張子があったら
		if(is_file(THUMB_DIR.$time.'s.jpg')) $data['option'][] = NOTICE_MAIL_THUMBNAIL.','.$root_url.THUMB_DIR.$time.'s.jpg';
		if ($_pch_ext = check_pch_ext(__DIR__.'/'.IMG_DIR.$time)) {
			$data['option'][] = NOTICE_MAIL_ANIME.','.$root_url.IMG_DIR.$time.$_pch_ext;
		}
		if($resto){
			$data['subject'] = '['.$boardname.'] No.'.$resto.NOTICE_MAIL_REPLY;
			$data['option'][] = "\n".NOTICE_MAIL_URL.','.$root_url.'?res='.$resto;
		}else{
			$data['subject'] = '['.$boardname.'] '.NOTICE_MAIL_NEWPOST;
			$data['option'][] = "\n".NOTICE_MAIL_URL.','.$root_url.'?res='.$no;
		}

		$data['comment'] = str_replace('"\n"',"\n",$com);

		noticemail::send($data);

	}

	//多重送信防止
	if($resto){
		return header('Location: ./?resno='.$resto);
	}
	
return header('Location: ./');

}
//お絵かき画面
function paint(){

	global $boardname,$skindir,$pmax_w,$pmax_h;

	$app = filter_input(INPUT_POST,'app');
	$picw = filter_input(INPUT_POST,'picw',FILTER_VALIDATE_INT);
	$pich = filter_input(INPUT_POST,'pich',FILTER_VALIDATE_INT);
	$usercode = t(filter_input(INPUT_COOKIE, 'usercode'));
	$resto = t(filter_input(INPUT_POST, 'resto',FILTER_VALIDATE_INT));
	if(strlen($resto>1000)){
		error('問題が発生しました。');
	}

	if($picw < 300) $picw = 300;
	if($pich < 300) $pich = 300;
	if($picw > $pmax_w) $picw = $pmax_w;
	if($pich > $pmax_h) $pich = $pmax_h;

	setcookie("appc", $app , time()+(60*60*24*30),"","",false,true);//アプレット選択
	setcookie("picwc", $picw , time()+(60*60*24*30),"","",false,true);//幅
	setcookie("pichc", $pich , time()+(60*60*24*30),"","",false,true);//高さ

	$mode = filter_input(INPUT_POST, 'mode');

	$imgfile='';
	$pchfile='';
	$img_chi='';
	$anime=true;
	$rep=false;
	$paintmode='paintcom';

	session_sta();


	$adminpost=adminpost_valid();

	//pchファイルアップロードペイント
	if($adminpost){
		
		$pchfilename = isset($_FILES['pchup']['name']) ? basename($_FILES['pchup']['name']) : '';
		
		$pchtmp=isset($_FILES['pchup']['tmp_name']) ? $_FILES['pchup']['tmp_name'] : '';

		if($pchtmp && in_array($_FILES['pchup']['error'],[1,2])){//容量オーバー
			error('ファイルサイズが大きすぎます。');
		} 

		if ($pchtmp && $_FILES['pchup']['error'] === UPLOAD_ERR_OK){
	
			$time = time().substr(microtime(),2,3);
			$pchext=pathinfo($pchfilename, PATHINFO_EXTENSION);
			$pchext=strtolower($pchext);//すべて小文字に
			//拡張子チェック
			if (!in_array($pchext, ['pch','chi'])) {
				error('アップロードペイントで使用できるファイルはpch、chiです',$pchtmp);
			}
			$pchup = TEMP_DIR.'pchup-'.$time.'-tmp.'.$pchext;//アップロードされるファイル名

			if(move_uploaded_file($pchtmp, $pchup)){//アップロード成功なら続行

				$pchup=TEMP_DIR.basename($pchup);//ファイルを開くディレクトリを固定
				if(!in_array(mime_content_type($pchup),["application/octet-stream","application/gzip"])){
					safe_unlink($pchup);
					error('ファイルの形式が一致しません。');
				}
				if($pchext==="pch"){
					$app='neo';
					$pchfile = $pchup;
				} elseif($pchext==="chi"){
					$app='chi';
					$img_chi = $pchup;
				}
			}
		}
	}



	if($mode==="contpaint"){

		$imgfile = filter_input(INPUT_POST,'imgfile');
		$ctype = filter_input(INPUT_POST, 'ctype');
		$type = filter_input(INPUT_POST, 'type');
		$time = filter_input(INPUT_POST, 'time');

		list($picw,$pich)=getimagesize(IMG_DIR.$imgfile);//キャンバスサイズ
		$_pch_ext = check_pch_ext(IMG_DIR.$time);

		if($ctype=='pch'&& $_pch_ext){//動画から続き
			$pchfile = IMG_DIR.$time.$_pch_ext;
		}

		if($ctype=='img' && is_file(IMG_DIR.$imgfile)){//画像から続き
			$anime=false;
			$animeform = false;
			$anime= false;
			$imgfile = IMG_DIR.$imgfile;
			if(is_file(IMG_DIR.$time.'.chi')){
			$img_chi =IMG_DIR.$time.'.chi';
			}
		}
		
		if($type==='rep'){//画像差し換え
			$rep=true;
			$no = filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
			$pwd = filter_input(INPUT_POST, 'pwd');
			$pwd=$pwd ? $pwd : t(filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード
			if(strlen($pwd) > 100) error('パスワードが長すぎます。');
			if($pwd){
				$pwd=openssl_encrypt ($pwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV);//暗号化
				$pwd=bin2hex($pwd);//16進数に
			}
			$userip = get_uip();
			$paintmode='picrep';
			$id=$time;	//テンプレートでも使用。
			$repcode = substr(crypt(md5($no.$id.$userip.$pwd.date("Ymd", time())),time()),-8);
			//念の為にエスケープ文字があればアルファベットに変換
			$repcode = strtr($repcode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~","ABCDEFGHIJKLMNOabcdefghijklmn");
		}
	}

	$parameter_day = date("Ymd");//JavaScriptのキャッシュ制御

	switch($app){
		case 'chi'://ChickenPaint
		
			$tool='chi';
			// HTML出力
			$templete='paint_chi.html';
			return include __DIR__.'/'.$skindir.$templete;

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
			return include __DIR__.'/'.$skindir.$templete;

		default:
			return error('失敗しました。');
	}

}
// お絵かきコメント 
function paintcom(){
	global $use_aikotoba,$boardname,$home,$skindir;
	$token=get_csrf_token();
	$userip = get_uip();
	$usercode = filter_input(INPUT_COOKIE,'usercode');
	//テンポラリ画像リスト作成
	$tmplist = [];
	$uresto = '';
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
			$fp = fopen(TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,,$starttime,$postedtime,$uresto,$tool) = explode("\t", rtrim($userdata));
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			$uresto = $uresto ? 'res' :''; 
			if(is_file(TEMP_DIR.$file_name.$imgext)){ //画像があればリストに追加
				$tmplist[] = $ucode."\t".$uip."\t".$file_name.$imgext."\t".$uresto;
			}
		}
	}
	closedir($handle);
	$tmps = [];
	if(count($tmplist)!==0){
		foreach($tmplist as $tmpimg){
			list($ucode,$uip,$ufilename,$uresto) = explode("\t", $tmpimg);
			if($ucode == $usercode||$uip == $userip){
				$tmps[] = [$ufilename,$uresto];
			}
		}
	}

	if(count($tmps)!==0){
		$pictmp = 2;
		sort($tmps);
		reset($tmps);
		foreach($tmps as $tmp){
			list($tmpfile,$resto)=$tmp;
			$tmp_img['src'] = TEMP_DIR.$tmpfile;
			$tmp_img['srcname'] = $tmpfile;
			$tmp_img['slect_src_val'] = $tmpfile.','.$resto;
			$tmp_img['date'] = date("Y/m/d H:i", filemtime($tmp_img['src']));
			$out['tmp'][] = $tmp_img;
		}
	}
	$aikotoba=aikotoba_valid();
	if(!$use_aikotoba){
		$aikotoba=true;
	}
	$namec = filter_input(INPUT_COOKIE,'namec');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$urlc=(string)filter_input(INPUT_COOKIE,'urlc');

	// HTML出力
	$templete='paint_com.html';
	return include __DIR__.'/'.$skindir.$templete;
}

//コンティニュー前画面
function to_continue(){

	global $boardname,$use_diary,$use_aikotoba,$set_nsfw,$skindir;

	$appc=(string)filter_input(INPUT_COOKIE,'appc');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');


	$no = filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
	$id = filter_input(INPUT_GET, 'id',FILTER_VALIDATE_INT);

	$flag = false;

	if(is_file(LOG_DIR."$no.log")){
		
		$rp=fopen(LOG_DIR."$no.log","r");
		while ($line = fgets($rp)) {
			list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",$line);
			if($id==$time){
				$flag=true;
				break;
			}
		}
		closeFile ($rp);
	}
	if(!$flag || !$imgfile || !is_file(IMG_DIR.$imgfile)){//画像が無い時は処理しない
		error('記事がありません');
	}
	$picfile = IMG_DIR.$imgfile;
	list($picw, $pich) = getimagesize($picfile);

	$select_app = true;
	$app_to_use = "";
	$ctype_pch = false;

	if(($pchext==='.pch')&&is_file(IMG_DIR.$time.'.pch')){
		$ctype_pch = true;
		$select_app = false;
		$app_to_use = "neo";
		
	}elseif(($pchext==='.chi')&&is_file(IMG_DIR.$time.'.chi')){
		$select_app = false;
		$app_to_use = 'chi';
	}
	//日記判定処理
	session_sta();
	$adminpost=adminpost_valid();
	$aikotoba=aikotoba_valid();

	if(!$use_aikotoba){
	$aikotoba=true;
	}
	// nsfw
	$nsfwc=(string)filter_input(INPUT_COOKIE,'nsfwc');

	// HTML出力
	$templete='continue.html';
	return include __DIR__.'/'.$skindir.$templete;
	
}

// 画像差し換え
function img_replace(){

	global $use_thumb,$skindir,$max_w,$max_h,$res_max_w,$res_max_h;

	$no = t(filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT));
	$id = t(filter_input(INPUT_GET, 'id',FILTER_VALIDATE_INT));
	$pwd = filter_input(INPUT_GET, 'pwd');
	$repcode = filter_input(INPUT_GET, 'repcode');
	$userip = t(get_uip());
	//ホスト取得
	$host = t(gethostbyaddr($userip));
	//ユーザーid
	$userid = t(getId($userip));


	$pwd=hex2bin($pwd);//バイナリに
	$pwd=openssl_decrypt($pwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV);//復号化


	/*--- テンポラリ捜査 ---*/
	$find=false;
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
			$fp = fopen(TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,$urepcode,$starttime,$postedtime,$uresto,$tool) = explode("\t", rtrim($userdata)."\t");//区切りの"\t"を行末に
			$file_name = pathinfo($file, PATHINFO_FILENAME );//拡張子除去
			//画像があり、認識コードがhitすれば抜ける
		
			if($file_name && is_file(TEMP_DIR.$file_name.$imgext) && $urepcode === $repcode){
				$find=true;break;
			}

		}
	}
	closedir($handle);
	if(!$find){
	error('失敗しました。');
	}
	$tempfile=TEMP_DIR.$file_name.$imgext;

	//ログ読み込み
	if(!is_file(LOG_DIR."$no.log")){
		return paintcom();//該当記事が無い時は新規投稿。
	}
	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);

	$r_arr=[];
	$rp=fopen(LOG_DIR."$no.log","r+");
		while ($line = fgets($rp)) {
			$r_arr[]=$line;
		}
	$flag=false;
	foreach($r_arr as $i => $line){
		list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));
		if($id==$_time && password_verify($pwd,$_hash)){
			$flag=true;
			break;
		}
	}
	if(!$flag){
		closeFile($rp);
		return error('見つかりませんでした。');
	}
	

	$time = time().substr(microtime(),2,3);

	$upfile=IMG_DIR.$time.'.tmp';
	copy($tempfile, $upfile);
	if(!is_file($upfile)) error('失敗しました。');
	chmod($upfile,0606);

	$filesize=filesize($upfile);
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
		
	$img_type=mime_content_type($upfile);

	$imgext = getImgType($img_type, $upfile);

	if (!$imgext) {
		safe_unlink($upfile);
		error('対応していないフォーマットです。');
	}
	list($w, $h) = getimagesize($upfile);
	$img_md5=md5_file($upfile);
	
	$imgfile = $time.$imgext;

	rename($upfile,IMG_DIR.$imgfile);
	chmod(IMG_DIR.$imgfile,0606);

	$src='';
	//PCHファイルアップロード
	// .pch, .spch, ブランク どれかが返ってくる
	if ($pchext = check_pch_ext(TEMP_DIR . $file_name)) {
		$src = TEMP_DIR . $file_name . $pchext;
		$dst = IMG_DIR . $time . $pchext;
		if(copy($src, $dst)){
			chmod($dst, 0606);
		}
	}
	//chiファイルアップロード
	if(is_file(TEMP_DIR.$file_name.'.chi')){
		$pchext = '.chi';
		$src = TEMP_DIR.$file_name.'.chi';
		$dst = IMG_DIR.$time.'.chi';
		if(copy($src, $dst)){
			chmod($dst,0606);
		}
	}
	list($w,$h)=getimagesize(IMG_DIR.$imgfile);

	//縮小表示 
	$max_w = ($_oya==='res') ? $res_max_w : $max_w; 
	$max_h = ($_oya==='res') ? $res_max_h : $max_h; 

	list($w,$h)=image_reduction_display($w,$h,$max_w,$max_h);
	
	//サムネイル
	$thumbnail='';
	if($use_thumb){
		if(thumb(IMG_DIR,$imgfile,$time,$max_w,$max_h)){
			$thumbnail='thumbnail';
		}
	}
	
	//描画時間追加

	$painttime = '';
	if($starttime && is_numeric($starttime) && is_numeric($_painttime)){
		$psec=(int)$postedtime-(int)$starttime;
		$painttime=(int)$_painttime+(int)$psec;
	}
	
	$new_line= "$_no\t$_sub\t$_name\t$_verified\t$_com\t$_url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$_first_posted_time\t$host\t$userid\t$_hash\t$_oya\n";

	$r_arr[$i] = $new_line;

	writeFile($rp, implode("", $r_arr));
	closeFile($rp);


	if($_oya ==='oya'){

		while ($_line = fgets($fp)) {
			$alllog_arr[]=$_line;	
		}
		foreach($alllog_arr as $i => $val){
			list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_) = explode("\t",$val);

			if($id==$time_){
				$alllog_arr[$i] = $new_line;
			break;
			}

		}
		$alllog=implode("",$alllog_arr);
		writeFile($fp,$alllog);

	}
	closeFile ($fp);
	
	//旧ファイル削除
	delete_files($_imgfile, $_time);
	//ワークファイル削除
	safe_unlink($src);
	safe_unlink($tempfile);
	safe_unlink(TEMP_DIR.$file_name.".dat");

	return header('Location: ./?resno='.$no);

}

// 動画表示
function pchview(){
	global $boardname,$skindir;

	$imagefile = filter_input(INPUT_GET, 'imagefile');
	$pch = pathinfo($imagefile, PATHINFO_FILENAME);
	$pchext = check_pch_ext(IMG_DIR . $pch);
	if(!$pchext){
		error('ファイルがありません。');
	}
	$pchfile = IMG_DIR.$pch.$pchext;

	list($picw, $pich) = getimagesize(IMG_DIR.$imagefile);
	$appw = $picw < 200 ? 200 : $picw;
	$apph = $pich < 200 ? 200 : $pich + 26;

	// HTML出力
	$templete='pch_view.html';
	return include __DIR__.'/'.$skindir.$templete;

}
//削除前の確認画面
function confirmation_before_deletion ($edit_mode=''){

	global $boardname,$home,$petit_ver,$petit_lot,$skindir,$use_aikotoba;
		//管理者判定処理
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$resmode = filter_input(INPUT_POST,'resmode',FILTER_VALIDATE_BOOLEAN);
	$resmode = $resmode ? 'true' : 'false';
	$postpage = filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$postresno = filter_input(INPUT_POST,'postresno',FILTER_VALIDATE_INT);
	$postpage = ($postpage || $postpage===0) ? $postpage : 0; 
	$postresno = ($postresno) ? $postresno : false; 

	$pwdc=filter_input(INPUT_COOKIE,'pwdc');


	$edit_mode = filter_input(INPUT_POST,'edit_mode');

	if(!($admindel||$userdel)){
		return error('失敗しました。');
	}

	if($edit_mode!=='delmode' && $edit_mode!=='editmode'){
		error('失敗しました。');
	}
	$id = t((string)filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT));
	$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));

	if(is_file(LOG_DIR."$no.log")){
				
		$rp=fopen(LOG_DIR."$no.log","r");
		flock($rp, LOCK_EX);
		while ($r_line = fgets($rp)) {
			$line[]=$r_line;
		}
		$res=[];
		$find=false;
		foreach($line as $i =>$val){

			$_line=explode("\t",trim($val));
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$_line;
			if($id===$time && $no===$_no){
				$res=create_res($_line);	
				$out[0][]=$res;

				$find=true;
				break;
				
			}

		}
		if(!$find){
			error('見つかりませんでした。');
		}

		closeFile ($rp);
	}

	$token=get_csrf_token();

	if(!$use_aikotoba){
		$aikotoba=true;
	}

	if($edit_mode==='delmode'){
		$templete='before_del.html';
		return include __DIR__.'/'.$skindir.$templete;
	}
	if($edit_mode==='editmode'){
		$templete='before_edit.html';
		return include __DIR__.'/'.$skindir.$templete;
	}
	error('失敗しました。');
}
//編集画面
function edit_form(){
	global  $petit_ver,$petit_lot,$home,$boardname,$skindir;

	$token=get_csrf_token();
	$admindel=admindel_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');

	$pwd=filter_input(INPUT_POST,'pwd');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	
	if(!($admindel||($userdel&&$pwd))){
		return error('失敗しました。');
	}
	$id_and_no=filter_input(INPUT_POST,'id_and_no');
	$id=$no='';
	if($id_and_no){
		list($id,$no)=explode(",",trim(filter_input(INPUT_POST,'id_and_no')));
	}
	$fp=fopen(LOG_DIR."alllog.log","r");
	flock($fp, LOCK_EX);

	$flag=false;

	if(is_file(LOG_DIR."$no.log")){
		
		$rp=fopen(LOG_DIR."$no.log","r");
		flock($rp, LOCK_EX);
		while ($r_line = fgets($rp)) {
			$line[]=$r_line;
		}
		foreach($line as $i =>$val){
			
			$line_=explode("\t",trim($val));

			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line_;
			if($id==$time && $no===$_no){
			
				if(!$admindel){
					
					if(!check_elapsed_days($time)||!password_verify($pwd,$hash)){
						return error('失敗しました。');
					}
				}
				$flag=true;
				break;
			}
		}
			
	}
	if(!$flag){
		error('見つかりませんでした。');
	}
		closeFile ($fp);
	
		$_res = create_res($line_);//$lineから、情報を取り出す
		$out[0][]=$_res;


	$resno=filter_input(INPUT_POST,'postresno',FILTER_VALIDATE_INT);
	$page=filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);

	$com=str_replace('"\n"',"\n",$com);


	$page = ($page||$page===0) ? $page : false; 
	$resno = $resno ? $resno : false;
// HTML出力
	$templete='edit_form.html';
	return include __DIR__.'/'.$skindir.$templete;

}
//編集
function edit(){
	global $name_input_required,$max_com;

	check_csrf_token();

	//POSTされた内容を取得
	$userip =t(get_uip());
	//ホスト取得
	$host = t(gethostbyaddr($userip));
	$userid = t(getId($userip));

	$sub = t((string)filter_input(INPUT_POST,'sub'));
	$name = t((string)filter_input(INPUT_POST,'name'));
	$com = t((string)filter_input(INPUT_POST,'com'));
	$url = t((string)filter_input(INPUT_POST,'url',FILTER_VALIDATE_URL));
	$id = t((string)filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT));
	$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));
	
	$pwd=filter_input(INPUT_POST,'pwd');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	session_sta();
	$admindel=admindel_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	if(!($admindel||($userdel&&$pwd))){
		return error('失敗しました。');
	}

	//NGワードがあれば拒絶
	Reject_if_NGword_exists_in_the_post();

	//制限
	if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
	if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
	if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
	if(!$url||preg_match("/\A\s*\z/u",$url)) $url="";

	if(strlen($sub) > 80) error('題名が長すぎます。');
	if(strlen($name) > 30) error('名前が長すぎます。');
	if(strlen($com) > $max_com) error('本文が長すぎます。');
	if(strlen($url) > 100) error('urlが長すぎます。');
	if(strlen($pwd) > 100) error('パスワードが長すぎます。');

	$sub=str_replace(["\r\n","\r","\n",],'',$sub);
	$name=str_replace(["\r\n","\r","\n",],'',$name);
	$com=str_replace(["\r\n","\r","\n",],"\n",$com);
	$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット
	$com=str_replace("\n",'"\n"',$com);
	if(!$name){
		if($name_input_required){
			error('名前がありません。');
		}else{
			$name='anonymous';
		}
	}
	//ログ読み込み
	if(!is_file(LOG_DIR."$no.log")){
		error('記事がありません。');//該当記事が無い時は新規投稿。
	}
	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);

	$r_arr=[];
	$rp=fopen(LOG_DIR."$no.log","r+");
		while ($line = fgets($rp)) {
			$r_arr[]=$line;
		}
	$flag=false;
	$_res=[];
	foreach($r_arr as $i => $line){
		list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));
		if($id===$_time && $no===$_no){

			if(!$admindel){

				if(!check_elapsed_days($_time)||!password_verify($pwd,$_hash)){
					return error('失敗しました。');
				}
			}
			$flag=true;
			break;
		}
	}
	if(!$flag){
		closeFile($rp);
		return error('見つかりませんでした。');
	}
	if(!$_imgfile && !$com){
		error('何か書いてください。');
	}
	$time = time().substr(microtime(),2,3);

	$sub=($_oya==='res') ? $_sub : $sub; 

	$sub=(!$sub) ? '無題' : $sub;

	$new_line= "$_no\t$sub\t$name\t$_verified\t$com\t$url\t$_imgfile\t$_w\t$_h\t$_thumbnail\t$_painttime\t$_log_md5\t$_tool\t$_pchext\t$_time\t$_first_posted_time\t$host\t$userid\t$_hash\t$_oya\n";

	$r_arr[$i] = $new_line;


	writeFile($rp, implode("", $r_arr));
	closeFile($rp);


	if($_oya==='oya'){

		while ($_line = fgets($fp)) {
			$alllog_arr[]=$_line;	
		}
		foreach($alllog_arr as $i => $val){
			list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_) = explode("\t",$val);

			if($id===$time_ && $no===$no_){
				$alllog_arr[$i] = $new_line;
			break;
			}

		}
		$alllog=implode("",$alllog_arr);
		writeFile($fp,$alllog);

	}
	closeFile ($fp);
	

	return header('Location: ./?resno='.$no);

}

//記事削除
function del(){
	$pwd=filter_input(INPUT_POST,'pwd');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	check_csrf_token();
	session_sta();
	$admindel=admindel_valid();
	$userdel_mode=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	if(!($admindel||($userdel_mode&&$pwd))){
		return error('失敗しました。');
	}
	$id_and_no=filter_input(INPUT_POST,'id_and_no');
	if(!$id_and_no){
		error('記事が選択されていません。');
	}
	$id=$no='';
	if($id_and_no){
		list($id,$no)=explode(",",trim(filter_input(INPUT_POST,'id_and_no')));
	}
	$alllog_arr=[];
	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);
	while ($_line = fgets($fp)) {
		$alllog_arr[]=$_line;	
	}

	if(is_file(LOG_DIR."$no.log")){
		
		$rp=fopen(LOG_DIR."$no.log","r+");
		flock($rp, LOCK_EX);
		while ($r_line = fgets($rp)) {
			$line[]=$r_line;
		}
		$find=false;
		foreach($line as $i =>$val){

			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($val));
			if($id===$time && $no===$_no){
			
				if(!$admindel){
					if(!password_verify($pwd,$hash)){
						return error('失敗しました。');
					}
				}
				if($oya==='oya'){//スレッド削除
					foreach($line as $r_line) {//レスファイル
						list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($r_line));

						delete_files ($_imgfile, $_time);//一連のファイルを削除

					}
				
					foreach($alllog_arr as $i =>$val){//全体ログ
						list($no_,$sub_,$name_,$verified_,$com_,$url_,$_imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=explode("\t",$val);
						if($id===$time_ && $no===$no_){
							unset($alllog_arr[$i]);
						}
					}
					$alllog=implode("",$alllog_arr);
					writeFile($fp,$alllog);
					safe_unlink(LOG_DIR.$no.'.log');
					closeFile ($rp);
			
				}else{

					unset($line[$i]);
					delete_files ($imgfile, $time);//一連のファイルを削除
					$line=implode("",$line);
					writeFile ($rp, $line);
					closeFile ($rp);

				}
				$find=true;
				break;
			}
		}
			if(!$find){
				error('見つかりませんでした。');
			}

		closeFile ($fp);

	}
	$resno=filter_input(INPUT_POST,'postresno');
	//多重送信防止
	if(filter_input(INPUT_POST,'resmode')==='true'){
		if(!is_file(LOG_DIR.$resno.'.log')){
			return header('Location: ./');
		}
		return header('Location: ./?resno='.$resno);
	}
	return header('Location: ./?page='.filter_input(INPUT_POST,'postpage'));
}

//カタログ表示
function catalog($page=0,$q=''){
	global $use_aikotoba,$home,$catalog_pagedef,$skindir;
	global $boardname,$petit_ver,$petit_lot,$set_nsfw; 
	$pagedef=$catalog_pagedef;
	
	$q=filter_input(INPUT_GET,'q');

	$fp=fopen(LOG_DIR."alllog.log","r");
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		$alllog_arr[]=$_line;	
	}

	$encoded_q='';
	$result=[];
	if($q){//名前検索の時
		foreach($alllog_arr as $alllog){
			$line=explode("\t",trim($alllog));
			list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$_oya)=$line;
	
			if($name===$q){//検索結果と一致した投稿を配列に入れる
				$result[]=$alllog;
			}
		}
		$alllog_arr=$result;
		$encoded_q=urlencode($q);
	}

	$count_alllog=count($alllog_arr);

	//ページ番号から1ページ分のスレッド分とりだす
	$alllog_arr=array_slice($alllog_arr,(int)$page,$pagedef,false);
	//oyaのループ

	foreach($alllog_arr as $oya => $alllog){

		$_res=[];
		
		$line=explode("\t",trim($alllog));

		$_res = create_res($line);//$lineから、情報を取り出す
		$out[$oya][]=$_res;

	}

	//管理者判定処理
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();

	if(!$use_aikotoba){
		$aikotoba=true;
	}

	//Cookie
	$nsfwc=(string)filter_input(INPUT_COOKIE,'nsfwc');
	//token
	$token=get_csrf_token();


	// HTML出力
	$templete='catalog.html';
	return include __DIR__.'/'.$skindir.$templete;

}

//通常表示
function view($page=0){
	global $use_aikotoba,$use_upload,$home,$pagedef,$dispres,$allow_coments_only,$use_top_form,$skindir,$descriptions;
	global $boardname,$max_res,$pmax_w,$pmax_h,$use_miniform,$use_diary,$petit_ver,$petit_lot,$set_nsfw,$use_sns_button,$denny_all_posts; 

	$fp=fopen(LOG_DIR."alllog.log","r");
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		$alllog_arr[]=$_line;	
	}
	$count_alllog=count($alllog_arr);


	//ページ番号から1ページ分のスレッドをとりだす
	$alllog_arr=array_slice($alllog_arr,(int)$page,$pagedef,false);
	//oyaのループ
	foreach($alllog_arr as $oya => $alllog){
		
		list($no)=explode("\t",$alllog);
		//個別スレッドのループ
		if(!is_file(LOG_DIR."$no.log")){
		continue;	
		}
		$_res=[];
			$fp = fopen(LOG_DIR."$no.log", "r");//個別スレッドのログを開く
			$s=0;
			while ($line = fgets($fp)) {
				$_res = create_res(explode("\t",trim($line)));//$lineから、情報を取り出す
				$out[$oya][]=$_res;
			}	
		fclose($fp);
	}

	//管理者判定処理
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();

	if(!$use_aikotoba){
		$aikotoba=true;
	}

	//Cookie
	$namec=(string)filter_input(INPUT_COOKIE,'namec');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$urlc=(string)filter_input(INPUT_COOKIE,'urlc');
	$appc=(string)filter_input(INPUT_COOKIE,'appc');
	$picwc=(string)filter_input(INPUT_COOKIE,'picwc');
	$pichc=(string)filter_input(INPUT_COOKIE,'pichc');
	$nsfwc=(string)filter_input(INPUT_COOKIE,'nsfwc');

	//token
	$token=get_csrf_token();

	// HTML出力
	$templete='main.html';
	return include __DIR__.'/'.$skindir.$templete;

}
//レス画面
function res ($resno){
	global $use_aikotoba,$use_upload,$home,$skindir,$root_url,$use_res_upload;
	global $boardname,$max_res,$pmax_w,$pmax_h,$petit_ver,$petit_lot,$set_nsfw,$use_sns_button,$denny_all_posts;
	
	$page='';
	$resno=filter_input(INPUT_GET,'resno');
	if(!is_file(LOG_DIR."$resno.log")){
		error('スレッドがありません');	
		}
		$rresname = [];
		$resname = '';
			$fp = fopen(LOG_DIR."$resno.log", "r");//個別スレッドのログを開く
			while ($line = fgets($fp)) {
				$_res = create_res(explode("\t",trim($line)));//$lineから、情報を取り出す


				if($_res['oya']==='oya'){

					$oyaname = $_res['name'];

				} 
				// 投稿者名を配列にいれる
					if (($oyaname !== $_res['name']) && !in_array($_res['name'], $rresname)) { // 重複チェックと親投稿者除外
						$rresname[] = $_res['name'];
					}
			
				if($rresname){//レスがある時
					$resname = $rresname ? implode('さん'.' ',$rresname) : false; // レス投稿者一覧
				}

			$out[0][]=$_res;
			}	
		fclose($fp);

	//管理者判定処理
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();
	if(!$use_aikotoba){
		$aikotoba=true;
	}

	//Cookie
	$namec=(string)filter_input(INPUT_COOKIE,'namec');
	$pwdc=filter_input(INPUT_COOKIE,'pwdc');
	$urlc=(string)filter_input(INPUT_COOKIE,'urlc');
	$appc=(string)filter_input(INPUT_COOKIE,'appc');
	$picwc=(string)filter_input(INPUT_COOKIE,'picwc');
	$pichc=(string)filter_input(INPUT_COOKIE,'pichc');
	$nsfwc=(string)filter_input(INPUT_COOKIE,'nsfwc');

	//token
	$token=get_csrf_token();
	$templete='res.html';
	return include __DIR__.'/'.$skindir.$templete;
	
}

