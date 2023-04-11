<?php
//Petit Note (c)さとぴあ @satopian 2021-2022
//1スレッド1ログファイル形式のスレッド式画像掲示板
$petit_ver='v0.63.10';
$petit_lot='lot.230411';
$lang = ($http_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
  ? explode( ',', $http_langs )[0] : '';
$en= (stripos($lang,'ja')!==0);

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
	die($en? "Error. PHP version 5.6.0 or higher is required for this program to work. <br>\n(Current PHP version:".PHP_VERSION.")":
		"エラー。本プログラムの動作には PHPバージョン 5.6.0 以上が必要です。<br>\n(現在のPHPバージョン：".PHP_VERSION.")"
	);
}
if(!is_file(__DIR__.'/functions.php')){
	return die(__DIR__.'/functions.php'.($en ? ' does not exist.':'がありません。'));
}
require_once(__DIR__.'/functions.php');
if(!isset($functions_ver)||$functions_ver<20230411){
	return die($en?'Please update functions.php to the latest version.':'functions.phpを最新版に更新してください。');
}
// jQueryバージョン
const JQUERY='jquery-3.6.0.min.js';
check_file(__DIR__.'/lib/'.JQUERY);
// luminous
check_file(__DIR__.'/lib/luminous/luminous.min.js');
check_file(__DIR__.'/lib/luminous/luminous-basic.min.css');

check_file(__DIR__.'/config.php');
check_file(__DIR__.'/thumbnail_gd.php');
check_file(__DIR__.'/noticemail.inc');

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/thumbnail_gd.php');
require_once(__DIR__.'/noticemail.inc');

//テンプレート
$skindir='template/'.$skindir;

if(!isset($thumbnail_gd_ver)||$thumbnail_gd_ver<20230220){
	return error($en?'Please update thumbmail_gd.php to the latest version.':'thumbnail_gd.phpを最新版に更新してください。');
}

if(!$max_log){
	return error($en?'The maximum number of threads has not been set.':'最大スレッド数が設定されていません。');
}
if(!isset($admin_pass)||!$admin_pass){
	return error($en?'The administrator password has not been set.':'管理者パスワードが設定されていません。');
}
$max_log=($max_log<500) ? 500 : $max_log;//最低500スレッド
$max_com= isset($max_com) ? $max_com : 1000;
$sage_all= isset($sage_all) ? $sage_all : false;
$view_other_works= isset($view_other_works) ? $view_other_works : true;
$deny_all_posts= isset($deny_all_posts) ? $deny_all_posts : (isset($denny_all_posts) ? $denny_all_posts : false);
$allow_comments_only = isset($allow_comments_only) ? $allow_comments_only : (isset($allow_coments_only) ? $allow_coments_only : false); 
$dispres = isset($dispres) ? $dispres : (isset($display) ? $display : 5); 
$latest_var=isset($latest_var) ? $latest_var : true;
$badhost=isset($badhost) ? $badhost :[]; 
$mark_sensitive_image = isset($mark_sensitive_image) ? $mark_sensitive_image : false; 
$only_admin_can_reply = isset($only_admin_can_reply) ? $only_admin_can_reply : false;
$check_password_input_error_count = isset($check_password_input_error_count) ? $check_password_input_error_count : false;
$aikotoba_required_to_view=isset($aikotoba_required_to_view) ? $aikotoba_required_to_view : false;
$keep_aikotoba_login_status=isset($keep_aikotoba_login_status) ? $keep_aikotoba_login_status : false;
$use_paintbbs_neo=isset($use_paintbbs_neo) ? $use_paintbbs_neo : true;
$use_chickenpaint=isset($use_chickenpaint) ? $use_chickenpaint : true;
$max_file_size_in_png_format_paint = isset($max_file_size_in_png_format_paint) ? $max_file_size_in_png_format_paint : 1024;
$max_file_size_in_png_format_upload = isset($max_file_size_in_png_format_upload) ? $max_file_size_in_png_format_upload : 800;
$use_klecs=isset($use_klecs) ? $use_klecs : true;
$display_link_back_to_home = isset($display_link_back_to_home) ? $display_link_back_to_home : true;
$password_require_to_continue = isset($password_require_to_continue) ? (bool)$password_require_to_continue : false;
$mode = (string)filter_input(INPUT_POST,'mode');
$mode = $mode ? $mode :(string)filter_input(INPUT_GET,'mode');
$page=(int)filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
$resno=(int)filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);

$usercode = t((string)filter_input(INPUT_COOKIE, 'usercode'));//user-codeを取得
$userip = get_uip();
//user-codeの発行
if(!$usercode){//user-codeがなければ発行
	$usercode = substr(crypt(md5($userip.uniqid()),'id'),-12);
	//念の為にエスケープ文字があればアルファベットに変換
	$usercode = strtr($usercode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~\t","ABCDEFGHIJKLMNOabcdefghijklmno");
}
setcookie("usercode", $usercode, time()+(86400*365),"","",false,true);//1年間
$x_frame_options_deny = isset($x_frame_options_deny) ? $x_frame_options_deny : true;
if($x_frame_options_deny){
	header('X-Frame-Options: DENY');
}
//初期化
init();
deltemp();//テンポラリ自動削除
switch($mode){
	case 'regist':
		if($deny_all_posts){
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
		$type = (string)filter_input(INPUT_POST, 'type');
		if($type==='rep'||$password_require_to_continue){
			check_cont_pass();
		} 
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
	case 'search':
		return search();
	case 'catalog':
		return catalog($page);
	case 'download':
		return download_app_dat();
	case '':
		if($resno){
			return res($resno);
		}
		return view($page);
	default:
		return header('Location: ./');
	}

//投稿処理
function post(){
	global $max_log,$max_res,$max_kb,$use_aikotoba,$use_upload,$use_res_upload,$use_diary,$max_w,$max_h,$use_thumb,$mark_sensitive_image;
	global $allow_comments_only,$res_max_w,$res_max_h,$admin_pass,$name_input_required,$max_com,$max_px,$sage_all,$en,$only_admin_can_reply;
	global $usercode,$max_file_size_in_png_format_upload,$max_file_size_in_png_format_paint;

	if($use_aikotoba){
		check_aikotoba();
	}
	check_csrf_token();

	//POSTされた内容を取得
	$userip =t(get_uip());
	//ホスト取得
	$host = $userip ? t(gethostbyaddr($userip)) : '';

	$sub = t((string)filter_input(INPUT_POST,'sub'));
	$name = t((string)filter_input(INPUT_POST,'name'));
	$com = t((string)filter_input(INPUT_POST,'com'));
	$url = t((string)filter_input(INPUT_POST,'url',FILTER_VALIDATE_URL));
	$resto = t((string)filter_input(INPUT_POST,'resto',FILTER_VALIDATE_INT));
	$pwd=t((string)filter_input(INPUT_POST, 'pwd'));//パスワードを取得
	$sage = $sage_all ? true : (bool)filter_input(INPUT_POST,'sage',FILTER_VALIDATE_BOOLEAN);
	$hide_thumbnail = $mark_sensitive_image ? (bool)filter_input(INPUT_POST,'hide_thumbnail',FILTER_VALIDATE_BOOLEAN) : false;
	$hide_animation=(bool)filter_input(INPUT_POST,'hide_animation',FILTER_VALIDATE_BOOLEAN);
	$check_elapsed_days=false;

	//NGワードがあれば拒絶
	Reject_if_NGword_exists_in_the_post();

	$pwd=$pwd ? $pwd : t((string)filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード
	if(!$pwd){//それでも$pwdが空なら
		srand();
		$pwd = substr(md5(uniqid(rand(),true)),2,15);
		$pwd = strtr($pwd,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~\t","ABCDEFGHIJKLMNOabcdefghijklmno");
	}
	if(strlen($pwd) < 6) return error($en? 'The password is too short. At least 6 characters.':'パスワードが短すぎます。最低6文字。');

	$upfile='';
	$imgfile='';
	$w='';
	$h='';
	$tool='';
	$time = (string)(time().substr(microtime(),2,6));	//投稿時刻

	$testexts=['.gif','.jpg','.png','.webp'];
	foreach($testexts as $testext){
		if(is_file(IMG_DIR.$time.$testext)){
			$time=(string)(substr($time,0,-6)+1).(string)substr($time,-6);
		break;	
		}
	}
	$time= is_file(TEMP_DIR.$time.'.tmp') ?	(string)(substr($time,0,-6)+1).(string)substr($time,-6) : $time;
	$time=basename($time);
	$adminpost=(adminpost_valid()||($pwd && $pwd === $admin_pass));

	//お絵かきアップロード
	$pictmp = (int)filter_input(INPUT_POST, 'pictmp',FILTER_VALIDATE_INT);
	$painttime ='';
	$pictmp2=false;
	$tempfile='';
	$picfile='';
	if($pictmp===2){//ユーザーデータを調べる
		list($picfile,) = explode(",",(string)filter_input(INPUT_POST, 'picfile'));
		$picfile=basename($picfile);
		$tempfile = TEMP_DIR.$picfile;
		$picfile=pathinfo($tempfile, PATHINFO_FILENAME );//拡張子除去
		//選択された絵が投稿者の絵か再チェック
		if (!$picfile || !is_file(TEMP_DIR.$picfile.".dat") || !is_file($tempfile)) {
			return error($en? 'Posting failed.':'投稿に失敗しました。');
		}
		//ユーザーデータから情報を取り出す
		$fp = fopen(TEMP_DIR.$picfile.".dat", "r");
		$userdata = fread($fp, 1024);
		fclose($fp);
		list($uip,$uhost,,,$ucode,,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");
		if(($ucode != $usercode) && (!$uip || ($uip != $userip))){return error($en? 'Posting failed.':'投稿に失敗しました。');}
		$tool= in_array($tool,['neo','chi','klecks']) ? $tool : '???';
		$uresto=filter_var($uresto,FILTER_VALIDATE_INT);
		$hide_animation= $hide_animation ? true : ($u_hide_animation==='true');
		$resto = $uresto ? $uresto : $resto;//変数上書き$userdataのレス先を優先する
		check_open_no($resto);
		$resto=(string)$resto;//(string)厳密な型
		//描画時間を$userdataをもとに計算
		if($starttime && is_numeric($starttime) && $postedtime && is_numeric($postedtime)){
			$painttime=(int)$postedtime-(int)$starttime;
		}
		if($resto && !$use_res_upload && !$adminpost){
			return error($en? 'Only administrator can post.':'投稿できるのは管理者だけです。');
		}
		$pictmp2=true;//お絵かきでエラーがなかった時にtrue;

	}

	if(!$resto && $use_diary && !$adminpost){
		return error($en? 'Only administrator can post.':'投稿できるのは管理者だけです。');
	}
	if($resto && $only_admin_can_reply && !$adminpost){
		return error($en?'Only administrator can reply.':'返信できるのは管理者だけです。');
	}

	if($resto && !is_file(LOG_DIR."{$resto}.log")){//エラー処理
		if(!$pictmp2){//お絵かきではない時は
			return error($en? 'The article does not exist.':'記事がありません。');
		}
		$resto='';//レス先がないお絵かきは新規投稿扱いにする。
	}
	$count_r_arr=0;
	$r_oya='';
	$r_no='';
	$rp=false;
	$r_arr=[];
	$chk_resto='';
	if($resto){//レスの時はファイルロックしてレスファイルを開く
		check_open_no($resto);
		$rp=fopen(LOG_DIR."{$resto}.log","r+");
		flock($rp, LOCK_EX);
		while ($line = fgets($rp)) {
			if(!trim($line)){
				continue;
			}
			$r_arr[]=$line;
		}
		if(empty($r_arr)){
			closeFile($rp);
			closeFile($fp);
			if(!$pictmp2){
				return error($en?'This operation has failed.':'失敗しました。');
			}
			$chk_resto=$resto;
			$resto = '';
		}

		list($r_no,$oyasub,$n_,$v_,$c_,$u_,$img_,$_,$_,$thumb_,$pt_,$md5_,$to_,$pch_,$postedtime,$fp_time_,$h_,$uid_,$h_,$r_oya)=explode("\t",trim($r_arr[0]));
		//レスファイルの1行目のチェック。経過日数、ログの1行目が'oya'かどうか確認。
		$check_elapsed_days = check_elapsed_days($postedtime);
		$count_r_arr=count($r_arr);

		//レス先のログファイルを再確認
		if($resto && ($r_no!==$resto || $r_oya!=='oya')){
			if(!$pictmp2){
				return error($en? 'The article does not exist.':'記事がありません。');
			}
			$chk_resto=$resto;
			$resto='';
		}
		if($pictmp2){//お絵かきの時は新規投稿にする
			//お絵かきの時に日数を経過していたら新規投稿。
			//お絵かきの時に最大レス数を超過していたら新規投稿。
			if($resto && !$adminpost && (!$check_elapsed_days || $count_r_arr>$max_res)){
				$chk_resto=$resto;
				$resto='';
			}
		}
		//お絵かき以外。
		if($resto && !$adminpost && !$check_elapsed_days){//指定した日数より古いスレッドには投稿できない。
			return error($en? 'This thread is too old to post.':'このスレッドには投稿できません。');
		}
		if($resto && !$adminpost &&  $count_r_arr>$max_res){//最大レス数超過。
			return error($en?'The maximum number of replies has been exceeded.':'最大レス数を超過しています。');
		}

		$sub='Re: '.$oyasub;

	}

	//ファイルアップロード
	$up_tempfile = isset($_FILES['imgfile']['tmp_name']) ? $_FILES['imgfile']['tmp_name'] : ''; // 一時ファイル名
	if(isset($_FILES['imgfile']['error']) && in_array($_FILES['imgfile']['error'],[1,2])){//容量オーバー
		return error($en? "Upload failed.The file size is too big.":"アップロードに失敗しました。ファイルサイズが大きすぎます。");
	} 
	$is_upload=false;
	if ($up_tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK && ($use_upload || $adminpost)){

		if($resto && !$use_res_upload && !$adminpost){
			safe_unlink($up_tempfile);
			return error($en? 'You are not logged in in diary mode.':'日記にログインしていません。');
		}

		$img_type = isset($_FILES['imgfile']['type']) ? $_FILES['imgfile']['type'] : '';

		if (!in_array($img_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])) {
			safe_unlink($up_tempfile);
			return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
		}
		$upfile=TEMP_DIR.$time.'.tmp';
		move_uploaded_file($up_tempfile,$upfile);
		$tool = 'upload'; 
		$is_upload=true;	
	}
	//お絵かきアップロード
	if($pictmp2 && is_file($tempfile)){

		$upfile=TEMP_DIR.$time.'.tmp';
			copy($tempfile, $upfile);
			chmod($upfile,0606);
	}
	$is_file_upfile=false;
	if($is_upload||$pictmp2){
		if(!is_file($upfile)){
			return error($en?'This operation has failed.':'失敗しました。');
		}
		$is_file_upfile=true;
	}
	//POSTされた値をログファイルに格納する書式にフォーマット
	$formatted_post=create_formatted_text_from_post($name,$sub,$url,$com);
	$name = $formatted_post['name'];
	$sub = $formatted_post['sub'];
	$url = $formatted_post['url'];
	$com = $formatted_post['com'];

	if(!$name){
		if($name_input_required){
			safe_unlink($upfile);
			return error($en?'Please enter your name.':'名前がありません。');
		}else{
			$name='anonymous';
		}
	}

	if(!$is_file_upfile&&!$com){
		return error($en?'Please write something.':'何か書いて下さい。');
	}

	if(!$resto && !$allow_comments_only && !$is_file_upfile && !$adminpost){
		return error($en?'Please attach an image.':'画像を添付してください。');
	}

	$hash = $pwd ? password_hash($pwd,PASSWORD_BCRYPT,['cost' => 5]) : '';

	setcookie("namec",$name,time()+(60*60*24*30),"","",false,true);
	setcookie("urlc",$url,time()+(60*60*24*30),"","",false,true);
	setcookie("pwdc",$pwd,time()+(60*60*24*30),"","",false,true);


	//ユーザーid
	$userid=(isset($_SESSION['userid'])&&$_SESSION['userid'])
	? $_SESSION['userid'] : getId($userip);
	$userid=t($userid);//タブ除去
	$_SESSION['userid'] = $userid; 

	$verified = $adminpost ? 'adminpost' : ''; 

	//全体ログを開く
	$fp=fopen(LOG_DIR."alllog.log","r+");
	if(!$fp){
		safe_unlink($upfile);
		return error($en?'This operation has failed.':'失敗しました。');
	}
	flock($fp, LOCK_EX);
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		if(!trim($_line)){
			continue;
		}
		$alllog_arr[]=$_line;
	}

	//チェックするスレッド数。画像ありなら15、コメントのみなら5 
	$n= $is_file_upfile ? 15 : 5;
	$chk_log_arr=array_slice($alllog_arr,0,$n,false);

	$chk_resnos=[];
	foreach($chk_log_arr as $chk_log){
		list($chk_resno)=explode("\t",$chk_log);
		$chk_resnos[]=$chk_resno;
	}
	$_chk_lines=[];
	$chk_lines=[];
	//条件分岐で新規投稿に変更になった時のエラー回避
	$chk_resto=$chk_resto ? $chk_resto : $resto; 
	foreach($chk_resnos as $chk_resno){

		if(($chk_resno!==$chk_resto)&&is_file(LOG_DIR."{$chk_resno}.log")){
			check_open_no($chk_resno);
			$cp=fopen(LOG_DIR."{$chk_resno}.log","r");
			while($line=fgets($cp)){
				if(!trim($line)){
					continue;
				}
				$_chk_lines[]=$line;
			}
			closefile($cp);
		}
	}
	$chk_lines=array_merge($_chk_lines,$r_arr);

	$chk_com=[];
	$chk_images=[];
	foreach($chk_lines as $chk_line){
		$chk_ex_line=explode("\t",trim($chk_line));
		list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=$chk_ex_line;
		$chk_time=(strlen($time_)>15) ? substr($time_,0,-6) : substr($time_,0,-3);
		if((string)substr($time,0,-6)===(string)$chk_time){//投稿時刻の重複回避
			safe_unlink($upfile);
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			return error($en? 'Please wait a little.':'少し待ってください。');
		}
		if($host === $host_){
			$chk_com[$time_]=$chk_ex_line;//コメント
		}
		if($is_file_upfile && $imgfile_){
			$chk_images[$time_]=$chk_ex_line;//画像
		}
	}

	krsort($chk_com);
	$chk_com=array_slice($chk_com,0,20,false);

	foreach($chk_com as $line){
		list($_no_,$_sub_,$_name_,$_verified_,$_com_,$_url_,$_imgfile_,$_w_,$_h_,$_thumbnail_,$_painttime_,$_log_md5_,$_tool_,$_pchext_,$_time_,$_first_posted_time_,$_host_,$_userid_,$_hash_,$_oya_)=$line;

		if($com && ($com === $_com_)){
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			return error($en?'Post once by this comment.':'同じコメントがありました。');
		}

		// 画像アップロードと画像なしそれぞれの待機時間
		$_chk_time_=(strlen($_time_)>15) ? substr($_time_,0,-6) : substr($_time_,0,-3);
		$interval=(int)time()-(int)$_chk_time_;
		if($interval>=0 && (($upfile && $interval<30)||(!$upfile && $interval<15))){//待機時間がマイナスの時は通す
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			return error($en? 'Please wait a little.':'少し待ってください。');
		}
	}

	$img_md5='';
	if($is_file_upfile){

		if(!$pictmp2){//実体データの縮小
			$max_px=isset($max_px) ? $max_px : 1024;
			thumb(TEMP_DIR,$time.'.tmp',$time,$max_px,$max_px,['toolarge'=>1]);
		}	
		clearstatcache();
		$filesize=filesize($upfile);
		if(($filesize > $max_file_size_in_png_format_paint * 1024) || ($is_upload && $filesize > $max_file_size_in_png_format_upload * 1024)){//指定サイズを超えていたら
			if ($im_jpg = png2jpg($upfile)) {//PNG→JPEG自動変換
				clearstatcache();
				if(filesize($im_jpg)<$filesize){//JPEGのほうが小さい時だけ
					rename($im_jpg,$upfile);//JPEGで保存
					chmod($upfile,0606);
				} else{//PNGよりファイルサイズが大きくなる時は
					unlink($im_jpg);//作成したJPEG画像を削除
				}
			}
		}
		if(!$pictmp2){
			clearstatcache();
			if(filesize($upfile) > $max_kb*1024){
				closeFile($fp);
				closeFile($rp);
				safe_unlink($upfile);
			return error($en? "Upload failed. File size exceeds {$max_kb}kb.":"アップロードに失敗しました。ファイル容量が{$max_kb}kbを超えています。");
			}
		}

		list($w,$h)=getimagesize($upfile);
		$_img_type=mime_content_type($upfile);
		$ext=getImgType ($_img_type);
		if (!$ext) {
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
		}

		//同じ画像チェック アップロード画像のみチェックしてお絵かきはチェックしない
		if(!$pictmp2){

			$img_md5=md5_file($upfile);
			foreach($chk_images as $line){
				list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=$line;
				if($log_md5 && ($log_md5 === $img_md5)){
					closeFile($fp);
					closeFile($rp);
					safe_unlink($upfile);
					return error($en?'Image already exists.':'同じ画像がありました。');
				}
			}
		}
		$imgfile=$time.$ext;

		rename($upfile,IMG_DIR.$imgfile);
		if(!is_file(IMG_DIR.$imgfile)){
			return error($en?'This operation has failed.':'失敗しました。');
		}
	}

	$src='';
	$pchext = '';
	//PCHファイルアップロード
	// .pch, .spch,.chi,.psd ブランク どれかが返ってくる
	if ($pictmp2 && $imgfile && ($pchext = check_pch_ext(TEMP_DIR.$picfile,['upload'=>true]))) {

		$src = TEMP_DIR.$picfile.$pchext;
		$dst = IMG_DIR.$time.$pchext;
			if(copy($src, $dst)){
				chmod($dst,0606);
		}
	}
	$pchext= ($pchext==='.pch' && $hide_animation) ? 'hide_animation' : $pchext; 

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
	$hide_thumbnail=$hide_thumbnail ? 'hide_' : '';
	$thumbnail =  $hide_thumbnail.$thumbnail;
		//webpサムネイル
		thumb(IMG_DIR,$imgfile,$time,300,800,['webp'=>true]);
	}
	//ログの番号の最大値
	$no_arr = [];
	foreach($alllog_arr as $i => $_alllog){
		list($log_no,)=explode("\t",$_alllog);
		$no_arr[]=$log_no;
	}

	$max_no=0;
	if(!empty($no_arr)){
		$max_no=max($no_arr);
	}
	//書き込むログの書式
	$line='';
	$newline='';
	$r_line='';
	$new_r_line='';
	if($resto){//レスの時はスレッド別ログに追記
		$r_oya='';
		$r_no='';
		if(empty($r_arr)){
			closeFile($fp);
			closeFile($rp);
			safe_unlink(IMG_DIR.$imgfile);
			return error($en?'This operation has failed.':'失敗しました。');
		}
		//レス先はoya?
		list($r_no,,,,,,,,,,,,,,,,,,,$r_oya)=explode("\t",trim($r_arr[0]));
		if($r_no!==$resto||$r_oya!=='oya'){
			closeFile($fp);
			closeFile($rp);
			safe_unlink(IMG_DIR.$imgfile);
			return error($en? 'The article does not exist.':'記事がありません。');
		}

		$r_line = "$resto\t$sub\t$name\t$verified\t$com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\tres\n";
		$new_rline=	implode("",$r_arr).$r_line;

		writeFile($rp,$new_rline);
		closeFile($rp);

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

	}else{
		//最後の記事ナンバーに+1
		$no=$max_no+1;
		//コメントを120バイトに短縮
		$strcut_com=mb_strcut($com,0,120);
		$newline = "$no\t$sub\t$name\t$verified\t$strcut_com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\toya\n";
		$new_r_line = "$no\t$sub\t$name\t$verified\t$com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\toya\n";
		check_open_no($no);
		file_put_contents(LOG_DIR.$no.'.log',$new_r_line,LOCK_EX);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
		chmod(LOG_DIR.$no.'.log',0600);
	}

	//保存件数超過処理
	$countlog=count($alllog_arr);
	if($max_log && $countlog && ($max_log<=$countlog)){
		for($i=$max_log-1; $i<$countlog;++$i){

		if(!isset($alllog_arr[$i]) || !trim($alllog_arr[$i])){
			continue;
		}
		list($d_no,)=explode("\t",$alllog_arr[$i]);
		if(is_file(LOG_DIR."{$d_no}.log")){
			check_open_no($d_no);
			$dp = fopen(LOG_DIR."{$d_no}.log", "r");//個別スレッドのログを開く
			flock($dp, LOCK_EX);

			while ($line = fgets($dp)) {
				if(!trim($line)){
					continue;
				}
				list($d_no,$_sub,$_name,$_verified,$_com,$_url,$d_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$d_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));

				delete_files ($d_imgfile, $d_time);//一連のファイルを削除

			}
			closeFile($dp);
			safe_unlink(LOG_DIR.$d_no.'.log');//スレッド個別ログファイル削除
		}	
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
	safe_unlink($up_tempfile);
	safe_unlink($upfile);
	safe_unlink(TEMP_DIR.$picfile.".dat");

	global $send_email,$to_mail,$root_url,$boardname;

	if($send_email){
	//template_ini.phpで未定義の時の初期値
	//このままでよければ定義不要
	defined('NOTICE_MAIL_IMG') or define('NOTICE_MAIL_IMG', '投稿画像');
	defined('NOTICE_MAIL_THUMBNAIL') or define('NOTICE_MAIL_THUMBNAIL', 'サムネイル画像');
	defined('NOTICE_MAIL_URL') or define('NOTICE_MAIL_URL', '記事URL');
	defined('NOTICE_MAIL_REPLY') or define('NOTICE_MAIL_REPLY', 'へのレスがありました');
	defined('NOTICE_MAIL_NEWPOST') or define('NOTICE_MAIL_NEWPOST', '新規投稿がありました');

		$data['to'] = $to_mail;
		$data['name'] = $name;
		$data['url'] = filter_var($url,FILTER_VALIDATE_URL) ? $url:'';
		$data['title'] = $sub;
		if($imgfile){
			$data['option'][] = NOTICE_MAIL_IMG.','.$root_url.IMG_DIR.$imgfile;//拡張子があったら
		} 
		if(is_file(THUMB_DIR.$time.'s.jpg')){
			$data['option'][] = NOTICE_MAIL_THUMBNAIL.','.$root_url.THUMB_DIR.$time.'s.jpg';
		} 
		if($resto){
			$data['subject'] = '['.$boardname.'] No.'.$resto.NOTICE_MAIL_REPLY;
			$data['option'][] = NOTICE_MAIL_URL.','.$root_url.'?res='.$resto;
		}else{
			$data['subject'] = '['.$boardname.'] '.NOTICE_MAIL_NEWPOST;
			$data['option'][] = NOTICE_MAIL_URL.','.$root_url.'?res='.$no;
		}

		$data['comment'] = str_replace('"\n"',"\n",$com);

		noticemail::send($data);
	}

	unset($admin_pass);

	//多重送信防止
	if($resto){
		return header('Location: ./?resno='.$resto.'#'.$time);
	}
	
return header('Location: ./');

}
//お絵かき画面
function paint(){

	global $boardname,$skindir,$pmax_w,$pmax_h,$en;
	global $usercode,$password_require_to_continue;

	check_same_origin();

	$app = (string)filter_input(INPUT_POST,'app');
	$picw = (int)filter_input(INPUT_POST,'picw',FILTER_VALIDATE_INT);
	$pich = (int)filter_input(INPUT_POST,'pich',FILTER_VALIDATE_INT);
	$resto = t((string)filter_input(INPUT_POST, 'resto',FILTER_VALIDATE_INT));
	if(strlen($resto)>1000){
		return error($en?'Unknown error':'問題が発生しました。');
	}
	if(!$usercode){
		error($en? 'User code does not exist.' :'ユーザーコードがありません。');
	}
	if($picw < 300) $picw = 300;
	if($pich < 300) $pich = 300;
	if($picw > $pmax_w) $picw = $pmax_w;
	if($pich > $pmax_h) $pich = $pmax_h;

	setcookie("appc", $app , time()+(60*60*24*30),"","",false,true);//アプレット選択
	setcookie("picwc", $picw , time()+(60*60*24*30),"","",false,true);//幅
	setcookie("pichc", $pich , time()+(60*60*24*30),"","",false,true);//高さ

	$mode = (string)filter_input(INPUT_POST, 'mode');

	$imgfile='';
	$pchfile='';
	$img_chi='';
	$img_klecks='';
	$anime=true;
	$rep=false;
	$paintmode='paintcom';

	session_sta();

	$adminpost=adminpost_valid();

	//pchファイルアップロードペイント
	if($adminpost){

		$pchfilename = isset($_FILES['pchup']['name']) ? basename($_FILES['pchup']['name']) : '';
		
		$pchtmp=isset($_FILES['pchup']['tmp_name']) ? $_FILES['pchup']['tmp_name'] : '';

		if(isset($_FILES['pchup']['error']) && in_array($_FILES['pchup']['error'],[1,2])){//容量オーバー
			return error($en? 'The file size is too big.':'ファイルサイズが大きすぎます。');
		} 

		if ($pchtmp && $_FILES['pchup']['error'] === UPLOAD_ERR_OK){
	
			$time = (string)(time().substr(microtime(),2,6));
			$pchext=pathinfo($pchfilename, PATHINFO_EXTENSION);
			$pchext=strtolower($pchext);//すべて小文字に
			//拡張子チェック
			if (!in_array($pchext, ['pch','chi','psd'])) {
				return error($en?'This file does not supported by the ability to load uploaded files onto the canvas.Supported formats are pch and chi.':'アップロードペイントで使用できるファイルはpch、chi、psdです。');
			}
			$pchup = TEMP_DIR.'pchup-'.$time.'-tmp.'.$pchext;//アップロードされるファイル名

			if(move_uploaded_file($pchtmp, $pchup)){//アップロード成功なら続行

				$pchup=TEMP_DIR.basename($pchup);//ファイルを開くディレクトリを固定
				if(!in_array(mime_content_type($pchup),["application/octet-stream","image/vnd.adobe.photoshop"])){
					safe_unlink($pchup);
					return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
				}
				if(($pchext==="pch")&&is_neo($pchup)){
					$app='neo';
						if($get_pch_size=get_pch_size($pchup)){
							list($picw,$pich)=$get_pch_size;//pchの幅と高さを取得
						}
					$pchfile = $pchup;
				} elseif($pchext==="chi"){
					$app='chi';
					$img_chi = $pchup;
				} elseif($pchext==="psd"){
					$app='klecks';
					$img_klecks = $pchup;
				}else{
					safe_unlink($pchup);
					return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
				}
			}
		}
	}
	$repcode='';
	$hide_animation=false;
	if($mode==="contpaint"){

		$imgfile = basename((string)filter_input(INPUT_POST,'imgfile'));
		$ctype = (string)filter_input(INPUT_POST, 'ctype');
		$type = (string)filter_input(INPUT_POST, 'type');
		$no = (string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
		$time = basename((string)filter_input(INPUT_POST, 'time'));
		$cont_paint_same_thread=(bool)filter_input(INPUT_POST, 'cont_paint_same_thread',FILTER_VALIDATE_BOOLEAN);

		if(is_file(LOG_DIR."{$no}.log")){
			if($type!=='rep'){
				$resto = $cont_paint_same_thread ? $no : '';
			}
		}
	if(!is_file(IMG_DIR.$imgfile)){
			return error($en? 'The article does not exist.':'記事がありません。');
		}
		list($picw,$pich)=getimagesize(IMG_DIR.$imgfile);//キャンバスサイズ

		$_pch_ext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);

		if($ctype=='pch'&& $_pch_ext){//動画から続き
			$pchfile = IMG_DIR.$time.$_pch_ext;
		}

		if($ctype=='img'){//画像から続き
			$animeform = false;
			$anime= false;
			$imgfile = IMG_DIR.$imgfile;
			if($_pch_ext==='.chi'){
				$img_chi =IMG_DIR.$time.'.chi';
			}
			if($_pch_ext==='.psd'){
				$img_klecks =IMG_DIR.$time.'.psd';
			}
		}
		$hide_animation = (bool)filter_input(INPUT_POST,'hide_animation',FILTER_VALIDATE_BOOLEAN);
		$hide_animation = $hide_animation ? 'true' : 'false';
		if($type==='rep'){//画像差し換え
			$rep=true;
			$pwd = t((string)filter_input(INPUT_POST, 'pwd'));
			$pwd=$pwd ? $pwd : t((string)filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード
			if(strlen($pwd) > 100) return error($en? 'Password is too long.':'パスワードが長すぎます。');
			if($pwd){
				$pwd=basename($pwd);
				$pwd=openssl_encrypt ($pwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV);//暗号化
				$pwd=bin2hex($pwd);//16進数に
			}
			$userip = get_uip();
			$paintmode='picrep';
			$id=$time;	//テンプレートでも使用。
			$repcode = substr(crypt(md5($no.$id.$userip.$pwd.uniqid()),'id'),-12);
			//念の為にエスケープ文字があればアルファベットに変換
			$repcode = strtr($repcode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~\t","ABCDEFGHIJKLMNOabcdefghijklmno");
		}
	}

	$parameter_day = date("Ymd");//JavaScriptのキャッシュ制御

	switch($app){
		case 'chi'://ChickenPaint
		
			$tool='chi';
			// HTML出力
			$templete='paint_chi.html';
			return include __DIR__.'/'.$skindir.$templete;

		case 'klecks':

			$tool ='klecks';
			$templete='paint_klecks.html';
			return include __DIR__.'/'.$skindir.$templete;

		case 'neo'://PaintBBS NEO

			global $petit_lot;

			$tool='neo';
			$appw = $picw + 150;//NEOの幅
			$apph = $pich + 172;//NEOの高さ
			if($apph < 560){$apph = 560;}//最低高
			//動的パレット
			$palettetxt = $en? 'palette_en.txt' : 'palette.txt';
			check_file(__DIR__.'/'.$palettetxt);  
			$lines =file($palettetxt);
			$pal=[];
			$arr_dynp=[];
			$arr_pal=[];
			$initial_palette = 'Palettes[0] = "#000000\n#FFFFFF\n#B47575\n#888888\n#FA9696\n#C096C0\n#FFB6FF\n#8080FF\n#25C7C9\n#E7E58D\n#E7962D\n#99CB7B\n#FCECE2\n#F9DDCF";';
			foreach ( $lines as $i => $line ) {
				$line=str_replace(["\r","\n","\t"],"",$line);
				$line=$line;
				list($pid,$pname,$pal[0],$pal[2],$pal[4],$pal[6],$pal[8],$pal[10],$pal[1],$pal[3],$pal[5],$pal[7],$pal[9],$pal[11],$pal[12],$pal[13]) = explode(",", $line);
				$arr_dynp[]=h($pname);
				$p_cnt=$i+1;
				ksort($pal);
				$arr_pal[$i] = 'Palettes['.h($p_cnt).'] = "#'.h(implode('\n#',$pal)).'";';
			}
			$palettes=$initial_palette.implode('',$arr_pal);
			$palsize = count($arr_dynp) + 1;

			// HTML出力
			$templete='paint_neo.html';
			return include __DIR__.'/'.$skindir.$templete;

		default:
			return error($en?'This operation has failed.':'失敗しました。');
	}

}
// お絵かきコメント 
function paintcom(){
	global $use_aikotoba,$boardname,$home,$skindir,$sage_all,$en,$mark_sensitive_image;
	global $usercode,$petit_lot; 

	aikotoba_required_to_view();
	$token=get_csrf_token();
	$userip = get_uip();
	//テンポラリ画像リスト作成
	$uresto = '';
	$handle = opendir(TEMP_DIR);
	$tmps = [];
	$hide_animation=false;
	while ($file = readdir($handle)) {
		if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
			$file=basename($file);
			$fp = fopen(TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");
			$hide_animation=($u_hide_animation==='true');
			$imgext=basename($imgext);
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			$uresto = $uresto ? 'res' :''; 
			if(is_file(TEMP_DIR.$file_name.$imgext)){ //画像があればリストに追加
				$pchext = check_pch_ext(TEMP_DIR . $file_name);
				$pchext = !$hide_animation ? $pchext : ''; 
				if($ucode === $usercode||($uip && ($uip === $userip))){
					$tmps[$file_name] = [$file_name.$imgext,$uresto,$pchext];
				}
			}
		}
	}
	closedir($handle);

	if(!empty($tmps)){
		$pictmp = 2;
		ksort($tmps);
		foreach($tmps as $tmp){
			list($tmpfile,$resto,$pchext)=$tmp;
			$tmpfile=basename($tmpfile);
			list($w,$h)=getimagesize(TEMP_DIR.$tmpfile);
			$tmp_img['w']=$w;
			$tmp_img['h']=$h;
			$tmp_img['src'] = TEMP_DIR.$tmpfile;
			$tmp_img['srcname'] = $tmpfile;
			$tmp_img['slect_src_val'] = $tmpfile.','.$resto.','.$pchext;
			$tmp_img['date'] = date("Y/m/d H:i", filemtime($tmp_img['src']));
			$out['tmp'][] = $tmp_img;
		}
	}
	$aikotoba=aikotoba_valid();
	if(!$use_aikotoba){
		$aikotoba=true;
	}
	$namec = (string)filter_input(INPUT_COOKIE,'namec');
	$pwdc = (string)filter_input(INPUT_COOKIE,'pwdc');
	$urlc = (string)filter_input(INPUT_COOKIE,'urlc');

	// HTML出力
	$templete='paint_com.html';
	return include __DIR__.'/'.$skindir.$templete;
}

//コンティニュー前画面
function to_continue(){

	global $boardname,$use_diary,$use_aikotoba,$set_nsfw,$skindir,$en,$password_require_to_continue;
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$petit_lot;

	aikotoba_required_to_view();

	$appc=(string)filter_input(INPUT_COOKIE,'appc');
	$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');

	$no = (string)filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input(INPUT_GET, 'id');//intの範囲外

	$flag = false;

	if(is_file(LOG_DIR."{$no}.log")){
		check_open_no($no);
		$rp=fopen(LOG_DIR."{$no}.log","r");
		while ($line = fgets($rp)) {
			if(!trim($line)){
				continue;
			}
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$_pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no){
				$flag=true;
				break;
			}
		}
		closeFile ($rp);
	}
	if(!$flag || !$imgfile || !is_file(IMG_DIR.$imgfile)){//画像が無い時は処理しない
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	if(!check_elapsed_days($time)&&!adminpost_valid()){
		return error($en? 'The article does not exist.':'記事がありません。');
	}

	$hidethumbnail = ($thumbnail==='hide_thumbnail'||$thumbnail==='hide_');
	$thumbnail=($thumbnail==='thumbnail'||$thumbnail==='hide_thumbnail');
	list($picw, $pich) = getimagesize(IMG_DIR.$imgfile);
	$picfile = $thumbnail ? THUMB_DIR.$time.'s.jpg' : IMG_DIR.$imgfile;

	$pch_exists = in_array($_pchext,['hide_animation','.pch']);
	$hide_animation_checkd = ($_pchext==='hide_animation');

	$pchext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);

	$pchext=basename($pchext);
	$select_app = false;
	$app_to_use = false;
	$ctype_pch = false;
	$download_app_dat=true;
	if($pchext==='.pch'){
		$ctype_pch = true;
		$app_to_use = "neo";
	}elseif($pchext==='.chi'){
		$app_to_use = 'chi';
	}elseif($pchext==='.psd'){
		$app_to_use = 'klecks';
	}else{
		$select_app = true;
		$download_app_dat=false;
	}

	//日記判定処理
	session_sta();
	$adminpost=adminpost_valid();
	$adminmode = ($adminpost||admindel_valid());
	$aikotoba=aikotoba_valid();

	if(!$use_aikotoba){
	$aikotoba=true;
	}

	$arr_apps=app_to_use();
	$count_arr_apps=count($arr_apps);
	$use_paint=!empty($count_arr_apps);
	$select_app= $select_app ? ($count_arr_apps>1) : false;
	$app_to_use=($use_paint && !$select_app && !$app_to_use) ? $arr_apps[0]: $app_to_use;
	// nsfw
	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);

	$is_badhost=is_badhost();
	// HTML出力
	$templete='continue.html';
	return include __DIR__.'/'.$skindir.$templete;
	
}

//アプリ固有ファイルのダウンロード
function download_app_dat(){

	global $en;

	check_same_origin();

	$pwd=(string)filter_input(INPUT_POST,'pwd');
	$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	$no = (string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input(INPUT_POST, 'id');//intの範囲外

	check_open_no($no);
	if(!is_file(LOG_DIR."{$no}.log")){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	$rp=fopen(LOG_DIR."{$no}.log","r");
	$flag=false;
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$_pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
		if($id===$time && $no===$_no){
			if(!adminpost_valid()&&!admindel_valid()&&(!$pwd || !password_verify($pwd,$hash))){
				return error($en?'Password is incorrect.':'パスワードが違います。');
			}
			$flag=true;
			break;

		} 
	}
	closeFile ($rp);
	$time=basename($time);
	$pchext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);
	$pchext=basename($pchext);
	$filepath= IMG_DIR.$time.$pchext;
	if(!$pchext){
		return error($en?'This operation has failed.':'失敗しました。');
	}
	$mime_type = mime_content_type($filepath);
	header('Content-Type: '.$mime_type);
	header('Content-Length: '.filesize($filepath));
	header('Content-Disposition: attachment; filename="'.h(basename($filepath)).'"');

	readfile($filepath);
}

// 画像差し換え
function img_replace(){

	global $use_thumb,$max_w,$max_h,$res_max_w,$res_max_h,$max_px,$en,$use_upload,$mark_sensitive_image;
	global $admin_pass,$max_file_size_in_png_format_upload,$max_file_size_in_png_format_paint;

	$no = t((string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT));
	$no = $no ? $no :t((string)filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT));
	$id = t((string)filter_input(INPUT_POST, 'id'));//intの範囲外
	$id = $id ? $id :t((string)filter_input(INPUT_GET, 'id'));

	$getpwd = t((string)filter_input(INPUT_GET, 'pwd'));
	$repcode = t((string)filter_input(INPUT_GET, 'repcode'));
	$userip = t(get_uip());
	//ホスト取得
	$host = $userip ? t(gethostbyaddr($userip)) : '';
	//ユーザーid
	$userid = t(getId($userip));
	$getpwd= basename($getpwd);
	$getpwd = $getpwd ? hex2bin($getpwd): '';//バイナリに
	$pwd = $getpwd ? 
	openssl_decrypt($getpwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV):'';//復号化

	if(strlen($pwd) > 100) return error($en? 'Password is too long.':'パスワードが長すぎます。');

	$adminpost=(adminpost_valid()||($pwd && $pwd === $admin_pass));
	$admindel=admindel_valid();

	//アップロード画像の差し換え
	$up_tempfile = isset($_FILES['imgfile']['tmp_name']) ? $_FILES['imgfile']['tmp_name'] : ''; // 一時ファイル名
	if (isset($_FILES['imgfile']['error']) && $_FILES['imgfile']['error'] === UPLOAD_ERR_NO_FILE){
		return error($en?'Please attach an image.':'画像を添付してください。');
	} 
	if(isset($_FILES['imgfile']['error']) && in_array($_FILES['imgfile']['error'],[1,2])){//容量オーバー
		return error($en? "Upload failed.The file size is too big.":"アップロードに失敗しました。ファイルサイズが大きすぎます。");
	} 
	$is_upload=false;
	$tool = '';
	if ($up_tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK){

		$img_type = isset($_FILES['imgfile']['type']) ? $_FILES['imgfile']['type'] : '';

		if (!in_array($img_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])) {
			safe_unlink($up_tempfile);
			return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
		}

		check_csrf_token();
		$is_upload = true;
		$tool = 'upload';
		$pwd = t((string)filter_input(INPUT_POST, 'pwd'));//アップロードの時はpostのパスワード

	}
	$tempfile='';
	$file_name='';
	$starttime='';
	$postedtime='';
	$repfind=false;
	$hide_animation=false;
	if(!$is_upload){
		/*--- テンポラリ捜査 ---*/
		$handle = opendir(TEMP_DIR);
		while ($file = readdir($handle)) {
			if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
				$file=basename($file);
				$fp = fopen(TEMP_DIR.$file, "r");
				$userdata = fread($fp, 1024);
				fclose($fp);
				list($uip,$uhost,$uagent,$imgext,$ucode,$urepcode,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");//区切りの"\t"を行末に
				$hide_animation = ($u_hide_animation==='true');
				$tool= in_array($tool,['neo','chi','klecks']) ? $tool : '???';
				$file_name = pathinfo($file, PATHINFO_FILENAME );//拡張子除去
				//画像があり、認識コードがhitすれば抜ける
				$imgext=basename($imgext);
				if($file_name && is_file(TEMP_DIR.$file_name.$imgext) && $urepcode === $repcode){
					$repfind=true;
					break;
				}
			}
		}
		closedir($handle);
		if(!$repfind){//見つからなかった時は
			return paintcom();//新規投稿
		}
		$tempfile=TEMP_DIR.$file_name.$imgext;
	}
	if($up_tempfile && $is_upload && !is_file($up_tempfile)){
		return error($en?'Please attach an image.':'画像を添付してください。');
	}
	//ログ読み込み
	if(!is_file(LOG_DIR."{$no}.log")){

		if($is_upload){//該当記事が無い時はエラー
			return error($en? 'The article does not exist.':'記事がありません。');
		} 
		return paintcom();//該当記事が無い時は新規投稿。
	}

	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		if(!trim($_line)){
			continue;
		}
		$alllog_arr[]=$_line;	
	}
	if(empty($alllog_arr)){
		closeFile($fp);
		if($is_upload){//該当記事が無い時はエラー
			return error($en?'This operation has failed.':'失敗しました。');
		} 
		return paintcom();//該当記事が無い時は新規投稿。
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r+");
	flock($rp, LOCK_EX);
	$r_arr=[];
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		$r_arr[]=$line;
	}
	if(empty($r_arr)){
		closeFile($rp);
		closeFile($fp);
		if($is_upload){//該当記事が無い時はエラー
			return error($en?'This operation has failed.':'失敗しました。');
		} 
		return paintcom();//該当記事が無い時は新規投稿。
	}

	$flag=false;
	foreach($r_arr as $i => $line){
		list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));
		if($id===$_time && $no===$_no){

			if(($is_upload) && ($_tool!=='upload')) {

				closeFile($rp);
				closeFile($fp);
				return error($en?'This operation has failed.':'失敗しました。');
			}

			if(($is_upload && $admindel) || ($pwd && password_verify($pwd,$_hash))){
				$flag=true;
				break;
			}
		}
	}
	if(!check_elapsed_days($_time)&&(!$adminpost && !$admindel)){//指定日数より古い画像差し換えは新規投稿にする

		closeFile($rp);
		closeFile($fp);
		if($is_upload){
			return error($en?'This operation has failed.':'失敗しました。');
		} 
		return paintcom();
	}

	if(!$flag){
		closeFile($rp);
		closeFile($fp);
		if($is_upload){//該当記事が無い時はエラー
			return error($en?'This operation has failed.':'失敗しました。');
		} 
		return paintcom();//該当記事が無い時は新規投稿。
	}
	$time = (string)(time().substr(microtime(),2,6));
	$testexts=['.gif','.jpg','.png','.webp'];
	foreach($testexts as $testext){
		if(is_file(IMG_DIR.$time.$testext)){
			$time=(string)(substr($time,0,-6)+1).(string)substr($time,-6);
			break;
		}
	}
	$time= is_file(TEMP_DIR.$time.'.tmp') ?	(string)(substr($time,0,-6)+1).(string)substr($time,-6) : $time;
	$time=basename($time);
	$upfile=TEMP_DIR.$time.'.tmp';

	if($is_upload && ($_tool==='upload') && ( $use_upload || $adminpost || $admindel) && is_file($up_tempfile)){
		move_uploaded_file($up_tempfile,$upfile);
	}
	if(!$is_upload && $repfind && is_file($tempfile) && ($_tool !== 'upload')){
		copy($tempfile, $upfile);
	}

	if(!is_file($upfile)){
		closeFile($rp);
		closeFile($fp);
		return error($en?'This operation has failed.':'失敗しました。');
	} 
	chmod($upfile,0606);
	if($is_upload&&($_tool==='upload')){//実体データの縮小
		$max_px=isset($max_px) ? $max_px : 1024;
		thumb(TEMP_DIR,$time.'.tmp',$time,$max_px,$max_px,['toolarge'=>1]);
	}	
	clearstatcache();
	$filesize=filesize($upfile);
	if(($filesize > $max_file_size_in_png_format_paint * 1024) || ($is_upload && $filesize > $max_file_size_in_png_format_upload * 1024)){//指定サイズを超えていたら
		if ($im_jpg = png2jpg($upfile)) {//PNG→JPEG自動変換
			clearstatcache();
			if(filesize($im_jpg)<$filesize){//JPEGのほうが小さい時だけ
				rename($im_jpg,$upfile);//JPEGで保存
				chmod($upfile,0606);
			} else{//PNGよりファイルサイズが大きくなる時は
				unlink($im_jpg);//作成したJPEG画像を削除
			}
		}
	}
		
	$img_type=mime_content_type($upfile);

	$imgext = getImgType($img_type);

	if (!$imgext) {
		closeFile($fp);
		closeFile($rp);
		safe_unlink($upfile);
		return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
	}
	list($w, $h) = getimagesize($upfile);
	$img_md5=md5_file($upfile);
	
	//チェックするスレッド数。 
	$n= 15;
	$chk_log_arr=array_slice($alllog_arr,0,$n,false);
	$chk_resnos=[];
	foreach($chk_log_arr as $chk_log){
		list($chk_resno)=explode("\t",$chk_log);
		$chk_resnos[]=$chk_resno;
	}
	$chk_lines=[];

	foreach($chk_resnos as $chk_resno){
		if(($chk_resno!==$no)&&is_file(LOG_DIR."{$chk_resno}.log")){
			check_open_no($chk_resno);
			$cp=fopen(LOG_DIR."{$chk_resno}.log","r");
			while($line=fgets($cp)){
				if(!trim($line)){
					continue;
				}
			$chk_lines[]=$line;//画像
			}
			fclose($cp);
		}
	}
	$chk_images=array_merge($chk_lines,$r_arr);
	foreach($chk_images as $chk_line){
		list($chk_no,$chk_sub,$chk_name,$chk_verified,$chk_com,$chk_url,$chk_imgfile,$chk_w,$chk_h,$chk_thumbnail,$chk_painttime,$chk_log_md5,$chk_tool,$chk_pchext,$chk_time,$chk_first_posted_time,$chk_host,$chk_userid,$chk_hash,$chk_oya_)=explode("\t",trim($chk_line));
		$_chk_time=(strlen($chk_time)>15) ? substr($chk_time,0,-6) : substr($chk_time,0,-3);//秒単位に戻す
		if($is_upload && ((string)substr($time,0,-6) === (string)$_chk_time)){//投稿時刻の重複回避
			safe_unlink($upfile);
			closeFile($fp);
			closeFile($rp);
			return error($en? 'Please wait a little.':'少し待ってください。');
		}
		if(!$is_upload && ((string)$time === (string)$chk_time)){
			$time=(string)(substr($time,0,-6)+1).(string)substr($time,-6);
		}
		if($is_upload && $chk_log_md5 && ($chk_log_md5 === $img_md5)){
			safe_unlink($upfile);
			closeFile($fp);
			closeFile($rp);
			return error($en?'Image already exists.':'同じ画像がありました。');
		}
	}

	$imgfile = $time.$imgext;
	rename($upfile,IMG_DIR.$imgfile);
	if(!is_file(IMG_DIR.$imgfile)){
		return error($en?'This operation has failed.':'失敗しました。');
	}
	chmod(IMG_DIR.$imgfile,0606);
	$src='';
	$pchext='';
	//PCHファイルアップロード
	// .pch, .spch,.chi,.psd ブランク どれかが返ってくる
	if (!$is_upload && $repfind && ($pchext = check_pch_ext(TEMP_DIR . $file_name,['upload'=>true]))) {
		$src = TEMP_DIR . $file_name . $pchext;
		$dst = IMG_DIR . $time . $pchext;
		if(copy($src, $dst)){
			chmod($dst, 0606);
		}
	}
	if(in_array($_pchext,['.pch','hide_animation'])){
		$pchext= !$hide_animation ?  '.pch' : 'hide_animation'; 
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
	//webpサムネイル
	thumb(IMG_DIR,$imgfile,$time,300,800,['webp'=>true]);
	$hide_thumbnail = ($_imgfile && ($_thumbnail==='hide_thumbnail'||$_thumbnail==='hide_')) ? 'hide_' : '';

	$thumbnail =  $hide_thumbnail.$thumbnail;

	//描画時間追加

	$painttime = '';
	if($starttime && is_numeric($starttime) && $postedtime && is_numeric($postedtime)){
		$psec=(int)$postedtime-(int)$starttime;
		$painttime=(int)$_painttime+(int)$psec;
	}
	
	$r_line= "$_no\t$_sub\t$_name\t$_verified\t$_com\t$_url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$_first_posted_time\t$host\t$userid\t$_hash\t$_oya\n";

	$r_arr[$i] = $r_line;

	if($_oya ==='oya'){

		$strcut_com=mb_strcut($_com,0,120);
		$newline = "$_no\t$_sub\t$_name\t$_verified\t$strcut_com\t$_url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$img_md5\t$tool\t$pchext\t$time\t$_first_posted_time\t$host\t$userid\t$_hash\toya\n";

		$flag=false;
		foreach($alllog_arr as $i => $val){
			list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_) = explode("\t",trim($val));

			if(($id===$time_ && $no===$no_) &&
			(($admindel && $is_upload ||
			($pwd && password_verify($pwd,$hash_))))){
				$alllog_arr[$i] = $newline;
				$flag=true;
				break;
			}
		}
		if(!$flag){
			closeFile($rp);
			closeFile($fp);
			safe_unlink(IMG_DIR.$imgfile);
			if($is_upload){//該当記事が無い時はエラー
				return error($en?'This operation has failed.':'失敗しました。');
			} 
			return paintcom();//該当記事が無い時は新規投稿。
		}

		writeFile($fp,implode("",$alllog_arr));

	}
	writeFile($rp, implode("", $r_arr));
	closeFile($rp);
	closeFile($fp);
	
	//旧ファイル削除
	delete_files($_imgfile, $_time);
	//ワークファイル削除
	safe_unlink($src);
	safe_unlink($tempfile);
	safe_unlink($up_tempfile);
	safe_unlink($upfile);
	safe_unlink(TEMP_DIR.$file_name.".dat");

	if($is_upload){
		return edit_form($time,$no);//編集画面にもどる
	}
	return header('Location: ./?resno='.$no.'#'.$time);

}

// 動画表示
function pchview(){

	global $boardname,$skindir,$en,$petit_lot;

	aikotoba_required_to_view();

	$imagefile = basename((string)filter_input(INPUT_GET, 'imagefile'));
	$no = (string)filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
	$id = pathinfo($imagefile, PATHINFO_FILENAME);
	check_open_no($no);
	if(!is_file(LOG_DIR."{$no}.log")){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	$rp=fopen(LOG_DIR."{$no}.log","r");
	$flag=false;
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
		if($id===$time && $no===$_no){
			$flag=true;
			break;
		} 
	}
	closeFile ($rp);

	$pchext=basename($pchext);

	$view_replay = ($pchext==='.pch') && check_pch_ext(IMG_DIR . $time) ;
	if(!$view_replay||!is_file(IMG_DIR.$imagefile)){
		return error('ファイルがありません。');
	}
	$pch=$time;
	$pchfile = IMG_DIR.$time.$pchext;
	list($picw, $pich) = getimagesize(IMG_DIR.$imagefile);
	$appw = $picw < 200 ? 200 : $picw;
	$apph = $pich < 200 ? 200 : $pich + 26;
	// HTML出力
	$templete='pch_view.html';
	return include __DIR__.'/'.$skindir.$templete;

}
//削除前の確認画面
function confirmation_before_deletion ($edit_mode=''){

	global $boardname,$home,$petit_ver,$petit_lot,$skindir,$use_aikotoba,$set_nsfw,$en;
	//管理者判定処理
	check_same_origin();
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$resmode = ((string)filter_input(INPUT_POST,'resmode')==='true');
	$resmode = $resmode ? 'true' : 'false';
	$postpage = (int)filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$postresno = (int)filter_input(INPUT_POST,'postresno',FILTER_VALIDATE_INT);
	$postresno = $postresno ? $postresno : false; 

	$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
	$edit_mode = (string)filter_input(INPUT_POST,'edit_mode');

	if(!($admindel||$userdel)){
		return error($en?'This operation has failed.':'失敗しました。');
	}

	if($edit_mode!=='delmode' && $edit_mode!=='editmode'){
		return error($en?'This operation has failed.':'失敗しました。');
	}
	$id = t((string)filter_input(INPUT_POST,'id'));//intの範囲外
	$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));

	if(!is_file(LOG_DIR."{$no}.log")){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r");
	flock($rp, LOCK_EX);
	$r_arr=[];
	while ($r_line = fgets($rp)) {
		if(!trim($r_line)){
			continue;
		}
		$r_arr[]=$r_line;
	}
	if(empty($r_arr)){
		closeFile($rp);
		return error($en?'This operation has failed.':'失敗しました。');
	}
	$find=false;
	foreach($r_arr as $i =>$val){
		$_line=explode("\t",trim($val));
		list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$_line;
		if($id===$time && $no===$_no){

			$out[0][]=create_res($_line);
			$find=true;
			break;
			
		}

	}
	if(!$find){
		closeFile ($rp);
		return error($en?'The article was not found.':'記事が見つかりません。');
	}

	closeFile ($rp);

	$token=get_csrf_token();

	if(!$use_aikotoba){
		$aikotoba=true;
	}
	// nsfw
	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);
	$count_r_arr=count($r_arr);

	if($edit_mode==='delmode'){
		$templete='before_del.html';
		return include __DIR__.'/'.$skindir.$templete;
	}
	if($edit_mode==='editmode'){
		$templete='before_edit.html';
		return include __DIR__.'/'.$skindir.$templete;
	}
	return error($en?'This operation has failed.':'失敗しました。');
}
//編集画面
function edit_form($id='',$no=''){

	global  $petit_ver,$petit_lot,$home,$boardname,$skindir,$set_nsfw,$en,$max_kb,$use_upload,$mark_sensitive_image;

	check_same_origin();

	$max_byte = $max_kb * 1024*2;
	$token=get_csrf_token();
	$admindel=admindel_valid();
	$adminpost=adminpost_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');

	$pwd=(string)filter_input(INPUT_POST,'pwd');
	$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	
	if(!($admindel||($userdel&&$pwd))){
		return error($en?'This operation has failed.':'失敗しました。');
	}
	$id_and_no=(string)filter_input(INPUT_POST,'id_and_no');

	if($id_and_no){
		list($id,$no)=explode(",",trim($id_and_no));
	}

	check_open_no($no);
	if(!is_file(LOG_DIR."{$no}.log")){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	$rp=fopen(LOG_DIR."{$no}.log","r");
	flock($rp, LOCK_EX);
	$r_arr=[];
	while ($r_line = fgets($rp)) {
		if(!trim($r_line)){
			continue;
		}
		$r_arr[]=$r_line;
	}
	if(empty($r_arr)){
		closeFile($rp);
		return error($en?'This operation has failed.':'失敗しました。');
	}

	$flag=false;
	foreach($r_arr as $val){

		$line=explode("\t",trim($val));

		list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;
		if($id===$time && $no===$_no){
		
			if($admindel||(check_elapsed_days($time)&&$pwd&&password_verify($pwd,$hash))){
				$flag=true;
				break;
			}
		}
	}

	if(!$flag){
		closeFile($rp);
		return error($en?'This operation has failed.':'失敗しました。');
	}
	closeFile($rp);

	$out[0][]=create_res($line);//$lineから、情報を取り出す;

	$resno=(int)filter_input(INPUT_POST,'postresno',FILTER_VALIDATE_INT);//古いバージョンで使用
	$page=(int)filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);

	foreach($line as $i => $val){
		$line[$i]=h($val);
	}
	list($_no,$sub,$name,$verified,$_com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;

	$com=h(str_replace('"\n"',"\n",$com));

	$pch_exists = in_array($pchext,['hide_animation','.pch']);
	$hide_animation_checkd = ($pchext==='hide_animation');
	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);

	$hide_thumb_checkd = ($thumbnail==='hide_thumbnail'||$thumbnail==='hide_');

	$admin = ($admindel||$adminpost);
	// HTML出力
	$templete='edit_form.html';
	return include __DIR__.'/'.$skindir.$templete;

}

//編集
function edit(){
	global $name_input_required,$max_com,$en,$mark_sensitive_image;

	check_csrf_token();

	//POSTされた内容を取得
	$userip =t(get_uip());
	//ホスト取得
	$host = $userip ? t(gethostbyaddr($userip)) : '';
	$userid = t(getId($userip));

	$sub = t((string)filter_input(INPUT_POST,'sub'));
	$name = t((string)filter_input(INPUT_POST,'name'));
	$com = t((string)filter_input(INPUT_POST,'com'));
	$url = t((string)filter_input(INPUT_POST,'url',FILTER_VALIDATE_URL));
	$id = t((string)filter_input(INPUT_POST,'id'));//intの範囲外
	$no = t((string)filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT));
	$hide_thumbnail = $mark_sensitive_image ? (bool)filter_input(INPUT_POST,'hide_thumbnail',FILTER_VALIDATE_BOOLEAN) : false;
	$hide_animation=(bool)filter_input(INPUT_POST,'hide_animation',FILTER_VALIDATE_BOOLEAN);
	$pwd=(string)filter_input(INPUT_POST,'pwd');
	$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	session_sta();
	$admindel=admindel_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	if(!($admindel||($userdel&&$pwd))){
		return error($en?'This operation has failed.':'失敗しました。');
	}

	//NGワードがあれば拒絶
	Reject_if_NGword_exists_in_the_post();

	//POSTされた値をログファイルに格納する書式にフォーマット
	$formatted_post=create_formatted_text_from_post($name,$sub,$url,$com);
	$name = $formatted_post['name'];
	$sub = $formatted_post['sub'];
	$url = $formatted_post['url'];
	$com = $formatted_post['com'];

	if(!$name){
		if($name_input_required){
			return error($en?'Please enter your name.':'名前がありません。');
		}else{
			$name='anonymous';
		}
	}
	//ログ読み込み
	if(!is_file(LOG_DIR."{$no}.log")){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);

	$r_arr=[];
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r+");
	flock($rp, LOCK_EX);
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		$r_arr[]=$line;
	}
	if(empty($r_arr)){
		closeFile($rp);
		closeFile($fp);
		return error($en?'This operation has failed.':'失敗しました。');
	}

	$flag=false;
	foreach($r_arr as $i => $line){

		list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));
			
		if($id===$_time && $no===$_no){

			if(!$_name && !$_com && !$_url && !$_imgfile && !$_userid && ($_oya==='oya')){//削除ずみのoyaの時
				return error($en?'This operation has failed.':'失敗しました。');
			}

			if($admindel||(check_elapsed_days($_time)&&$pwd&&password_verify($pwd,$_hash))){
				$flag=true;
				break;
			}
		}
	}
	if(!$flag){
		closeFile($rp);
		closeFile($fp);
		return error($en?'This operation has failed.':'失敗しました。');
	}
	if(!$_imgfile && !$com){
		closeFile($rp);
		closeFile($fp);
		return error($en?'Please write something.':'何か書いて下さい。');
	}

	$sub=($_oya==='res') ? $_sub : $sub; 

	$thumbnail=is_file(THUMB_DIR.$_time.'s.jpg') ? 'thumbnail': '';
	$hide_thumbnail=($_imgfile && $hide_thumbnail) ? 'hide_' : '';
	$thumbnail =  $mark_sensitive_image ? $hide_thumbnail.$thumbnail : $_thumbnail;

	if(in_array($pchext,['.pch','hide_animation'])){
		$pchext= $hide_animation ? 'hide_animation' : '.pch'; 
	}

	$sub=(!$sub) ? ($en? 'No subject':'無題') : $sub;

	$r_line= "$_no\t$sub\t$name\t$_verified\t$com\t$url\t$_imgfile\t$_w\t$_h\t$thumbnail\t$_painttime\t$_log_md5\t$_tool\t$pchext\t$_time\t$_first_posted_time\t$host\t$userid\t$_hash\t$_oya\n";
	
	$r_arr[$i] = $r_line;

	if($_oya==='oya'){
	//コメントを120バイトに短縮
	$strcut_com=mb_strcut($com,0,120);
	$newline = "$_no\t$sub\t$name\t$_verified\t$strcut_com\t$url\t$_imgfile\t$_w\t$_h\t$thumbnail\t$_painttime\t$_log_md5\t$_tool\t$pchext\t$_time\t$_first_posted_time\t$host\t$userid\t$_hash\toya\n";

		$alllog_arr=[];
		while ($_line = fgets($fp)) {
			if(!trim($_line)){
				continue;
			}
			$alllog_arr[]=$_line;	
		}
		if(empty($alllog_arr)){
			closeFile($rp);
			closeFile($fp);
			return error($en?'This operation has failed.':'失敗しました。');
		}
		$flag=false;
		foreach($alllog_arr as $i => $val){
			list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_) = explode("\t",trim($val));
			if(($id===$time_ && $no===$no_) &&
			($admindel || ($pwd && password_verify($pwd,$hash_)))){

				$alllog_arr[$i] = $newline;
				$flag=true;
				break;
			}
		}
		if(!$flag){
			closeFile($rp);
			closeFile($fp);
			return error($en?'This operation has failed.':'失敗しました。');
		}

		writeFile($fp,implode("",$alllog_arr));
	}
	writeFile($rp, implode("", $r_arr));

	closeFile($rp);
	closeFile($fp);

	unset($_SESSION['userdel']);

	return header('Location: ./?resno='.$no.'#'.$_time);

}

//記事削除
function del(){
	global $en;

	check_csrf_token();

	$pwd=(string)filter_input(INPUT_POST,'pwd');
	$pwdc=(string)filter_input(INPUT_COOKIE,'pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	session_sta();
	$admindel=admindel_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	if(!($admindel||($userdel&&$pwd))){
		return error($en?'This operation has failed.':'失敗しました。');
	}
	$id_and_no=(string)filter_input(INPUT_POST,'id_and_no');
	if(!$id_and_no){
		return error($en?'Please select an article.':'記事が選択されていません。');
	}
	$id=$no='';
	if($id_and_no){
		list($id,$no)=explode(",",trim($id_and_no));
	}
	$delete_thread=(bool)filter_input(INPUT_POST,'delete_thread',FILTER_VALIDATE_BOOLEAN);
	$fp=fopen(LOG_DIR."alllog.log","r+");
	flock($fp, LOCK_EX);

	if(!is_file(LOG_DIR."{$no}.log")){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r+");
	flock($rp, LOCK_EX);
	$r_arr=[];
	while ($r_line = fgets($rp)) {
		if(!trim($r_line)){
			continue;
		}
		$r_arr[]=$r_line;
	}
	if(empty($r_arr)){
		closeFile ($rp);
		closeFile($fp);
		return error($en?'This operation has failed.':'失敗しました。');
	}

	$find=false;
	foreach($r_arr as $i =>$val){
		list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($val));
		if($id===$time && $no===$_no){
		
			if(!$admindel){
				if(!$pwd||!password_verify($pwd,$hash)){
					closeFile ($rp);
					closeFile($fp);
					return error($en?'This operation has failed.':'失敗しました。');
				}
			}

			$count_r_arr=count($r_arr);
			list($d_no,$d_sub,$d_name,$s_verified,$d_com,$d_url,$d_imgfile,$d_w,$d_h,$d_thumbnail,$d_painttime,$d_log_md5,$d_tool,$d_pchext,$d_time,$d_first_posted_time,$d_host,$d_userid,$d_hash,$d_oya)=explode("\t",trim($r_arr[0]));
			$res_oya_deleted=(!$d_name && !$d_com && !$d_url && !$d_imgfile && !$d_userid && ($d_oya==='oya'));

			if(($oya==='oya')||(($count_r_arr===2) && $res_oya_deleted)){//スレッド削除?
				$alllog_arr=[];
				while ($_line = fgets($fp)) {
					if(!trim($_line)){
						continue;
					}
					$alllog_arr[]=$_line;	
				}
				if(empty($alllog_arr)){
					closeFile ($rp);
					closeFile($fp);
					return error($en?'This operation has failed.':'失敗しました。');
				}
				$flag=false;
				foreach($alllog_arr as $j =>$_val){//全体ログ
					list($no_,$sub_,$name_,$verified_,$com_,$url_,$_imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_md5_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=explode("\t",trim($_val));
					$alllog_oya_deleted=($no===$no_ && !$name_ && !$com_ && !$url_ && !$_imgfile_ && !$userid_ && ($oya_==='oya'));

					if(($alllog_oya_deleted && ($no===$no_))||((($id===$time_) && $no===$no_) &&
					( $admindel || ($pwd && password_verify($pwd,$hash_))))){
						$flag=true;
						break;
					}
				}

				if(!$flag){
					closeFile ($rp);
					closeFile($fp);
					return error($en?'This operation has failed.':'失敗しました。');
				}
				if($count_r_arr===1 || (($count_r_arr===2) && $res_oya_deleted) || $delete_thread){

					unset($alllog_arr[$j]);
					foreach($r_arr as $r_line) {//スレッドの一連のファイルを削除
						list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_md5,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($r_line));
						
						delete_files ($_imgfile, $_time);//一連のファイルを削除
						
					}
					closeFile ($rp);
					safe_unlink(LOG_DIR.$no.'.log');
					
				}else{
					delete_files ($imgfile, $time);//該当記事の一連のファイルを削除
					$deleted_sub = $en? 'No subject':'無題';
					$newline="$no\t$deleted_sub\t\t\t\t\t\t\t\t\t\t\t\t\t$time_\t$first_posted_time_\t$host_\t\t$hash_\toya\n";
					$alllog_arr[$j]=$newline;
					$r_arr[$i]=$newline;
					writeFile ($rp,implode("",$r_arr));
					closeFile ($rp);

				}

				writeFile($fp,implode("",$alllog_arr));
		
			}else{
				
				unset($r_arr[$i]);
				delete_files ($imgfile, $time);//一連のファイルを削除
				writeFile ($rp,implode("",$r_arr));
				closeFile ($rp);
			}
			$find=true;
			break;
		}
	}
	closeFile($fp);

	if(!$find){
		return error($en?'The article was not found.':'記事が見つかりません。');
	}

	unset($_SESSION['userdel']);
	$resno=(string)filter_input(INPUT_POST,'postresno');
	//多重送信防止
	if((string)filter_input(INPUT_POST,'resmode')==='true'){
		if(!is_file(LOG_DIR.$resno.'.log')){
			return header('Location: ./');
		}
		return header('Location: ./?resno='.$resno);
	}
	return header('Location: ./?page='.(int)filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT));
}

//検索画面
function search(){
	global $use_aikotoba,$home,$skindir;
	global $boardname,$petit_ver,$petit_lot,$set_nsfw,$en; 
	global $max_search,$search_images_pagedef,$search_comments_pagedef; 

	aikotoba_required_to_view();

	//検索可能最大数
	$max_search= isset($max_search) ? $max_search : 300;

	//画像検索の時の1ページあたりの表示件数
	$search_images_pagedef = isset($search_images_pagedef) ? $search_images_pagedef : 60;
	//通常検索の時の1ページあたりの表示件数
	$search_comments_pagedef = isset($search_comments_pagedef) ? $search_comments_pagedef : 30;

	$imgsearch=(bool)filter_input(INPUT_GET,'imgsearch',FILTER_VALIDATE_BOOLEAN);
	$page=(int)filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$q=(string)filter_input(INPUT_GET,'q');
	$q=urldecode($q);
	$q=mb_convert_kana($q, 'rn', 'UTF-8');
	$q=str_replace(array(" ", "　"), "", $q);
	$q=str_replace("〜","～",$q);//波ダッシュを全角チルダに
	$radio =(int)filter_input(INPUT_GET,'radio',FILTER_VALIDATE_INT);

	if($imgsearch){
		$pagedef=$search_images_pagedef;//画像検索の時の1ページあたりの表示件数
	}
	else{
		$pagedef=$search_comments_pagedef;//通常検索の時の1ページあたりの表示件数
	}
	//ログの読み込み
	$arr=[];
	$i=0;
	$j=0;
	$fp=fopen("log/alllog.log","r");
	while ($log = fgets($fp)) {
		if(!trim($log)){
			continue;
		}
		list($resno)=explode("\t",$log);
		$resno=basename($resno);
		//個別スレッドのループ
		if(!is_file(LOG_DIR."{$resno}.log")){
			continue;	
		}
		$cp=fopen("log/{$resno}.log","r");
		while($line=fgets($cp)){

			list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",$line);
	
			if(!$name && !$com && !$url && !$imgfile && !$userid){//この記事はありませんの時は表示しない
				continue;
			}
			$continue_to_search=true;
			if($imgsearch){//画像検索の場合
				$continue_to_search=(bool)$imgfile;//画像があったら
			}

			if($continue_to_search){
				if($radio===1||$radio===2||$radio===0){
					$s_name=mb_convert_kana($name, 'rn', 'UTF-8');//全角英数を半角に
					$s_name=str_replace(array(" ", "　"), "", $s_name);
					$s_name=str_replace("〜","～", $s_name);//波ダッシュを全角チルダに
				}
				else{
					$s_sub=mb_convert_kana($sub, 'rn', 'UTF-8');//全角英数を半角に
					$s_sub=str_replace(array(" ", "　"), "", $s_sub);
					$s_sub=str_replace("〜","～", $s_sub);//波ダッシュを全角チルダに
					$s_com=mb_convert_kana($com, 'rn', 'UTF-8');//全角英数を半角に
					$s_com=str_replace(array(" ", "　"), "", $s_com);
					$s_com=str_replace("〜","～", $s_com);//波ダッシュを全角チルダに
				}
				
				//ログとクエリを照合
				if($q===''||//空白なら
						$q!==''&&$radio===3&&stripos($s_com,$q)!==false||//本文を検索
						$q!==''&&$radio===3&&stripos($s_sub,$q)!==false||//題名を検索
						$q!==''&&($radio===1||$radio===0)&&stripos($s_name,$q)===0||//作者名が含まれる
						$q!==''&&($radio===2&&$s_name===$q)//作者名完全一致
				){
					$hidethumb = ($thumbnail==='hide_thumbnail'||$thumbnail==='hide_');

					$thumb= ($thumbnail==='hide_thumbnail'||$thumbnail==='thumbnail');

					$arr[$time]=[$no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya];
					++$i;
					if($i>=$max_search){break 2;}//1掲示板あたりの最大検索数
				}
				
			}
		}
		fclose($cp);
		if($j>=5000){break;}//1掲示板あたりの最大行数
		++$j;
	}
	fclose($fp);

	krsort($arr);

	//検索結果の出力
	$j=0;
	$out=[];
	if(!empty($arr)){
	//ページ番号から1ページ分のスレッド分とりだす
	$articles=array_slice($arr,(int)$page,$pagedef,false);
	$articles = array_values($articles);//php5.6 32bit 対応
	foreach($articles as $i => $line){

			$out[$i] = create_res($line,['catalog'=>true]);//$lineから、情報を取り出す

			// マークダウン
			$com= preg_replace("{\[([^\[\]\(\)]+?)\]\((https?://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)\)}","\\1",$out[$i]['com']);
			$com=h(strip_tags($com));
			$com=mb_strcut($com,0,180);
			$out[$i]['com']=$com;
			$out[$i]['date']=$out[$i]['datetime'] ? (date("y/m/d G:i", $out[$i]['datetime'])) : '';

			$j=$page+$i+1;//表示件数
		}
	}

	if($imgsearch){
		$img_or_com=$en ? 'Images' : 'イラスト';
		$mai_or_ken=$en ? ' ' : '枚';
	}
	else{
		$img_or_com=$en ? 'Comments' : 'コメント';
		$mai_or_ken=$en ? ' ' : '件';
	}
	$imgsearch= (bool)$imgsearch;

	//ラジオボタンのチェック
	$radio_chk1=false;//作者名
	$radio_chk2=false;//完全一致
	$radio_chk3=false;//本文題名	
	if($q!==''&&$radio===3){//本文題名
		$radio_chk3=true;
	}
	elseif($q!==''&&$radio===2){//完全一致
		$radio_chk2=true;	
	}
	elseif($q!==''&&($radio===0||$radio===1)){//作者名
		$radio_chk1=true;
	}
	else{//作者名	
		$radio_chk1=true;
	}

	$page=(int)$page;
	$en_q=h(urlencode($q));
	$q=h($q);

	$pageno=0;
	if($j&&$page>=2){
		$pageno = ($page+1).'-'.$j.$mai_or_ken;
	}
	else{
		$pageno = $j.$mai_or_ken;
	}
	if($q!==''&&$radio===3){
		$result_subject=($en ? $img_or_com.' of '.$q : $q."の");//h2タグに入る
	}
	elseif($q!==''){
		$result_subject=$en ? 'Posts by '.$q : $q.'さんの';
	}
	else{
		$result_subject=$en ? 'Recent '.$pageno.' Posts' : $boardname.'に投稿された最新の';
		$pageno=$en ? '':$pageno;
	}

	//ページング

	$nextpage=$page+$pagedef;//次ページ
	$prevpage=$page-$pagedef;//前のページ
	$countarr=count($arr);//配列の数
	$prev=false;
	$next=false;

	//
	$countarr=count($arr);//配列の数

	//ページング
	$start_page=$page-$pagedef*8;
	$end_page=$page+($pagedef*8);
	if($page<$pagedef*17){
		$start_page=0;
		$end_page=$pagedef*17;
	}
	//prev next 
	$next=(($page+$pagedef)<$countarr) ? $page+$pagedef : false;//ページ番号がmaxを超える時はnextのリンクを出さない
	$prev=((int)$page<=0) ? false : ($page-$pagedef) ;//ページ番号が0の時はprevのリンクを出さない

	//最終更新日時を取得
	$postedtime='';
	$lastmodified='';
	if(!empty($arr)){
		
		$time= key($arr);
		$postedtime=(strlen($time)>15) ? substr($time,0,-6) : substr($time,0,-3);
		$lastmodified=date("Y/m/d G:i", (int)$postedtime);
	}

	unset($arr);
	unset($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya);

	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);
	
	//HTML出力
	$templete='search.html';
	return include __DIR__.'/'.$skindir.$templete;
}
//カタログ表示
function catalog($page=0,$q=''){
	global $use_aikotoba,$home,$catalog_pagedef,$skindir,$display_link_back_to_home;
	global $boardname,$petit_ver,$petit_lot,$set_nsfw,$en; 

	aikotoba_required_to_view();

	$pagedef=$catalog_pagedef;

	$q=(string)filter_input(INPUT_GET,'q');

	$fp=fopen(LOG_DIR."alllog.log","r");
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		if(!trim($_line)){
			continue;
		}
		$alllog_arr[]=$_line;	
	}
	fclose($fp);

	$encoded_q='';
	$result=[];
	$j=0;
	if($q){//名前検索の時
		foreach($alllog_arr as $i => $alllog){
			if(!trim($alllog)){
				continue;
			}
			list($no,)=explode("\t",trim($alllog));

			//個別スレッドのループ
			if(!is_file(LOG_DIR."{$no}.log")){
				continue;	
			}
			check_open_no($no);
			$cp=fopen('log/'."{$no}.log","r");
			while($r_line=fgets($cp)){
				if(!trim($r_line)){
					continue;
				}
				list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($r_line));
				if ($imgfile&&$name===$q){
					$result[$time]=[$no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya];
					++$j;
					if($j>120){
						break 2;
					}
				}
			}
			fclose($cp);	
			if($i>300){
				break;
			}
		}
		krsort($result);
		$alllog_arr=$result;
		$encoded_q=urlencode($q);
	}
	$count_alllog=count($alllog_arr);

	//ページ番号から1ページ分のスレッド分とりだす
	$articles=array_slice($alllog_arr,(int)$page,$pagedef,false);
	//oyaのループ
	foreach($articles as $oya => $line){
		$out[$oya]=[];
		if(!$q){//検索結果は分割ずみ
			$line=explode("\t",trim($line));
		}
		list($_no)=$line;
		if(!is_file(LOG_DIR."{$_no}.log")){
		continue;
		}	
		$out[$oya][] = create_res($line,['catalog'=>true]);//$lineから、情報を取り出す
		if(empty($out[$oya])){
			unset($out[$oya]);
		}

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
	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);
	//token
	$token=get_csrf_token();

	//ページング
	$start_page=$page-$pagedef*8;
	$end_page=$page+($pagedef*8);
	if($page<$pagedef*17){
		$start_page=0;
		$end_page=$pagedef*17;
	}
	//prev next 
	$next=(($page+$pagedef)<$count_alllog) ? $page+$pagedef : false;//ページ番号がmaxを超える時はnextのリンクを出さない
	$prev=((int)$page!==0) ? ($page-$pagedef) : false;//ページ番号が0の時はprevのリンクを出さない

	// HTML出力
	$templete='catalog.html';
	return include __DIR__.'/'.$skindir.$templete;

}

//通常表示
function view($page=0){
	global $use_aikotoba,$use_upload,$home,$pagedef,$dispres,$allow_comments_only,$use_top_form,$skindir,$descriptions,$max_kb;
	global $boardname,$max_res,$pmax_w,$pmax_h,$use_miniform,$use_diary,$petit_ver,$petit_lot,$set_nsfw,$use_sns_button,$deny_all_posts,$en,$mark_sensitive_image,$only_admin_can_reply; 
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$display_link_back_to_home;

	aikotoba_required_to_view();

	$max_byte = $max_kb * 1024*2;
	$denny_all_posts=$deny_all_posts;//互換性
	$allow_coments_only=$allow_comments_only;//互換性

	$fp=fopen(LOG_DIR."alllog.log","r");
	$alllog_arr=[];
	while ($_line = fgets($fp)) {
		if(!trim($_line)){
			continue;
		}
		$alllog_arr[]=$_line;	
	}
	fclose($fp);
	$count_alllog=count($alllog_arr);


	//ページ番号から1ページ分のスレッドをとりだす
	$articles=array_slice($alllog_arr,(int)$page,$pagedef,false);
	//oyaのループ
	foreach($articles as $oya => $alllog){

		list($no)=explode("\t",trim($alllog));
		//個別スレッドのループ
		if(!is_file(LOG_DIR."{$no}.log")){
			continue;	
		}
		$_res=[];
		$out[$oya]=[];
		check_open_no($no);
		$rp = fopen(LOG_DIR."{$no}.log", "r");//個別スレッドのログを開く
			$s=0;
			while ($line = fgets($rp)) {
				if(!trim($line)){
					continue;
				}
				$_res = create_res(explode("\t",trim($line)));//$lineから、情報を取り出す
				$out[$oya][]=$_res;
			}	
		fclose($rp);
		if(empty($out[$oya])||$out[$oya][0]['oya']!=='oya'){
			unset($out[$oya]);
		}
	}

	// 禁止ホスト
	$is_badhost=is_badhost();
	//管理者判定処理
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();
	$resform = ((!$deny_all_posts && !$only_admin_can_reply && !$use_diary && !$is_badhost)||$adminpost);

	if(!$use_aikotoba){
		$aikotoba=true;
	}

	//Cookie
	$namec=h((string)filter_input(INPUT_COOKIE,'namec'));
	$pwdc=h((string)filter_input(INPUT_COOKIE,'pwdc'));
	$urlc=h((string)filter_input(INPUT_COOKIE,'urlc'));
	$appc=h((string)filter_input(INPUT_COOKIE,'appc'));
	$picwc=h((string)filter_input(INPUT_COOKIE,'picwc'));
	$pichc=h((string)filter_input(INPUT_COOKIE,'pichc'));
	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);

	//token
	$token=get_csrf_token();

	$arr_apps=app_to_use();
	$count_arr_apps=count($arr_apps);
	$use_paint=!empty($count_arr_apps);
	$select_app=($count_arr_apps>1);
	$app_to_use=($count_arr_apps===1) ? $arr_apps[0] : ''; 

	//ページング
	$start_page=$page-$pagedef*8;
	$end_page=$page+($pagedef*8);
	if($page<$pagedef*17){
		$start_page=0;
		$end_page=$pagedef*17;
	}
	//prev next 
	$next=(($page+$pagedef)<$count_alllog) ? $page+$pagedef : false;//ページ番号がmaxを超える時はnextのリンクを出さない
	$prev=((int)$page!==0) ? ($page-$pagedef) : false;//ページ番号が0の時はprevのリンクを出さない
	// HTML出力
	$templete='main.html';
	return include __DIR__.'/'.$skindir.$templete;

}
//レス画面
function res ($resno){
	global $use_aikotoba,$use_upload,$home,$skindir,$root_url,$use_res_upload,$max_kb,$mark_sensitive_image,$only_admin_can_reply;
	global $boardname,$max_res,$pmax_w,$pmax_h,$petit_ver,$petit_lot,$set_nsfw,$use_sns_button,$deny_all_posts,$sage_all,$view_other_works,$en;
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$display_link_back_to_home;

	aikotoba_required_to_view();

	$max_byte = $max_kb * 1024*2;

	$denny_all_posts=$deny_all_posts;
	$page='';
	$resno=(string)filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	if(!is_file(LOG_DIR."{$resno}.log")){
		return error($en?'Thread does not exist.':'スレッドがありません');	
	}
	$rresname = [];
	$resname = '';
	$oyaname='';
	$_res['time_left_to_close_the_thread']=false;
	$_res['descriptioncom']='';
	check_open_no($resno);
	$rp = fopen(LOG_DIR."{$resno}.log", "r");//個別スレッドのログを開く
		$out[0]=[];
		while ($line = fgets($rp)) {
			if(!trim($line)){
				continue;
			}
			$_res = create_res(explode("\t",trim($line)));//$lineから、情報を取り出す

			if($_res['oya']==='oya'){

				$_res['time_left_to_close_the_thread'] = time_left_to_close_the_thread($_res['time']);
				$_res['descriptioncom']= $_res['com'] ? h(s(mb_strcut(str_replace('"\n"'," ",$_res['com']),0,300))) : '';

				$oyaname = $_res['name'];
			} 
			// 投稿者名を配列にいれる
				if (($oyaname !== $_res['name']) && !in_array($_res['name'], $rresname)) { // 重複チェックと親投稿者除外
					$rresname[] = $_res['name'];
				}
		$out[0][]=$_res;
		}	
	fclose($rp);
	if(empty($out[0])||$out[0][0]['oya']!=='oya'){
		return error($en? 'The article does not exist.':'記事がありません。');
	}
	//投稿者名の特殊文字を全角に
	foreach($rresname as $key => $val){
		$rep=str_replace('&quot;','”',$val);
		$rep=str_replace('&#039;','’',$rep);
		$rep=str_replace('&lt;','＜',$rep);
		$rep=str_replace('&gt;','＞',$rep);
		$rresname[$key]=str_replace('&amp;','＆',$rep);
	}			

	$resname = !empty($rresname) ? implode(($en?'-san':'さん').' ',$rresname) : false; // レス投稿者一覧

	$fp=fopen(LOG_DIR."alllog.log","r");
	$articles=[];
	while ($line = fgets($fp)) {
		if(!trim($line)){
			continue;
		}
		$articles[] = $line;//$_lineから、情報を取り出す
	}
	fclose($fp);
	$i=0;
	foreach($articles as $i =>$article){//現在のスレッドのキーを取得
		if (strpos(trim($article), $resno . "\t") === 0) {
			break;
		}
	}
	$next=isset($articles[$i+1])? rtrim($articles[$i+1]) :'';
	$prev=isset($articles[$i-1])? rtrim($articles[$i-1]) :'';
	$next=$next ? (create_res(explode("\t",trim($next)),['catalog'=>true])):[];
	$prev=$prev ? (create_res(explode("\t",trim($prev)),['catalog'=>true])):[];
	$next=(!empty($next) && is_file(LOG_DIR."{$next['no']}.log"))?$next:[];
	$prev=(!empty($prev) && is_file(LOG_DIR."{$prev['no']}.log"))?$prev:[];

	if($view_other_works){
		$view_other_works=[];
		$a=[];
		$start_view=(($i-7)>=0) ? ($i-7) : 0;
		$other_articles=array_slice($articles,$start_view,17,false);
		foreach($other_articles as $val){
			$b=create_res(explode("\t",trim($val)),['catalog'=>true]);
			if(!empty($b)&&$b['img']&&$b['no']!==$resno){
				$a[]=$b;
			}
		}
		$c=($i<5) ? 0 : (count($a)>9 ? 4 :0);
		$view_other_works=array_slice($a,$c,6,false);
	}

	//禁止ホスト
	$is_badhost=is_badhost();
	//管理者判定処理
	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();
	$resform = ((!$deny_all_posts && !$only_admin_can_reply && !$is_badhost)||$adminpost);
	if(!$use_aikotoba){
		$aikotoba=true;
	}

	//Cookie
	$namec=h((string)filter_input(INPUT_COOKIE,'namec'));
	$pwdc=h((string)filter_input(INPUT_COOKIE,'pwdc'));
	$urlc=h((string)filter_input(INPUT_COOKIE,'urlc'));
	$appc=h((string)filter_input(INPUT_COOKIE,'appc'));
	$picwc=h((string)filter_input(INPUT_COOKIE,'picwc'));
	$pichc=h((string)filter_input(INPUT_COOKIE,'pichc'));
	$nsfwc=(bool)filter_input(INPUT_COOKIE,'nsfwc',FILTER_VALIDATE_BOOLEAN);

	$arr_apps=app_to_use();
	$count_arr_apps=count($arr_apps);
	$use_paint=!empty($count_arr_apps);
	$select_app=($count_arr_apps>1);
	$app_to_use=($count_arr_apps===1) ? $arr_apps[0] : ''; 

	//token
	$token=get_csrf_token();
	$templete='res.html';
	return include __DIR__.'/'.$skindir.$templete;

}
