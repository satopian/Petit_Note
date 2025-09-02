<?php
//Petit Note (c)さとぴあ @satopian 2021-2025 MIT License
//https://paintbbs.sakura.ne.jp/
//1スレッド1ログファイル形式のスレッド式画像掲示板

$petit_ver='v1.110.5';
$petit_lot='lot.20250902';

$lang = ($http_langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')
  ? explode( ',', $http_langs )[0] : '';
$en= (stripos($lang,'ja')!==0);

if (version_compare(PHP_VERSION, '7.3.0', '<')) {
	die($en? "Error. PHP version 7.3.0 or higher is required for this program to work. <br>\n(Current PHP version:".PHP_VERSION.")":
		"エラー。本プログラムの動作には PHPバージョン 7.3.0 以上が必要です。<br>\n(現在のPHPバージョン：".PHP_VERSION.")"
	);
}

if(!is_file(__DIR__.'/functions.php')){
	die(__DIR__.'/functions.php'.($en ? ' does not exist.':'がありません。'));
}
require_once(__DIR__.'/functions.php');
if(!isset($functions_ver)||$functions_ver<20250821){
	die($en?'Please update functions.php to the latest version.':'functions.phpを最新版に更新してください。');
}

check_file(__DIR__.'/misskey_note.inc.php');
require_once(__DIR__.'/misskey_note.inc.php');
if(!isset($misskey_note_ver)||$misskey_note_ver<20250718){
	die($en?'Please update misskey_note.inc.php to the latest version.':'misskey_note.inc.phpを最新版に更新してください。');
}

check_file(__DIR__.'/save.inc.php');
require_once(__DIR__.'/save.inc.php');
if(!isset($save_inc_ver)||$save_inc_ver<20250707){
	die($en?'Please update save.inc.php to the latest version.':'save.inc.phpを最新版に更新してください。');
}

check_file(__DIR__.'/search.inc.php');
require_once(__DIR__.'/search.inc.php');
if(!isset($search_inc_ver)||$search_inc_ver<20250619){
	die($en?'Please update search.inc.php to the latest version.':'search.inc.phpを最新版に更新してください。');
}

check_file(__DIR__.'/thumbnail_gd.inc.php');
require_once(__DIR__.'/thumbnail_gd.inc.php');
if(!isset($thumbnail_gd_ver)||$thumbnail_gd_ver<20250707){
	error($en?'Please update thumbmail_gd.inc.php to the latest version.':'thumbnail_gd.inc.phpを最新版に更新してください。');
}

check_file(__DIR__.'/noticemail.inc.php');
require_once(__DIR__.'/noticemail.inc.php');
if(!isset($noticemail_inc_ver)||$noticemail_inc_ver<20250315){
	error($en?'Please update noticemail.inc.php to the latest version.':'noticemail.inc.phpを最新版に更新してください。');
}

check_file(__DIR__.'/config.php');
require_once(__DIR__.'/config.php');
// jQueryバージョン
const JQUERY='jquery-3.7.0.min.js';
check_file(__DIR__.'/lib/'.JQUERY);
// luminous
check_file(__DIR__.'/lib/lightbox/js/lightbox.min.js');
check_file(__DIR__.'/lib/lightbox/css/lightbox.min.css');

//テンプレート
$skindir='template/'.$skindir;

if(!$max_log){
	error($en?'The maximum number of threads has not been set.':'最大スレッド数が設定されていません。');
}
if(!isset($admin_pass)||!$admin_pass){
	error($en?'The administrator password has not been set.':'管理者パスワードが設定されていません。');
}
$max_log=($max_log<500) ? 500 : $max_log;//最低500スレッド
$max_com= $max_com ?? 1000;
$sage_all= $sage_all ?? false;
$view_other_works= $view_other_works ?? true;
$deny_all_posts= $deny_all_posts ?? ($denny_all_posts ?? false);
$allow_comments_only = $allow_comments_only ?? ($allow_coments_only ?? false); 
$dispres = $dispres ?? ($display ?? 5); 
$disp_image_res = $disp_image_res ?? 0;//0ですべて表示
$latest_var= $latest_var ?? true;
$badhost= $badhost ?? []; 
$set_all_images_to_nsfw = $set_all_images_to_nsfw ?? false ; 
$mark_sensitive_image = $mark_sensitive_image ?? false; 
$mark_sensitive_image = $set_all_images_to_nsfw ? false : $mark_sensitive_image;
$only_admin_can_reply = $only_admin_can_reply ?? false;
$check_password_input_error_count = $check_password_input_error_count ?? false;
$aikotoba_required_to_view= $aikotoba_required_to_view ?? false;
$keep_aikotoba_login_status= $keep_aikotoba_login_status ?? false;
$use_paintbbs_neo= $use_paintbbs_neo ?? true;
$use_chickenpaint= $use_chickenpaint ?? true;
$max_file_size_in_png_format_paint = $max_file_size_in_png_format_paint ?? 1024;
$max_file_size_in_png_format_upload = $max_file_size_in_png_format_upload ?? 800;
$use_klecs= $use_klecs ?? true;
$use_tegaki= $use_tegaki ?? true;
$use_axnos= $use_axnos ?? true;
$display_link_back_to_home = $display_link_back_to_home ?? true;
$password_require_to_continue = $password_require_to_continue ?? false;
$subject_input_required = $subject_input_required ?? false;
$comment_input_required = $comment_input_required ?? false;
$display_search_nav = $display_search_nav ?? false;
$switch_sns = $switch_sns ?? true;
$sns_window_width = $sns_window_width ?? 600;
$sns_window_height = $sns_window_height ?? 600;
$use_misskey_note = $use_misskey_note ?? true;
$sort_comments_by_newest = $sort_comments_by_newest ?? false;
$pmin_w = $pmin_w ?? 300;//幅
$pmin_h = $pmin_h ?? 300;//高さ
$pdef_w = $pdef_w ?? 300;//幅
$pdef_h = $pdef_h ?? 300;//高さ
$step_of_canvas_size = $step_of_canvas_size ?? 50;
$use_url_input_field = $use_url_input_field ?? true;
$max_px = $max_px ?? 1024;
$nsfw_checked = $nsfw_checked ?? true;
$use_darkmode = $use_darkmode ?? true;
$darkmode_by_default = $darkmode_by_default ?? false;
$sitename = $sitename ?? '';
$fetch_articles_to_skip = $fetch_articles_to_skip ?? true;
$mode = (string)filter_input_data('POST','mode');
$mode = $mode ? $mode :(string)filter_input_data('GET','mode');
$resno=(int)filter_input_data('GET','resno',FILTER_VALIDATE_INT);
$httpsonly = (bool)($_SERVER['HTTPS'] ?? '');
//user-codeの発行
$usercode = t(filter_input_data('COOKIE', 'usercode'));//user-codeを取得

session_sta();
$session_usercode = $_SESSION['usercode'] ?? "";
$session_usercode = t($session_usercode);

$usercode = $usercode ? $usercode : $session_usercode;
if(!$usercode){//user-codeがなければ発行
	$userip = get_uip();
	$usercode = hash('sha256', $userip.random_bytes(16));
}
setcookie("usercode", $usercode, time()+(86400*365),"","",$httpsonly,true);//1年間
$_SESSION['usercode']=$usercode;

$x_frame_options_deny = $x_frame_options_deny ?? true;
if($x_frame_options_deny){
	header("Content-Security-Policy: frame-ancestors 'none';");
}
//ダークモード
if(!isset($_COOKIE["p_n_set_darkmode"])&&$darkmode_by_default){
	setcookie("p_n_set_darkmode","1",time()+(60*60*24*180),"","",$httpsonly,true);
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
		$type = (string)filter_input_data('POST', 'type');
		if($type==='rep'||$password_require_to_continue){
			check_cont_pass();
		} 
		return paint();
	case 'set_app_select_enabled_session':
		return set_app_select_enabled_session();
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
	case 'age_check':
		return age_check();
	case 'view_nsfw':
		return view_nsfw();
	case 'set_nsfw_show_hide':
		return set_nsfw_show_hide();
	case 'set_darkmode':
		return set_darkmode();
	case 'logout_admin':
		return logout_admin();
	case 'logout':
		return logout();
	case 'set_share_server':
		return set_share_server();
	case 'post_share_server':
		return post_share_server();
	case 'before_misskey_note':
		return misskey_note::before_misskey_note();
	case 'misskey_note_edit_form':
		return misskey_note::misskey_note_edit_form();
	case 'create_misskey_note_sessiondata':
		return misskey_note::create_misskey_note_sessiondata();
	case 'create_misskey_authrequesturl':
		return misskey_note::create_misskey_authrequesturl();
	case 'misskey_success':
		return misskey_note::misskey_success();
	case 'saveimage':
		return saveimage();
	case 'search':
		return processsearch::search();
	case 'catalog':
		return catalog();
	case 'download':
		return download_app_dat();
	case '':
		if($resno){
			return res();
		}
		return view();
	default:
		return view();
}

//投稿処理
function post(): void {
	global $max_log,$max_res,$use_upload,$use_res_upload,$use_diary,$max_w,$max_h,$mark_sensitive_image;
	global $allow_comments_only,$res_max_w,$res_max_h,$name_input_required,$max_com,$max_px,$sage_all,$en,$only_admin_can_reply;
	global $usercode,$use_url_input_field,$httpsonly;

	//投稿間隔をチェック
	check_submission_interval();
	//Fetch API以外からのPOSTを拒否
	check_post_via_javascript();
	check_csrf_token();
	//NGワードがあれば拒絶
	Reject_if_NGword_exists_in_the_post();
	//POSTされた内容を取得
	$userip =t(get_uip());
	//ホスト取得
	$host = $userip ? t(gethostbyaddr($userip)) : '';

	$sub = t(filter_input_data('POST','sub'));
	$name = t(filter_input_data('POST','name'));
	$com = t(filter_input_data('POST','com'));
	$resto = t(filter_input_data('POST','resto',FILTER_VALIDATE_INT));
	$pwd=t(filter_input_data('POST', 'pwd'));//パスワードを取得
	$sage = $sage_all ? true : (bool)filter_input_data('POST','sage',FILTER_VALIDATE_BOOLEAN);
	$hide_thumbnail = $mark_sensitive_image ? (bool)filter_input_data('POST','hide_thumbnail',FILTER_VALIDATE_BOOLEAN) : false;
	$hide_animation=(bool)filter_input_data('POST','hide_animation',FILTER_VALIDATE_BOOLEAN);
	$check_elapsed_days=false;

	$url = t(filter_input_data('POST','url',FILTER_VALIDATE_URL));
	$url= (adminpost_valid() || $use_url_input_field) ? $url : '';

	$pwd=$pwd ? $pwd : t(filter_input_data('COOKIE','pwdc'));//未入力ならCookieのパスワード
	if(!$pwd){//それでも$pwdが空なら
		$pwd = substr(hash('sha256', random_bytes(16)), 0, 15);
	}
	if(strlen($pwd) < 6) error($en? 'The password is too short. At least 6 characters.':'パスワードが短すぎます。最低6文字。');

	$upfile='';
	$imgfile='';
	$w='';
	$h='';
	$tool='';

	$time=create_post_time();//ファイル名が重複しない投稿時刻を作成
	$adminpost=(adminpost_valid()|| is_adminpass($pwd));

	//お絵かきアップロード
	$pictmp = (int)filter_input_data('POST', 'pictmp',FILTER_VALIDATE_INT);
	$painttime ='';
	$is_painted_img=false;
	$tempfile='';
	$picfile='';
	if($pictmp===2){//ユーザーデータを調べる
		list($picfile,) = explode(",",(string)filter_input_data('POST', 'picfile'));
		$picfile=basename($picfile);
		$tempfile = TEMP_DIR.$picfile;
		$picfile=pathinfo($tempfile, PATHINFO_FILENAME );//拡張子除去
		//選択された絵が投稿者の絵か再チェック
		if (!$picfile || !is_file(TEMP_DIR.$picfile.".dat") || !is_file($tempfile)) {
			error($en? 'Posting failed.':'投稿に失敗しました。');
		}
		//ユーザーデータから情報を取り出す
		$userdata = file_get_contents(TEMP_DIR.$picfile.".dat");
		list($uip,$uhost,,,$ucode,,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");
		//ユーザーコードまたはipアドレスは一致しているか?
		$valid_poster_found = ($ucode && ($ucode == $usercode)) || ($uip && ($uip == $userip)); 
		if(!$valid_poster_found){//正しい投稿者が見つからなかった時は
			error($en? 'Posting failed.':'投稿に失敗しました。');
		}
		$tool= is_paint_tool_name($tool);
		$uresto = (string)filter_var($uresto,FILTER_VALIDATE_INT);
		$hide_animation= $hide_animation ? true : ($u_hide_animation==='true');
		$resto = $uresto ? $uresto : $resto;//変数上書き$userdataのレス先を優先する
		$resto=(string)$resto;//(string)厳密な型
		//描画時間を$userdataをもとに計算
		$hide_painttime=(bool)filter_input_data('POST','hide_painttime',FILTER_VALIDATE_BOOLEAN);
		if(!$hide_painttime && $starttime && ctype_digit($starttime) && $postedtime && ctype_digit($postedtime)){
			$painttime=(int)$postedtime-(int)$starttime;
		}
		if($resto && !$use_res_upload && !$adminpost){
			error($en? 'Only administrator can post.':'投稿できるのは管理者だけです。');
		}
		$is_painted_img=true;//お絵かきでエラーがなかった時にtrue;
	}

	if(!$resto && $use_diary && !$adminpost){
		error($en? 'Only administrator can post.':'投稿できるのは管理者だけです。');
	}
	if($resto && $only_admin_can_reply && !$adminpost){
		error($en?'Only administrator can reply.':'返信できるのは管理者だけです。');
	}

	if($resto && !is_file(LOG_DIR."{$resto}.log")){//エラー処理
		if(!$is_painted_img){//お絵かきではない時は
			error($en? 'The article does not exist.':'記事がありません。');
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
		chmod(LOG_DIR."{$resto}.log",0600);
		$rp=fopen(LOG_DIR."{$resto}.log","r+");
		file_lock($rp, LOCK_EX);
		$r_arr = create_array_from_fp($rp);
		if(empty($r_arr)){
			closeFile($rp);
			if(!$is_painted_img){
				error($en?'This operation has failed.':'失敗しました。');
			}
			$chk_resto=$resto;
			$resto = '';
		}

		list($r_no,$oyasub,$n_,$v_,$c_,$u_,$img_,$_,$_,$thumb_,$pt_,$hash_,$to_,$pch_,$postedtime,$fp_time_,$h_,$uid_,$h_,$r_oya)=explode("\t",trim($r_arr[0]));
		//レスファイルの1行目のチェック。経過日数、ログの1行目が'oya'かどうか確認。
		$check_elapsed_days = check_elapsed_days($postedtime);
		$count_r_arr=count($r_arr);

		//レス先のログファイルを再確認
		if($resto && ($r_no!==$resto || $r_oya!=='oya')){
			if(!$is_painted_img){
				error($en? 'The article does not exist.':'記事がありません。');
			}
			$chk_resto=$resto;
			$resto='';
		}
		if($is_painted_img){//お絵かきの時は新規投稿にする
			//お絵かきの時に日数を経過していたら新規投稿。
			//お絵かきの時に最大レス数を超過していたら新規投稿。
			if($resto && !$adminpost && (!$check_elapsed_days || $count_r_arr>$max_res)){
				$chk_resto=$resto;
				$resto='';
			}
		}
		//お絵かき以外。
		if($resto && !$adminpost && !$check_elapsed_days){//指定した日数より古いスレッドには投稿できない。
			error($en? 'This thread is too old to post.':'このスレッドには投稿できません。');
		}
		if($resto && !$adminpost &&  $count_r_arr>$max_res){//最大レス数超過。
			error($en?'The maximum number of replies has been exceeded.':'最大レス数を超過しています。');
		}

		$sub='Re: '.$oyasub;

	}

	//POSTされた値をログファイルに格納する書式にフォーマット
	$formatted_post=create_formatted_text_from_post($name,$sub,$url,$com);
	$name = $formatted_post['name'];
	$sub = $formatted_post['sub'];
	$url = $formatted_post['url'];
	$com = $formatted_post['com'];

	//ファイルアップロード
	$up_tempfile = $_FILES['imgfile']['tmp_name'] ?? ''; // 一時ファイル名
	if(isset($_FILES['imgfile']['error']) && in_array($_FILES['imgfile']['error'],[1,2])){//容量オーバー
		error($en? "The file is too large." : "ファイルサイズが大きすぎます。");
	} 
	$is_upload_img=false;
	if ($up_tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK && ($use_upload || $adminpost)){

		if($resto && !$use_res_upload && !$adminpost){
			safe_unlink($up_tempfile);
			error($en? 'You are not logged in in diary mode.':'日記にログインしていません。');
		}
		if (!get_image_type($up_tempfile)) {//対応フォーマットではなかった時
			safe_unlink($up_tempfile);
			error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
		}
		$upfile=TEMP_DIR.$time.'.tmp';
		$move_uploaded = move_uploaded_file($up_tempfile,$upfile);
		if(!$move_uploaded){//アップロードは成功した?
			safe_unlink($up_tempfile);
			error($en?'This operation has failed.':'失敗しました。');
		}
		//Exifをチェックして画像が回転している時と位置情報が付いている時は上書き保存
		check_jpeg_exif($upfile);
		if(!is_file($upfile)){
			error($en?'This operation has failed.':'失敗しました。');
		}

		$tool = 'upload'; 
		$is_upload_img=true;
		$is_painted_img=false;
	}
	//お絵かきアップロード
	if($is_painted_img && is_file($tempfile)){

		$upfile=TEMP_DIR.$time.'.tmp';
			copy($tempfile, $upfile);
			chmod($upfile,0606);
	}
	$is_file_upfile=false;
	if($is_upload_img||$is_painted_img){
		if(!is_file($upfile)){
			error($en?'This operation has failed.':'失敗しました。');
		}
		$is_file_upfile=true;
	}

	if(!$is_file_upfile&&!$com){
		error($en?'Please write something.':'何か書いて下さい。');
	}

	if(!$resto && !$allow_comments_only && !$is_file_upfile && !$adminpost){
		error($en?'Please attach an image.':'画像を添付してください。');
	}

	$hash = $pwd ? password_hash($pwd,PASSWORD_BCRYPT,['cost' => 5]) : '';

	setcookie("namec",$name,time()+(60*60*24*30),"","",$httpsonly,true);
	setcookie("urlc",$url,time()+(60*60*24*30),"","",$httpsonly,true);
	setcookie("pwdc",$pwd,time()+(60*60*24*30),"","",$httpsonly,true);


	//ユーザーid
	$userid = t(getId($userip));//sessionも確認
	$_SESSION['userid'] = $userid;

	$verified = $adminpost ? 'adminpost' : ''; 

	//全体ログを開く
	chmod(LOG_DIR."alllog.log",0600);
	$fp=fopen(LOG_DIR."alllog.log","r+");
	if(!$fp){
		safe_unlink($upfile);
		error($en?'This operation has failed.':'失敗しました。');
	}
	file_lock($fp, LOCK_EX);

	$alllog_arr = create_array_from_fp($fp);
	if($resto){//投稿数が0の時には空になるため、レス時のみチェック
		if(empty($alllog_arr)){
			closeFile($fp);
			safe_unlink($upfile);
			error($en?'This operation has failed.':'失敗しました。');
		}
	}

	//チェックするスレッド数。画像ありなら15、コメントのみなら5 
	$n= $is_file_upfile ? 15 : 5;
	$chk_log_arr=array_slice($alllog_arr,0,$n,false);
	$chk_resto=$chk_resto ? $chk_resto : $resto; 
	//$n行分の全体ログをもとにスレッドのログファイルを開いて配列を作成
	$_chk_lines = create_chk_lins($chk_log_arr,$chk_resto);//取得済みの$chk_restoの配列を除外
	$chk_lines=array_merge($_chk_lines,$r_arr);

	$chk_com=[];
	$chk_images=[];
	$m2time=microtime2time($time);
	foreach($chk_lines as $chk_line){
		$chk_ex_line=explode("\t",trim($chk_line));
		list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_img_hash_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=$chk_ex_line;
		if($m2time===microtime2time($time_)){//投稿時刻の重複回避
			safe_unlink($upfile);
			closeFile($fp);
			closeFile($rp);
			error($en? 'Please wait a little.':'少し待ってください。');
		}
		if($userid === $userid_){
			$chk_com[$time_]=$chk_ex_line;//コメント
		}
		if($is_file_upfile && $imgfile_){
			$chk_images[$time_]=$chk_ex_line;//画像
		}
	}

	krsort($chk_com);
	$chk_com=array_slice($chk_com,0,20,false);

	foreach($chk_com as $line){
		list($_no_,$_sub_,$_name_,$_verified_,$_com_,$_url_,$_imgfile_,$_w_,$_h_,$_thumbnail_,$_painttime_,$_log_img_hash_,$_tool_,$_pchext_,$_time_,$_first_posted_time_,$_host_,$_userid_,$_hash_,$_oya_)=$line;

		if(!$adminpost && ($com && ($com === $_com_))){
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			error($en?'Post once by this comment.':'同じコメントがありました。');
		}

		// 画像アップロードと画像なしそれぞれの待機時間
		$interval=(int)time()-(int)microtime2time($_time_);
		if($interval>=0 && (($upfile && $interval<30)||(!$upfile && $interval<20))){//待機時間がマイナスの時は通す
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			error($en? 'Please wait a little.':'少し待ってください。');
		}
	}

	$up_img_hash='';
	if($is_file_upfile){

		if($is_upload_img){//実体データの縮小
			thumbnail_gd::thumb(TEMP_DIR,$time.'.tmp',$time,$max_px,$max_px,['toolarge'=>true]);
		}	
		//サイズオーバの時に変換したwebpのほうがファイル容量が小さくなっていたら元のファイルを上書き
		convert_andsave_if_smaller_png2webp($is_upload_img,$time.'.tmp',$time);
		if($is_upload_img){//アップロード画像のファイルサイズが大きすぎる時は削除
			delete_file_if_sizeexceeds($upfile,$fp,$rp);
		}

		$ext=get_image_type($upfile);
		if (!$ext) {
			closeFile($fp);
			closeFile($rp);
			safe_unlink($upfile);
			error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
		}

		//同じ画像チェック アップロード画像のみチェックしてお絵かきはチェックしない
		$up_img_hash=substr(hash_file('sha256', $upfile), 0, 32);

		if($is_upload_img){
			foreach($chk_images as $line){
				list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_img_hash,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=$line;
				if(!adminpost_valid() && ($log_img_hash && ($log_img_hash === $up_img_hash))){
					closeFile($fp);
					closeFile($rp);
					safe_unlink($upfile);
					error($en?'Image already exists.':'同じ画像がありました。');
				}
			}
		}
		$imgfile=$time.$ext;

		rename($upfile,IMG_DIR.$imgfile);
		if(!is_file(IMG_DIR.$imgfile)){
			error($en?'This operation has failed.':'失敗しました。');
		}
	}

	$src='';
	$pchext = '';
	//PCHファイルアップロード
	// .pch, .spch,.chi,.psd ブランク どれかが返ってくる
	if ($is_painted_img && $imgfile && ($pchext = check_pch_ext(TEMP_DIR.$picfile,['upload'=>true]))) {

		$src = TEMP_DIR.$picfile.$pchext;
		$dst = IMG_DIR.$time.$pchext;
			if(copy($src, $dst)){
				chmod($dst,0606);
		}
	}
	$pchext= ($pchext==='.pch' && $hide_animation) ? 'hide_animation' : $pchext; 
	$pchext= ($pchext==='.tgkr' && $hide_animation) ? 'hide_tgkr' : $pchext; 
	
	$thumbnail='';
	if($imgfile && is_file(IMG_DIR.$imgfile)){
		
		list($w,$h)=getimagesize(IMG_DIR.$imgfile);

		$max_w = $resto ? $res_max_w : $max_w; 
		$max_h = $resto ? $res_max_h : $max_h; 
		//縮小表示
		list($w,$h)=image_reduction_display($w,$h,$max_w,$max_h);
		//サムネイル作成
		$thumbnail = make_thumbnail($imgfile,$time,$max_w,$max_h);
		$hide_thumbnail=$hide_thumbnail ? 'hide_' : '';
		$thumbnail =  $hide_thumbnail.$thumbnail;

	}
	//ログの番号の最大値
	$no_arr = [];
	foreach($alllog_arr as $i => $_alllog){
		list($log_no,)=explode("\t",$_alllog,2);
		if(!ctype_digit($log_no)){
			error($en?'This operation has failed.':'失敗しました。');
		}
		$no_arr[]=$log_no;
	}

	$max_no = (!empty($no_arr)) ? max($no_arr) : 0; 

	//書き込むログの書式
	$line='';
	$newline='';
	$r_line='';
	$new_r_line='';
	if($resto){//レスの時はスレッド別ログに
		$r_oya='';
		$r_no='';
		if(empty($r_arr)){
			closeFile($fp);
			closeFile($rp);
			safe_unlink(IMG_DIR.$imgfile);
			error($en?'This operation has failed.':'失敗しました。');
		}
		//レス先はoya?
		list($r_no,,,,,,,,,,,,,,,,,,,$r_oya)=explode("\t",trim($r_arr[0]));
		if($r_no!==$resto||$r_oya!=='oya'){
			closeFile($fp);
			closeFile($rp);
			safe_unlink(IMG_DIR.$imgfile);
			error($en? 'The article does not exist.':'記事がありません。');
		}

		$r_line = "$resto\t$sub\t$name\t$verified\t$com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$up_img_hash\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\tres\n";
		$new_rline=	implode("",$r_arr).$r_line;

		writeFile($rp,$new_rline);
		closeFile($rp);

		if(!$sage){
			foreach($alllog_arr as $i =>$val){
				if (strpos(trim($val), $resto . "\t") === 0) {//全体ログで$noが一致したら
					list($_no)=explode("\t",$val,2);
					if($resto==$_no){
						$newline = $val;//レスが付いたスレッドを$newlineに保存。あとから全体ログの先頭に追加して上げる
						unset($alllog_arr[$i]);//レスが付いたスレッドを全体ログからいったん削除
						break;
					}
				}
			}	
		}

	}else{
		//最後の記事ナンバーに+1
		$no=$max_no+1;
		//コメントを120バイトに短縮
		$strcut_com=mb_strcut($com,0,120);
		$newline = "$no\t$sub\t$name\t$verified\t$strcut_com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$up_img_hash\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\toya\n";
		$new_r_line = "$no\t$sub\t$name\t$verified\t$com\t$url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$up_img_hash\t$tool\t$pchext\t$time\t$time\t$host\t$userid\t$hash\toya\n";
		check_open_no($no);
		file_put_contents(LOG_DIR.$no.'.log',$new_r_line,LOCK_EX);//新規投稿の時は、記事ナンバーのファイルを作成して書き込む
		chmod(LOG_DIR."{$no}.log",0600);
	}

	//保存件数超過処理
	$countlog=count($alllog_arr);
	if($max_log && $countlog && ($max_log<=$countlog)){
		for($i=$max_log-1; $i<$countlog;++$i){

		if(!isset($alllog_arr[$i]) || !trim($alllog_arr[$i])){
			continue;
		}
		list($d_no,)=explode("\t",$alllog_arr[$i],2);
		if(is_file(LOG_DIR."{$d_no}.log")){
			check_open_no($d_no);
			$dp = fopen(LOG_DIR."{$d_no}.log", "r");//個別スレッドのログを開く
			file_lock($dp, LOCK_EX);

			while ($line = fgets($dp)) {
				if(!trim($line)){
					continue;
				}
				list($d_no,$_sub,$_name,$_verified,$_com,$_url,$d_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_img_hash,$_tool,$_pchext,$d_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));

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

	//ワークファイル削除
	safe_unlink($src);
	safe_unlink($tempfile);
	safe_unlink($up_tempfile);
	safe_unlink($upfile);
	safe_unlink(TEMP_DIR.$picfile.".dat");
	delete_res_cache();

	$resno = $resto ? $resto : $no;	

	global $send_email,$to_mail,$root_url,$boardname;

	if($send_email){
		//config.phpで未定義の時の初期値
		//このままでよければ定義不要
		defined('NOTICE_MAIL_NAME') or define('NOTICE_MAIL_NAME', '名前');
		defined('NOTICE_MAIL_SUBJECT') or define('NOTICE_MAIL_SUBJECT', '記事題名');
		defined('NOTICE_MAIL_IMG') or define('NOTICE_MAIL_IMG', '投稿画像');
		defined('NOTICE_MAIL_THUMBNAIL') or define('NOTICE_MAIL_THUMBNAIL', 'サムネイル画像');
		defined('NOTICE_MAIL_URL') or define('NOTICE_MAIL_URL', '記事URL');
		defined('NOTICE_MAIL_REPLY') or define('NOTICE_MAIL_REPLY', 'へのレスがありました');
		defined('NOTICE_MAIL_NEWPOST') or define('NOTICE_MAIL_NEWPOST', '新規投稿がありました');
		$data['label_name']=NOTICE_MAIL_NAME;
		$data['label_subject']=NOTICE_MAIL_SUBJECT;
		$data['to'] = $to_mail;
		$data['name'] = $name;
		$data['url'] = filter_var($url,FILTER_VALIDATE_URL) ? $url:'';
		$data['title'] = $sub;
		if($imgfile){
			$data['option'][] = [NOTICE_MAIL_IMG,$root_url.IMG_DIR.$imgfile];//拡張子があったら
		}
		if(is_file(THUMB_DIR.$time.'s.webp')){
			$data['option'][] = [NOTICE_MAIL_THUMBNAIL,$root_url.THUMB_DIR.$time.'s.webp'];
		}elseif(is_file(THUMB_DIR.$time.'s.jpg')){
			$data['option'][] = [NOTICE_MAIL_THUMBNAIL,$root_url.THUMB_DIR.$time.'s.jpg'];
		} 
		if($resto){
			$data['subject'] = '['.$boardname.'] No.'.$resto.NOTICE_MAIL_REPLY;
		}else{
			$data['subject'] = '['.$boardname.'] '.NOTICE_MAIL_NEWPOST;
		}

		$data['option'][] = [NOTICE_MAIL_URL,"{$root_url}?resno={$resno}#{$time}"];
		$data['comment'] = str_replace('"\n"',"\n",$com);

		noticemail::send($data);
	}

	//多重送信防止
	redirect("./?resno={$resno}&resid={$time}");

}
//お絵かき画面
function paint(): void {

	global $boardname,$skindir,$pmax_w,$pmax_h,$pmin_w,$pmin_h,$max_px,$en;
	global $usercode,$petit_lot,$httpsonly;

	//禁止ホストをチェック
	check_badhost();
	check_same_origin();
	
	$app = (string)filter_input_data('POST','app');
	$picw = (int)filter_input_data('POST','picw',FILTER_VALIDATE_INT);
	$pich = (int)filter_input_data('POST','pich',FILTER_VALIDATE_INT);
	$resto = t(filter_input_data('POST', 'resto',FILTER_VALIDATE_INT));
	if(strlen($resto)>1000){
		error($en?'Unknown error':'問題が発生しました。');
	}

	$picw = max($picw, $pmin_w); // 最低の幅チェック
	$pich = max($pich, $pmin_h); // 最低の高さチェック
	$picw = min($picw, $pmax_w); // 最大の幅チェック
	$pich = min($pich, $pmax_h); // 最大の高さチェック

	setcookie("appc", $app , time()+(60*60*24*30),"","",$httpsonly,true);//アプレット選択
	setcookie("picwc", $picw , time()+(60*60*24*30),"","",$httpsonly,true);//幅
	setcookie("pichc", $pich , time()+(60*60*24*30),"","",$httpsonly,true);//高さ

	$mode = (string)filter_input_data('POST', 'mode');

	$imgfile='';
	$oekaki_id='';
	$pchfile='';
	$img_chi='';
	$img_klecks='';
	$rep=false;
	$paintmode='paintcom';

	$adminpost=adminpost_valid();

	//pchファイルアップロードペイント
	if($adminpost){

		$pchfilename = $_FILES['pchup']['name'] ?? '';
		$pchfilename = basename($pchfilename);
		
		$pchtmp= $_FILES['pchup']['tmp_name'] ?? '';

		if(isset($_FILES['pchup']['error']) && in_array($_FILES['pchup']['error'],[1,2])){//容量オーバー
			error($en? 'The file size is too large.':'ファイルサイズが大きすぎます。');
		} 

		if ($pchtmp && $_FILES['pchup']['error'] === UPLOAD_ERR_OK){
	
			$time = (string)(time().substr(microtime(),2,6));
			$pchext=pathinfo($pchfilename, PATHINFO_EXTENSION);
			$pchext=strtolower($pchext);//すべて小文字に
			//拡張子チェック
			if (!in_array($pchext, ['pch','chi','psd','gif','jpg','jpeg','png','webp'])) {
				safe_unlink($pchtmp);
				error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
			}
			$pchup = TEMP_DIR.'pchup-'.$time.'-tmp.'.$pchext;//アップロードされるファイル名

			$move_uploaded = move_uploaded_file($pchtmp, $pchup);
			if(!$move_uploaded){//アップロードは成功した?
				safe_unlink($pchtmp);
				error($en?'This operation has failed.':'失敗しました。');
			
			}
			$mime_type = mime_content_type($pchup);
			if(($pchext==="pch") && ($mime_type === "application/octet-stream") && is_neo($pchup)){
			$app='neo';
				if($get_pch_size = get_pch_size($pchup)){
					list($picw,$pich)=$get_pch_size;//pchの幅と高さを取得
				}
			$pchfile = $pchup;
			} elseif(($pchext==="chi") && ($mime_type === "application/octet-stream")){
					$app='chi';
				$img_chi = $pchup;
			} elseif(($pchext==="psd") && ($mime_type === "image/vnd.adobe.photoshop")){
					$app='klecks';
				$img_klecks = $pchup;
			} elseif(in_array($pchext, ['gif','jpg','jpeg','png','webp']) && in_array($mime_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])){
				$file_name=pathinfo($pchup,PATHINFO_FILENAME);
				thumbnail_gd::thumb(TEMP_DIR,$pchup,$time,$max_px,$max_px,['toolarge'=>true]);
				list($picw,$pich) = getimagesize($pchup);
				$imgfile = $pchup;
			}else{
				safe_unlink($pchup);
				error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
			}
		}
	}
	$repcode='';
	$hide_animation=false;
	if($mode==="contpaint"){

		$imgfile = basename((string)filter_input_data('POST','imgfile'));
		$ctype = (string)filter_input_data('POST', 'ctype');
		$type = (string)filter_input_data('POST', 'type');
		$no = (string)filter_input_data('POST', 'no',FILTER_VALIDATE_INT);
		$time = basename((string)filter_input_data('POST', 'time'));
		$cont_paint_same_thread=(bool)filter_input_data('POST', 'cont_paint_same_thread',FILTER_VALIDATE_BOOLEAN);

		session_sta();
		unset ($_SESSION['enableappselect']);

		if(is_file(LOG_DIR."{$no}.log")){
			if($type!=='rep'){
				$resto = $cont_paint_same_thread ? $no : '';
			}
		}
		if(!is_file(IMG_DIR.$imgfile)){
			error($en? 'The article does not exist.':'記事がありません。');
		}
		$find=false;
		$rp=fopen(LOG_DIR."{$no}.log","r");
		while($_line=fgets($rp)){
			if(strpos($_line,"\t".$imgfile."\t")!==false){
				list($_no,,,,,,$_imgfile,,,,,,$_tool,,$_time,$_first_posted_time,)=explode("\t",trim($_line));
				if($no===$_no && $time===$_time && $imgfile === $_imgfile && $_tool !== 'upload'){
					$find=true;
					break;
				}
			}
		}
		closeFile($rp);
		if(!$find){
			error($en?'This operation has failed.':'失敗しました。');
		}

		list($picw,$pich)=getimagesize(IMG_DIR.$imgfile);//キャンバスサイズ

		$_pch_ext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);

		if($ctype=='pch'&& $_pch_ext){//動画から続き
			$pchfile = IMG_DIR.$time.$_pch_ext;
		}

		$imgfile = IMG_DIR.$imgfile;

		if($ctype=='img'){//画像から続き
			if($_pch_ext==='.chi'){
				$img_chi =IMG_DIR.$time.'.chi';
			}
			if($_pch_ext==='.psd'){
				$img_klecks =IMG_DIR.$time.'.psd';
			}
		}

		$hide_animation = (bool)filter_input_data('POST','hide_animation',FILTER_VALIDATE_BOOLEAN);
		$hide_animation = $hide_animation ? 'true' : 'false';
		if($type==='rep'){//画像差し換え
			$rep=true;
			$pwd = t(filter_input_data('POST', 'pwd'));
			$pwd=$pwd ? $pwd : t(filter_input_data('COOKIE','pwdc'));//未入力ならCookieのパスワード
			if(strlen($pwd) > 100) error($en? 'Password is too long.':'パスワードが長すぎます。');
			if($pwd){
				$pwd=basename($pwd);
				$pwd=openssl_encrypt ($pwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV);//暗号化
				$pwd=bin2hex($pwd);//16進数に
			}
			$userip = get_uip();
			$paintmode='picrep';
			$id=$time;	//テンプレートでも使用。
			$repcode = $no.'-'.$id.'-'.hash('sha256', $userip.random_bytes(16));
		}
	}

	check_AsyncRequest();//Asyncリクエストの時は処理を中断

	//AXNOS Paint用
	//画像の幅と高さが最大値を超えている時は、画像の幅と高さを優先する
	$pmax_w = max($picw, $pmax_w); // 最大幅を元画像にあわせる
	$pmax_h = max($pich, $pmax_h); // 最大高を元画像にあわせる
	$pmax_w = min($pmax_w,1800); // 1800px以上にはならない
	$pmax_h = min($pmax_h,1800); // 1800px以上にはならない

	$pmin_w = min($picw, $pmin_w); // 最小幅を元画像にあわせる
	$pmin_h = min($pich, $pmin_h); // 最小高を元画像にあわせる
	$pmin_w = max($pmin_w, 8); // 8px以下にはならない
	$pmin_h = max($pmin_h, 8); // 8px以下にはならない

	$parameter_day = date("Ymd");//JavaScriptのキャッシュ制御

	$admin_pass= null;
	//投稿可能な最大値
	$max_pch = get_upload_max_filesize();

	switch($app){
		case 'chi'://ChickenPaint
		
			$tool='chi';
			// HTML出力
			$templete='paint_chi.html';
			include __DIR__.'/'.$skindir.$templete;
			exit();

		case 'tegaki':

			$tool ='tegaki';
			$templete='paint_tegaki.html';
			include __DIR__.'/'.$skindir.$templete;
			exit();
		case 'axnos':

			$tool ='axnos';
			$templete='paint_axnos.html';
			include __DIR__.'/'.$skindir.$templete;
			exit();

		case 'klecks':

			$tool ='klecks';
			$templete='paint_klecks.html';
			include __DIR__.'/'.$skindir.$templete;
			exit();

		case 'neo'://PaintBBS NEO

			$tool='neo';
			$anime= true;//常にtrue
			$appw = $picw + 150;//NEOの幅
			$apph = $pich + 172;//NEOの高さ
			$appw = max($appw,450);//最低幅
			$apph = max($apph,560);//最低高
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
				list($pid,$pname,$pal[0],$pal[2],$pal[4],$pal[6],$pal[8],$pal[10],$pal[1],$pal[3],$pal[5],$pal[7],$pal[9],$pal[11],$pal[12],$pal[13]) = explode(",", $line);
				$arr_dynp[]=h($pname);
				$p_cnt=$i+1;
				ksort($pal);
				$arr_pal[$i] = 'Palettes['.h($p_cnt).'] = "#'.h(implode('\n#',$pal)).'";';
			}
			$palettes=$initial_palette.implode('',$arr_pal);
			$palsize = count($arr_dynp) + 1;
			$admin_pass= null;
			// HTML出力
			$templete='paint_neo.html';
			include __DIR__.'/'.$skindir.$templete;
			exit();

		default:
			error($en?'This operation has failed.':'失敗しました。');
	}

}
// お絵かきコメント 
function paintcom(): void {
	global $boardname,$home,$skindir,$sage_all,$en,$mark_sensitive_image;
	global $usercode,$petit_lot,$use_hide_painttime,$nsfw_checked;

	aikotoba_required_to_view(true);
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
			$userdata = file_get_contents(TEMP_DIR.$file);
			list($uip,$uhost,$uagent,$imgext,$ucode,,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");
			$hide_animation=($u_hide_animation==='true');
			$imgext=basename($imgext);
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			$uresto = $uresto ? 'res' :''; 
			if(is_file(TEMP_DIR.$file_name.$imgext)){ //画像があればリストに追加
				$pchext = check_pch_ext(TEMP_DIR . $file_name);
				$pchext = !$hide_animation ? $pchext : ''; 
				if(($ucode && ($ucode === $usercode))||($uip && ($uip === $userip))){
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
			$tmp_img=[
				'w'=>$w,
				'h'=>$h,
				'src' => TEMP_DIR.$tmpfile,
				'srcname' => $tmpfile,
				'slect_src_val' => $tmpfile.','.$resto.','.$pchext,
				'date' => date("Y/m/d H:i", filemtime(TEMP_DIR.$tmpfile)),
			];
			$out['tmp'][] = $tmp_img;
		}
	}
	$aikotoba = aikotoba_valid();
	//禁止ホストにはコメント入力欄を表示しない
	$aikotoba = is_badhost() ? false : $aikotoba;

	$namec=h((string)filter_input_data('COOKIE','namec'));
	$pwdc=h((string)filter_input_data('COOKIE','pwdc'));
	$urlc=h((string)filter_input_data('COOKIE','urlc',FILTER_VALIDATE_URL));

	$adminpost = adminpost_valid();
	$use_hide_painttime = $use_hide_painttime ?? false;
	$use_hide_painttime = ($adminpost || $use_hide_painttime);

	//フォームの表示時刻をセット
	set_form_display_time();

	$admin_pass= null;
	// HTML出力
	$templete='paint_com.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}

//コンティニュー前画面
function to_continue(): void {

	global $boardname,$use_diary,$set_nsfw,$skindir,$en,$password_require_to_continue;
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$use_tegaki,$use_axnos,$petit_lot,$elapsed_days,$max_res;

	$is_badhost=is_badhost();//テンプレートの互換性のため変数名が必要
	if($is_badhost){
		error($en? 'Rejected.' : '拒絶されました。');
	}

	aikotoba_required_to_view(true);

	$appc=(string)filter_input_data('COOKIE','appc');
	$pwdc=(string)filter_input_data('COOKIE','pwdc');

	$no = (string)filter_input_data('GET', 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input_data('GET', 'id');//intの範囲外

	$adminpost = adminpost_valid();
	session_sta();
	$enableappselect= $_SESSION['enableappselect'] ?? false;

	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r");
	$i=0;
	//スレッドが閉じてるかどうか
	$oya_time=0;
	$time=0;
	//記事は存在するか
	$flag = false;
	$resid = '';
	while ($line = fgets($rp)) {
		if(strpos($line,"\toya")!==false || strpos($line,"\t".$id."\t")!==false){
			list($_no,$sub,$name,$verified,$com,$url,$_imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$_pchext,$_time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($oya==="oya"){
				$oya_time=$_time;
			}
			if($id===$_time && $no===$_no && $tool!=='upload'){
				$time=$_time ? basename($_time) : '';
				$imgfile=$_imgfile ? basename($_imgfile) : '';
				$resid=$first_posted_time;
				$flag=true;
			}
		}
		++$i;
	}

	closeFile ($rp);
	//閉じていたら $res_max_over が true になる
	$res_max_over=(!$adminpost && ($i>$max_res||!check_elapsed_days($oya_time)));

	if(!$flag || !$imgfile || !is_file(IMG_DIR.$imgfile)){//画像が無い時は処理しない
		error($en? 'The article does not exist.':'記事がありません。');
	}
	if(!check_elapsed_days($time)&&!$adminpost){
		error($en? "Article older than {$elapsed_days} days cannot be edited.":"{$elapsed_days}日以上前の記事は編集できません。");
	}
	$hidethumbnail = (strpos($thumbnail,'hide_')!==false);

	$thumbnail_webp = ((strpos($thumbnail,'thumbnail_webp')!==false)) ? $time.'s.webp' : false; 
	$thumbnail_jpg = (!$thumbnail_webp && strpos($thumbnail,'thumbnail')!==false) ? $time.'s.jpg' : false; 

	$thumbnail_img = $thumbnail_webp ? $thumbnail_webp : $thumbnail_jpg;

	list($picw, $pich) = getimagesize(IMG_DIR.$imgfile);
	$picfile = $thumbnail_img ? THUMB_DIR.$thumbnail_img : IMG_DIR.$imgfile;
	$pch_exists = in_array($_pchext,['hide_animation','.pch']);
	$hide_animation_checkd = ($_pchext==='hide_animation');

	$pchext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);
	$pchext=basename($pchext);
	$pch_kb = $pchext ?  ceil(filesize(IMG_DIR.$time.$pchext) / 1024) : '';
	$select_app = false;
	$app_to_use = false;
	$ctype_pch = false;
	$download_app_dat=true;
	$current_app = '';
	if($pchext==='.pch'){
		$ctype_pch = true;
		$app_to_use = "neo";
		$current_app = "PaintBBS NEO";
	}elseif($pchext==='.chi'){
		$app_to_use = 'chi';
		$current_app = "ChickenPaint";
	}elseif($pchext==='.psd'){
		$app_to_use = 'klecks';
		$current_app = "Klecks";
	}else{
		$select_app = true;
		$download_app_dat=false;
	}
	//日記判定処理
	$adminpost=adminpost_valid();
	$adminmode = ($adminpost||admindel_valid());
	$aikotoba = aikotoba_valid();

	$arr_apps=app_to_use();
	$count_arr_apps=count($arr_apps);
	$use_paint=!empty($count_arr_apps);
	$select_app= ($select_app||$enableappselect) ? ($count_arr_apps>1) : false;
	$app_to_use=($use_paint && !$app_to_use) ? $arr_apps[0]: $app_to_use;
	$app_to_use = $select_app ? false : $app_to_use;
	if(!$use_paint){
		error($en ? "The paint feature is disabled." : "ペイント機能が無効です。");
	}
	// nsfw
	$admindel=admindel_valid();
	$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
	$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

	set_form_display_time();
	$admin_pass= null;

	// HTML出力
	$templete='continue.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}

//アプリ固有ファイルのダウンロード
function download_app_dat(): void {
	global $en;
	//投稿間隔をチェック
	check_submission_interval();
	check_same_origin();

	$pwd=(string)filter_input_data('POST','pwd');
	$pwdc=(string)filter_input_data('COOKIE','pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	$no = (string)filter_input_data('POST', 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input_data('POST', 'id');//intの範囲外

	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r");
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		if(strpos($line,"\t".$id."\t")!==false){
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$_pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no){
				if(!adminpost_valid()&&!admindel_valid()&&(!$pwd || !password_verify($pwd,$hash))){
					error($en?'Password is incorrect.':'パスワードが違います。');
				}
				break;
			} 
		}
	}
	closeFile ($rp);
	$time=basename($time);
	$pchext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);
	$pchext=basename($pchext);
	$filepath= IMG_DIR.$time.$pchext;
	if(!$pchext){
		error($en?'This operation has failed.':'失敗しました。');
	}

	check_AsyncRequest();//Asyncリクエストの時は処理を中断

	$mime_type = mime_content_type($filepath);
	header('Content-Type: '.$mime_type);
	header('Content-Length: '.filesize($filepath));
	header('Content-Disposition: attachment; filename="'.h(basename($filepath)).'"');

	readfile($filepath);
}

// 画像差し換え
function img_replace(): void {

	global $max_w,$max_h,$res_max_w,$res_max_h,$max_px,$en,$use_upload,$mark_sensitive_image,$usercode;

	$no = t(filter_input_data('POST', 'no',FILTER_VALIDATE_INT));
	$no = $no ? $no :t(filter_input_data('GET', 'no',FILTER_VALIDATE_INT));
	$id = t(filter_input_data('POST', 'id'));//intの範囲外
	$id = $id ? $id :t(filter_input_data('GET', 'id'));//intの範囲外

	$enc_pwd =t(filter_input_data('POST', 'enc_pwd'));
	$enc_pwd = $enc_pwd ? $enc_pwd : t(filter_input_data('GET', 'pwd'));
	$repcode = t(filter_input_data('POST', 'repcode'));
	$repcode = $repcode ? $repcode : t(filter_input_data('GET', 'repcode'));
	$userip = t(get_uip());
	//ホスト取得
	$host = $userip ? t(gethostbyaddr($userip)) : '';
	//ユーザーid
	$userid = t(getId($userip));
	$enc_pwd= $enc_pwd ? basename($enc_pwd): '';
	$enc_pwd = $enc_pwd ? hex2bin($enc_pwd): '';//バイナリに
	$pwd = $enc_pwd ? 
	openssl_decrypt($enc_pwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV):'';//復号化

	if(strlen($pwd) > 100) error($en? 'Password is too long.':'パスワードが長すぎます。');

	$adminpost=(adminpost_valid()|| is_adminpass($pwd));
	$admindel=admindel_valid();

	//アップロード画像の差し換え
	$up_tempfile = $_FILES['imgfile']['tmp_name'] ?? ''; // 一時ファイル名
	if (isset($_FILES['imgfile']['error']) && $_FILES['imgfile']['error'] === UPLOAD_ERR_NO_FILE){
		error($en?'Please attach an image.':'画像を添付してください。');
	} 
	if(isset($_FILES['imgfile']['error']) && in_array($_FILES['imgfile']['error'],[1,2])){//容量オーバー
		error($en? "Upload failed.\nThe file size is too large.":"アップロードに失敗しました。\nファイルサイズが大きすぎます。");
	} 
	$is_upload_img=false;
	$tool = '';
	if ($up_tempfile && $_FILES['imgfile']['error'] === UPLOAD_ERR_OK){

		if (!get_image_type($up_tempfile)) {//対応フォーマットではなかった時
			safe_unlink($up_tempfile);
			error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
		}

		check_csrf_token();
		$is_upload_img = true;
		$tool = 'upload';
		$pwd = t(filter_input_data('POST', 'pwd'));//アップロードの時はpostのパスワード

	}
	$tempfile='';
	$file_name='';
	$starttime='';
	$postedtime='';
	$repfind=false;
	$is_painted_img=false;
	$hide_animation=false;
	if(!$is_upload_img){
		/*--- テンポラリ捜査 ---*/
		$handle = opendir(TEMP_DIR);
		while ($file = readdir($handle)) {
			if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
				$file=basename($file);
				$userdata = file_get_contents(TEMP_DIR.$file);
				list($uip,$uhost,$uagent,$imgext,$ucode,$urepcode,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");//区切りの"\t"を行末に
				$hide_animation = ($u_hide_animation==='true');
				$tool= is_paint_tool_name($tool);
				$file_name = pathinfo($file, PATHINFO_FILENAME );//拡張子除去
				$imgext=basename($imgext);
				//ユーザーコードまたはipアドレスは一致しているか?
				$valid_poster_found = ($ucode && ($ucode == $usercode)) || ($uip && ($uip == $userip)); 
				//画像があり、認識コードがhitすれば抜ける
				if($file_name && is_file(TEMP_DIR.$file_name.$imgext) && $valid_poster_found && $urepcode && ($urepcode === $repcode)){
					$repfind=true;
					$is_painted_img=true;
					break;
				}
			}
		}
		closedir($handle);
		if(!$repfind){//見つからなかった時は
			location_paintcom();//新規投稿
		}
		$tempfile=TEMP_DIR.$file_name.$imgext;
	}
	if($up_tempfile && $is_upload_img && !is_file($up_tempfile)){
		error($en?'Please attach an image.':'画像を添付してください。');
	}
	//ログ読み込み
	if(!is_file(LOG_DIR."{$no}.log")){

		if($is_upload_img){//該当記事が無い時はエラー
			error($en? 'The article does not exist.':'記事がありません。');
		} 
		location_paintcom();//該当記事が無い時は新規投稿。
	}

	chmod(LOG_DIR."alllog.log",0600);
	$fp=fopen(LOG_DIR."alllog.log","r+");
	(array)$flock_option = $is_upload_img ? []: ['paintcom'=>true];
	file_lock($fp, LOCK_EX,$flock_option);

	$alllog_arr = create_array_from_fp($fp);

	if(empty($alllog_arr)){
		closeFile($fp);
		if($is_upload_img){//該当記事が無い時はエラー
			error($en?'This operation has failed.':'失敗しました。');
		} 
		location_paintcom();//該当記事が無い時は新規投稿。
	}
	check_open_no($no);
	chmod(LOG_DIR."{$no}.log",0600);
	$rp=fopen(LOG_DIR."{$no}.log","r+");
	(array)$flock_option = $is_upload_img ? []: ['paintcom'=>true];
	file_lock($rp, LOCK_EX,$flock_option);

	$r_arr = create_array_from_fp($rp);

	if(empty($r_arr)){
		closeFile($rp);
		closeFile($fp);
		if($is_upload_img){//該当記事が無い時はエラー
			error($en?'This operation has failed.':'失敗しました。');
		} 
		location_paintcom();//該当記事が無い時は新規投稿。
	}

	$flag=false;

	foreach($r_arr as $i => $line){
		if(strpos($line,"\t".$id."\t")!==false){
			list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_img_hash,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));
			if($id===$_time && $no===$_no){

				if($is_upload_img && ($_tool !== 'upload') || $is_painted_img && ($_tool === 'upload')) {
					safe_unlink($tempfile);
					closeFile($rp);
					closeFile($fp);
					error($en?'This operation has failed.':'失敗しました。');
				}
				if(($is_upload_img && $admindel) || (($adminpost||$admindel) && $_verified === 'adminpost') || ($pwd && password_verify($pwd,$_hash))){
					$flag=true;
					break;
				}
			}
			break;
		}
	}
	if($flag && !check_elapsed_days($_time)&&(!$adminpost && !$admindel)){//指定日数より古い画像差し換えは新規投稿にする

		closeFile($rp);
		closeFile($fp);
		if($is_upload_img){
			error($en?'This operation has failed.':'失敗しました。');
		} 
		location_paintcom();
	}

	if(!$flag){
		closeFile($rp);
		closeFile($fp);
		if($is_upload_img){//該当記事が無い時はエラー
			error($en?'This operation has failed.':'失敗しました。');
		} 
		location_paintcom();//該当記事が無い時は新規投稿。
	}
	$time=create_post_time();//ファイル名が重複しない投稿時刻を作成
	$upfile=TEMP_DIR.$time.'.tmp';

	if($is_upload_img && ($_tool==='upload') && ( $use_upload || $adminpost || $admindel) && is_file($up_tempfile)){
		$move_uploaded = move_uploaded_file($up_tempfile,$upfile);
		if(!$move_uploaded){//アップロード成功なら続行
			safe_unlink($up_tempfile);
			closeFile($rp);
			closeFile($fp);
			error($en?'This operation has failed.':'失敗しました。');
		}
		//Exifをチェックして画像が回転している時と位置情報が付いている時は上書き保存
		check_jpeg_exif($upfile);
	}
	if(!$is_upload_img && $repfind && is_file($tempfile) && ($_tool !== 'upload')){
		copy($tempfile, $upfile);
	}

	if(!is_file($upfile)){
		closeFile($rp);
		closeFile($fp);
		if($is_upload_img){
			error($en?'This operation has failed.':'失敗しました。');
		}
		location_paintcom();
	} 
	chmod($upfile,0606);
	if($is_upload_img){//実体データの縮小
		thumbnail_gd::thumb(TEMP_DIR,$time.'.tmp',$time,$max_px,$max_px,['toolarge'=>true]);
	}	
	//サイズオーバの時に変換したwebpのほうがファイル容量が小さくなっていたら元のファイルを上書き
	convert_andsave_if_smaller_png2webp($is_upload_img,$time.'.tmp',$time);

	if($is_upload_img){//アップロード画像のファイルサイズが大きすぎる時は削除
		delete_file_if_sizeexceeds($upfile,$fp,$rp);
	}

	$imgext = get_image_type($upfile);

	if (!$imgext) {
		closeFile($fp);
		closeFile($rp);
		safe_unlink($upfile);
		error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
	}
	list($w, $h) = getimagesize($upfile);
	$up_img_hash=substr(hash_file('sha256', $upfile), 0, 32);
	
	//チェックするスレッド数。 
	$n= 15;
	$chk_log_arr=array_slice($alllog_arr,0,$n,false);
	//$n行分の全体ログをもとにスレッドのログファイルを開いて配列を作成
	$chk_lines = create_chk_lins($chk_log_arr,$no);//取得済みの$noの配列を除外
	$chk_images=array_merge($chk_lines,$r_arr);
	$m2time=microtime2time($time);
	foreach($chk_images as $chk_line){
		list($chk_no,$chk_sub,$chk_name,$chk_verified,$chk_com,$chk_url,$chk_imgfile,$chk_w,$chk_h,$chk_thumbnail,$chk_painttime,$chk_log_img_hash,$chk_tool,$chk_pchext,$chk_time,$chk_first_posted_time,$chk_host,$chk_userid,$chk_hash,$chk_oya_)=explode("\t",trim($chk_line));

		if($is_upload_img && ($m2time === microtime2time($chk_time))){//投稿時刻の重複回避
			safe_unlink($upfile);
			closeFile($fp);
			closeFile($rp);
			error($en? 'Please wait a little.':'少し待ってください。');
		}
		if(!$is_upload_img && ((string)$time === (string)$chk_time)){
			$time=(string)($m2time+1).(string)substr($time,-6);
		}
		if(!$admindel && $is_upload_img && ($chk_log_img_hash && ($chk_log_img_hash === $up_img_hash))){
			safe_unlink($upfile);
			closeFile($fp);
			closeFile($rp);
			error($en?'Image already exists.':'同じ画像がありました。');
		}
	}

	check_AsyncRequest($upfile);//Asyncリクエストの時は処理を中断

	$imgfile = $time.$imgext;
	rename($upfile,IMG_DIR.$imgfile);
	if(!is_file(IMG_DIR.$imgfile)){
		closeFile($rp);
		closeFile($fp);
		error($en?'This operation has failed.':'失敗しました。');
	}
	chmod(IMG_DIR.$imgfile,0606);
	$src='';
	$pchext='';
	//PCHファイルアップロード
	// .pch, .spch,.chi,.psd ブランク どれかが返ってくる
	if (!$is_upload_img && $repfind && ($pchext = check_pch_ext(TEMP_DIR . $file_name,['upload'=>true]))) {
		$src = TEMP_DIR . $file_name . $pchext;
		$dst = IMG_DIR . $time . $pchext;
		if(copy($src, $dst)){
			chmod($dst, 0606);
		}
	}
	if($pchext === '.pch'){
		$pchext = $hide_animation ? 'hide_animation' : '.pch'; 
	}

	list($w,$h)=getimagesize(IMG_DIR.$imgfile);

	//縮小表示 
	$max_w = ($_oya==='res') ? $res_max_w : $max_w; 
	$max_h = ($_oya==='res') ? $res_max_h : $max_h; 

	list($w,$h)=image_reduction_display($w,$h,$max_w,$max_h);
	
	//サムネイル作成
	$thumbnail = make_thumbnail($imgfile,$time,$max_w,$max_h);//サムネイル作成

	$hide_thumbnail = ($_imgfile && strpos($_thumbnail,'hide_')!==false) ? 'hide_' : '';

	$thumbnail =  $hide_thumbnail.$thumbnail;

	//描画時間追加

	$painttime = '';
	if(ctype_digit($_painttime) && $starttime && ctype_digit($starttime) && $postedtime && ctype_digit($postedtime)){
		$psec=(int)$postedtime-(int)$starttime;
		$painttime=(int)$_painttime+(int)$psec;
	}
	
	$r_line= "$_no\t$_sub\t$_name\t$_verified\t$_com\t$_url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$up_img_hash\t$tool\t$pchext\t$time\t$_first_posted_time\t$host\t$userid\t$_hash\t$_oya\n";

	$r_arr[$i] = $r_line;

	if($_oya ==='oya'){

		$strcut_com=mb_strcut($_com,0,120);
		$newline = "$_no\t$_sub\t$_name\t$_verified\t$strcut_com\t$_url\t$imgfile\t$w\t$h\t$thumbnail\t$painttime\t$up_img_hash\t$tool\t$pchext\t$time\t$_first_posted_time\t$host\t$userid\t$_hash\toya\n";

		$flag=false;
		foreach($alllog_arr as $i => $val){
			if (strpos(trim($val), $no . "\t") === 0) {//全体ログで$noが一致したら
				list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_img_hash_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_) = explode("\t",trim($val));
				break;
			}
		}
		if(($id===$time_ && $no===$no_) &&
		((($is_upload_img && $admindel) ||
		(($adminpost||$admindel) && $verified_ === 'adminpost') ||
		($pwd && password_verify($pwd,$hash_))))){
			$alllog_arr[$i] = $newline;
			$flag=true;
		}
		if(!$flag){
			closeFile($rp);
			closeFile($fp);
			safe_unlink(IMG_DIR.$imgfile);
			if($is_upload_img){//該当記事が無い時はエラー
				error($en?'This operation has failed.':'失敗しました。');
			} 
			location_paintcom();//該当記事が無い時は新規投稿。
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

	if($is_upload_img){
		edit_form($time,$no);//編集画面にもどる
		exit();
	}
	redirect("./?resno={$no}&resid={$_first_posted_time}");

}

// 動画表示
function pchview(): void {

	global $boardname,$skindir,$en,$petit_lot;

	aikotoba_required_to_view();

	$imagefile = basename((string)filter_input_data('GET', 'imagefile'));
	$no = (string)filter_input_data('GET', 'no',FILTER_VALIDATE_INT);
	$id = pathinfo($imagefile, PATHINFO_FILENAME);
	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r");
	$flag=false;
	$resid = '';
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		if(strpos($line,"\t".$id."\t")!==false){
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no && $pchext){
				$resid=$first_posted_time;
				$flag=true;
				break;
			} 
			break;
		} 
	}
	closeFile ($rp);
	if(!$flag){
		error($en? 'The article does not exist.':'記事がありません。');
	}

	$pchext=basename($pchext);
	$view_replay = in_array($pchext,['.pch','.tgkr']);
	$pchfile = IMG_DIR.$time.$pchext;
	if(!$view_replay){
		error($en?'This operation has failed.':'失敗しました。');
	}
	list($picw, $pich) = getimagesize(IMG_DIR.$imgfile);
	$appw = $picw < 200 ? 200 : $picw;
	$apph = $pich < 200 ? 200 : $pich + 26;
	$parameter_day = date("Ymd");
	// HTML出力
	if($pchext==='.pch'){
		$templete='pch_view.html';
	}elseif($pchext==='.tgkr'){
		$templete='tgkr_view.html';
	}
	$admin_pass= null;

	include __DIR__.'/'.$skindir.$templete;
	exit();

}
//削除前の確認画面
function confirmation_before_deletion ($edit_mode=''): void {

	global $boardname,$home,$petit_ver,$petit_lot,$skindir,$set_nsfw,$en;
	global $deny_all_posts;

	//禁止ホストをチェック
	check_badhost();
	check_same_origin();
	//管理者判定処理
	$admindel=admindel_valid();
	aikotoba_required_to_view(true);
	$aikotoba = true;//テンプレートの互換性のため
	$userdel=userdel_valid();

	$resmode = false;//使っていない

	$page= $_SESSION['current_page_context']["page"] ?? 0;
	$resno= $_SESSION['current_page_context']["resno"] ?? 0;
	$postpage = $page;//古いテンプレート互換
	$postresno = $resno;//古いテンプレート互換

	$pwdc=(string)filter_input_data('COOKIE','pwdc');
	$edit_mode = (string)filter_input_data('POST','edit_mode');

	if(!($admindel||$userdel)){
		error($en?'This operation has failed.':'失敗しました。');
	}

	if($edit_mode!=='delmode' && $edit_mode!=='editmode'){
		error($en?'This operation has failed.':'失敗しました。');
	}
	$id = t(filter_input_data('POST','id'));//intの範囲外
	$no = t(filter_input_data('POST','no',FILTER_VALIDATE_INT));

	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r");
	$r_arr = create_array_from_fp($rp);
	closeFile ($rp);

	if(empty($r_arr)){
		error($en?'This operation has failed.':'失敗しました。');
	}
	$find=false;
	$resid= '';
	foreach($r_arr as $i =>$val){
		if(strpos($val,"\t".$id."\t")!==false){
			$_line=explode("\t",trim($val));
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$_line;
			if($id===$time && $no===$_no){

				$out[0][]=create_res($_line);
				$resid=$first_posted_time;
				$find=true;
				break;
				
			}
		}
	}
	if(!$find){
		error($en?'The article was not found.':'記事が見つかりません。');
	}

	$_SESSION['current_resid']	= $first_posted_time;

	$token=get_csrf_token();

	// nsfw
	$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
	$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

	$count_r_arr=count($r_arr);

	set_form_display_time();

	$admin_pass= null;
	if($edit_mode==='delmode'){
		$templete='before_del.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}
	if($edit_mode==='editmode'){
		$templete='before_edit.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();
	}
	error($en?'This operation has failed.':'失敗しました。');
}

//編集画面
function edit_form($id='',$no=''): void {

	global $petit_ver,$petit_lot,$home,$boardname,$skindir,$set_nsfw,$en,$max_kb,$use_upload,$mark_sensitive_image,$use_url_input_field;

	//投稿間隔をチェック
	check_submission_interval();

	check_same_origin();

	$max_byte = $max_kb * 1024*2;
	$token=get_csrf_token();
	$admindel=admindel_valid();
	$adminpost=adminpost_valid();
	$userdel=userdel_valid();

	$pwd=(string)filter_input_data('POST','pwd');
	$pwdc=(string)filter_input_data('COOKIE','pwdc');
	$pwd = $pwd ? $pwd : $pwdc;

	if(!($admindel||$userdel)){
		error($en?"This operation has failed.\nPlease reload.":"失敗しました。\nリロードしてください。");
	}
	if(!$admindel&&!$pwd){
		error($en?'Password is incorrect.':'パスワードが違います。');
	}

	$id_and_no=(string)filter_input_data('POST','id_and_no');

	if($id_and_no){//引数の$id,$noを更新
		list($id,$no)=explode(",",trim($id_and_no));
	}

	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	$rp=fopen(LOG_DIR."{$no}.log","r");

	$flag=false;
	while ($line = fgets($rp)) {
		if(strpos($line,"\t".$id."\t")!==false){
			$lines=explode("\t",trim($line));
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$lines;
			if($id===$time && $no===$_no){
			
				if(!$admindel&&(!$pwd||!password_verify($pwd,$hash))){
					closeFile($rp);
					error($en?'Password is incorrect.':'パスワードが違います。');
				}
				if($admindel||check_elapsed_days($time)){
					$flag=true;
					break;
				}
			}
		}
	}
	closeFile($rp);

	if(!$flag){
		error($en?'This operation has failed.':'失敗しました。');
	}

	check_AsyncRequest();//Asyncリクエストの時は処理を中断

	$out[0][]=create_res($lines);//$linesから、情報を取り出す;

	$page= $_SESSION['current_page_context']["page"] ?? 0;
	$resno= $_SESSION['current_page_context']["resno"] ?? 0;
	$postpage = $page;//古いテンプレート互換
	$postresno = $resno;//古いテンプレート互換

	foreach($lines as $i => $val){//エスケープ処理
		$lines[$i]=h($val);
	}
	list($_no,$sub,$name,$verified,$_com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$lines;

	$_SESSION['current_resid']	= $first_posted_time;

	$com=h(str_replace('"\n"',"\n",$com));

	$pch_exists = in_array($pchext,['.pch','.tgkr','hide_animation','hide_tgkr']);
	$hide_animation_checkd = (strpos($pchext,'hide_') === 0);
	$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);

	$hide_thumb_checkd = (strpos($thumbnail,'hide_') === 0);
	$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

	$admin = ($admindel||$adminpost||is_adminpass($pwd));

	$image_rep=true;

	//フォームの表示時刻をセット
	set_form_display_time();

	$admin_pass= null;
	// HTML出力
	$templete='edit_form.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}

//編集
function edit(): void {
	global $name_input_required,$max_com,$en,$mark_sensitive_image,$use_url_input_field,$admin_pass;

	//投稿間隔をチェック
	check_submission_interval();
	//Fetch API以外からのPOSTを拒否
	check_post_via_javascript();
	check_csrf_token();
	//NGワードがあれば拒絶
	Reject_if_NGword_exists_in_the_post();
	//POSTされた内容を取得
	$userip =t(get_uip());
	//ホスト取得
	$host = $userip ? t(gethostbyaddr($userip)) : '';
	$userid = t(getId($userip));

	$sub = t(filter_input_data('POST','sub'));
	$name = t(filter_input_data('POST','name'));
	$com = t(filter_input_data('POST','com'));
	$id = t(filter_input_data('POST','id'));//intの範囲外
	$no = t(filter_input_data('POST','no',FILTER_VALIDATE_INT));
	$hide_thumbnail = $mark_sensitive_image ? (bool)filter_input_data('POST','hide_thumbnail',FILTER_VALIDATE_BOOLEAN) : false;
	$hide_animation=(bool)filter_input_data('POST','hide_animation',FILTER_VALIDATE_BOOLEAN);
	$pwd=(string)filter_input_data('POST','pwd');
	$pwdc=(string)filter_input_data('COOKIE','pwdc');
	$pwd = $pwd ? $pwd : $pwdc;
	$url = t(filter_input_data('POST','url',FILTER_VALIDATE_URL));

	$admindel=(admindel_valid() || is_adminpass($pwd));

	$url= (adminpost_valid()|| $admindel || $use_url_input_field) ? $url : '';

	$userdel=userdel_valid();
	if(!($admindel||($userdel&&$pwd))){
		error($en?"This operation has failed.\nPlease reload.":"失敗しました。\nリロードしてください。");
	}

	//ログ読み込み
	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	chmod(LOG_DIR."alllog.log",0600);
	$fp=fopen(LOG_DIR."alllog.log","r+");
	file_lock($fp, LOCK_EX);

	check_open_no($no);
	chmod(LOG_DIR."{$no}.log",0600);
	$rp=fopen(LOG_DIR."{$no}.log","r+");
	file_lock($rp, LOCK_EX);

	$r_arr = create_array_from_fp($rp);

	if(empty($r_arr)){
		closeFile($rp);
		closeFile($fp);
		error($en?'This operation has failed.':'失敗しました。');
	}

	$flag=false;
	foreach($r_arr as $i => $line){
		if(strpos($line,"\t".$id."\t")!==false){

			list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_img_hash,$_tool,$pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($line));

			if($id===$_time && $no===$_no){
				$res_oya_deleted=(!$_name && !$_com && !$_url && !$_imgfile && !$_userid && ($_oya==='oya'));//削除ずみのoyaの時
				if(!$admindel && $res_oya_deleted){//削除ずみのoyaの時
					error($en?'This operation has failed.':'失敗しました。');
				}

				if($admindel||(check_elapsed_days($_time)&&$pwd&&password_verify($pwd,$_hash))){
					$flag=true;
					break;
				}
			}
			break;
		}
	}
	if(!$flag){
		closeFile($rp);
		closeFile($fp);
		error($en?'This operation has failed.':'失敗しました。');
	}

	$sub=($_oya==='res') ? $_sub : $sub; 
	//POSTされた値をログファイルに格納する書式にフォーマット
	$formatted_post=create_formatted_text_from_post($name,$sub,$url,$com);
	$name = $formatted_post['name'];
	$sub = $formatted_post['sub'];
	$url = $formatted_post['url'];
	$com = $formatted_post['com'];

	if(!$_imgfile && !$com && !$admindel){
		closeFile($rp);
		closeFile($fp);
		error($en?'Please write something.':'何か書いて下さい。');
	}

	$alllog_arr = create_array_from_fp($fp);

	$n= 5;
	$chk_log_arr=array_slice($alllog_arr,0,$n,false);
	//$n行分の全体ログをもとにスレッドのログファイルを開いて配列を作成
	$_chk_lines = create_chk_lins($chk_log_arr,$no);//取得済みの$chk_restoの配列を除外
	$chk_lines=array_merge($_chk_lines,$r_arr);
	foreach($chk_lines as $line){
		if(strpos($line,"\t".$userid."\t")!==false){
			list($_no_,$_sub_,$_name_,$_verified_,$_com_,$_url_,$_imgfile_,$_w_,$_h_,$_thumbnail_,$_painttime_,$_log_img_hash_,$_tool_,$_pchext_,$_time_,$_first_posted_time_,$_host_,$_userid_,$_hash_,$_oya_)=explode("\t",trim($line));

			if(!$admindel && ($userid===$_userid_) && ($id!==$_time_) && ($com && ($com!==$_com) && ($com === $_com_))){
				closeFile($fp);
				closeFile($rp);
				error($en?'Post once by this comment.':'同じコメントがありました。');
			}
		}
	}

	$thumbnail_webp = is_file(THUMB_DIR.$_time.'s.webp') ? 'thumbnail_webp' : '';
	$thumbnail_jpg = is_file(THUMB_DIR.$_time.'s.jpg') ? 'thumbnail' : '';
	$thumbnail = $thumbnail_webp ? $thumbnail_webp : $thumbnail_jpg;

	$hide_thumbnail=($_imgfile && $hide_thumbnail) ? 'hide_' : '';
	$thumbnail =  $mark_sensitive_image ? $hide_thumbnail.$thumbnail : $thumbnail;

	if(in_array($pchext,['.pch','hide_animation'])){
		$pchext= $hide_animation ? 'hide_animation' : '.pch'; 
	}
	if(in_array($pchext,['.tgkr','hide_tgkr'])){
		$pchext= $hide_animation ? 'hide_tgkr' : '.tgkr'; 
	}
	$is_admin_set_nsfw = ($admindel && ($sub === $_sub) && ($url === $_url) && ($com === $_com));
	$host = $is_admin_set_nsfw ? $_host : $host;//管理者による閲覧注意への変更時は投稿者のホスト名を変更しない
	$userid = ($admindel && !$res_oya_deleted) ? $_userid : $userid;//管理者による変更時は投稿者のidを変更しない
	$hash = ($admindel && $res_oya_deleted) ? password_hash($admin_pass,PASSWORD_BCRYPT,['cost' => 5]) : $_hash;//削除ずみのoyaの編集時は管理者パスを設定。
	$verified = ($admindel && $res_oya_deleted) ? 'adminpost' : $_verified;//削除ずみのoyaの編集時は管理者パスを設定。

	$r_line= "$_no\t$sub\t$name\t$verified\t$com\t$url\t$_imgfile\t$_w\t$_h\t$thumbnail\t$_painttime\t$_log_img_hash\t$_tool\t$pchext\t$_time\t$_first_posted_time\t$host\t$userid\t$hash\t$_oya\n";
	
	$r_arr[$i] = $r_line;

	if($_oya==='oya'){
		//コメントを120バイトに短縮
		$strcut_com=mb_strcut($com,0,120);
		$newline = "$_no\t$sub\t$name\t$verified\t$strcut_com\t$url\t$_imgfile\t$_w\t$_h\t$thumbnail\t$_painttime\t$_log_img_hash\t$_tool\t$pchext\t$_time\t$_first_posted_time\t$host\t$userid\t$hash\toya\n";

		if(empty($alllog_arr)){
			closeFile($rp);
			closeFile($fp);
			error($en?'This operation has failed.':'失敗しました。');
		}
		$flag=false;
		foreach($alllog_arr as $i => $val){
			if (strpos(trim($val), $no . "\t") === 0) {//全体ログで$noが一致したら
				break;
			}
		}
		list($no_,$sub_,$name_,$verified_,$com_,$url_,$imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_img_hash_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_) = explode("\t",trim($val));
		if(($id===$time_ && $no===$no_) &&
		($admindel || ($pwd && password_verify($pwd,$hash_)))){

			$alllog_arr[$i] = $newline;
			$flag=true;
		}
		if(!$flag){
			closeFile($rp);
			closeFile($fp);
			error($en?'This operation has failed.':'失敗しました。');
		}

		writeFile($fp,implode("",$alllog_arr));
	}
	writeFile($rp, implode("", $r_arr));

	closeFile($rp);
	closeFile($fp);

	unset($_SESSION['userdel']);
	delete_res_cache();

	redirect("./?resno={$no}&resid={$_first_posted_time}");

}

//記事削除
function del(): void {
	global $en;

	//禁止ホストをチェック
	check_badhost();
	//投稿間隔をチェック
	check_submission_interval();

	check_csrf_token();

	$admindel=admindel_valid();
	$userdel=userdel_valid();

	$pwd=(string)filter_input_data('POST','pwd');
	$pwdc=(string)filter_input_data('COOKIE','pwdc');
	$pwd = $pwd ? $pwd : $pwdc;

	if(!($admindel||$userdel)){
		error($en?"This operation has failed.\nPlease reload.":"失敗しました。\nリロードしてください。");
	}
	if(!$admindel&&!$pwd){
		error($en?'Password is incorrect.':'パスワードが違います。');
	}
	$id_and_no=(string)filter_input_data('POST','id_and_no');
	if(!$id_and_no){
		error($en?'The post deletion checkbox is unchecked.':'記事が選択されていません。');
	}
	$id=$no='';
	if($id_and_no){
		list($id,$no)=explode(",",trim($id_and_no));
	}
	$delete_thread=(bool)filter_input_data('POST','delete_thread',FILTER_VALIDATE_BOOLEAN);
	chmod(LOG_DIR."alllog.log",0600);
	$fp=fopen(LOG_DIR."alllog.log","r+");
	file_lock($fp, LOCK_EX);

	if(!is_file(LOG_DIR."{$no}.log")){
		error($en? 'The article does not exist.':'記事がありません。');
	}
	check_open_no($no);
	chmod(LOG_DIR."{$no}.log",0600);
	$rp=fopen(LOG_DIR."{$no}.log","r+");
	file_lock($rp, LOCK_EX);

	$r_arr = create_array_from_fp($rp);

	if(empty($r_arr)){
		closeFile ($rp);
		closeFile($fp);
		error($en?'This operation has failed.':'失敗しました。');
	}

	$find=false;
	foreach($r_arr as $i =>$val){
		if(strpos($val,"\t".$id."\t")!==false){
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_img_hash,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($val));
			if($id===$time && $no===$_no){
				if(!$admindel){
					if(!$pwd||!password_verify($pwd,$hash)){
						closeFile ($rp);
						closeFile($fp);
						error($en?'Password is incorrect.':'パスワードが違います。');
					}
				}
				$find=true;
				break;
			}
		}
	}
	if(!$find){
		closeFile ($rp);
		closeFile($fp);
		error($en?'The article was not found.':'記事が見つかりません。');
	}

	$count_r_arr=count($r_arr);
	list($d_no,$d_sub,$d_name,$s_verified,$d_com,$d_url,$d_imgfile,$d_w,$d_h,$d_thumbnail,$d_painttime,$d_log_img_hash,$d_tool,$d_pchext,$d_time,$d_first_posted_time,$d_host,$d_userid,$d_hash,$d_oya)=explode("\t",trim($r_arr[0]));
	$res_oya_deleted=(!$d_name && !$d_com && !$d_url && !$d_imgfile && !$d_userid && ($d_oya==='oya'));

	if(($oya==='oya')||(($count_r_arr===2) && $res_oya_deleted)){//スレッド削除?

		$alllog_arr = create_array_from_fp($fp);

		if(empty($alllog_arr)){
			closeFile ($rp);
			closeFile($fp);
			error($en?'This operation has failed.':'失敗しました。');
		}
		$flag=false;
		foreach($alllog_arr as $j =>$_val){//全体ログ
			if (strpos(trim($_val), $no . "\t") === 0) {//全体ログで$noが一致したら
				break;
			}
		}

		list($no_,$sub_,$name_,$verified_,$com_,$url_,$_imgfile_,$w_,$h_,$thumbnail_,$painttime_,$log_img_hash_,$tool_,$pchext_,$time_,$first_posted_time_,$host_,$userid_,$hash_,$oya_)=explode("\t",trim($_val));
		$alllog_oya_deleted=($no===$no_ && !$name_ && !$com_ && !$url_ && !$_imgfile_ && !$userid_ && ($oya_==='oya'));

		if(($alllog_oya_deleted && ($no===$no_))||($id===$time_ && $no===$no_)){
			if(!$alllog_oya_deleted && !$admindel && (!$pwd||!password_verify($pwd,$hash_))){
				closeFile ($rp);
				closeFile($fp);
				error($en?'Password is incorrect.':'パスワードが違います。');//親削除ずみ、管理者では無い時はパスワードの一致を確認
			}
			$flag=true;
		}
		if(!$flag){
			closeFile ($rp);
			closeFile($fp);
			error($en?'This operation has failed.':'失敗しました。');
		}

		check_AsyncRequest();//Asyncリクエストの時は処理を中断

		if($count_r_arr===1 || (($count_r_arr===2) && $res_oya_deleted) || $delete_thread){//スレッドを削除する?

				unset($alllog_arr[$j]);
				foreach($r_arr as $r_line) {//スレッドの一連のファイルを削除
					list($_no,$_sub,$_name,$_verified,$_com,$_url,$_imgfile,$_w,$_h,$_thumbnail,$_painttime,$_log_img_hash,$_tool,$_pchext,$_time,$_first_posted_time,$_host,$_userid,$_hash,$_oya)=explode("\t",trim($r_line));
					
					delete_files ($_imgfile, $_time);//一連のファイルを削除
					
				}
				closeFile ($rp);
				safe_unlink(LOG_DIR.$no.'.log');

		}else{
				delete_files ($imgfile, $time);//該当記事の一連のファイルを削除
				$deleted_sub = $en? 'No subject':'無題';
				$newline="$no\t$deleted_sub\t\t\t\t\t\t\t\t\t\t\t\t\t$time_\t$first_posted_time_\t$host_\t\t$hash_\toya\n";
				$alllog_arr[$j]=$newline;
				$r_arr[0]=$newline;
				writeFile ($rp,implode("",$r_arr));
				closeFile ($rp);
		}

		writeFile($fp,implode("",$alllog_arr));

	}else{//レスの削除のみ

		unset($r_arr[$i]);
		delete_files ($imgfile, $time);//一連のファイルを削除
		writeFile ($rp,implode("",$r_arr));
		closeFile ($rp);
	}
	closeFile($fp);

	unset($_SESSION['userdel']);
	//多重送信防止
	branch_destination_of_location();
}

//シェアするserverの選択画面
function set_share_server(): void {
	global $en,$skindir,$servers,$petit_lot,$boardname;
	
	//ShareするServerの一覧
	//｢"ラジオボタンに表示するServer名","snsのserverのurl"｣
	$servers= $servers ??
	[
	
		["X","https://x.com"],
		["Bluesky","https://bsky.app"],
		["Threads","https://www.threads.net"],
		["pawoo.net","https://pawoo.net"],
		["fedibird.com","https://fedibird.com"],
		["misskey.io","https://misskey.io"],
		["xissmie.xfolio.jp","https://xissmie.xfolio.jp"],
		["misskey.design","https://misskey.design"],
		["nijimiss.moe","https://nijimiss.moe"],
		["sushi.ski","https://sushi.ski"],
	
	];
	//設定項目ここまで

	$servers[]=[($en?"Direct input":"直接入力"),"direct"];//直接入力の箇所はそのまま。

	$encoded_t=filter_input_data('GET',"encoded_t");
	$encoded_u=filter_input_data('GET',"encoded_u");
	$sns_server_radio_cookie=(string)filter_input_data('COOKIE',"sns_server_radio_cookie");
	$sns_server_direct_input_cookie=(string)filter_input_data('COOKIE',"sns_server_direct_input_cookie");

	$admin_pass= null;
	//HTML出力
	$templete='set_share_server.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}
function saveimage(): void {
	
	$tool=filter_input_data('GET',"tool");

	$image_save = new image_save;

	header('Content-type: text/plain');

	switch($tool){
		case "neo":
			$image_save->save_neo();
			break;
		case "chi":
			$image_save->save_chickenpaint();
			break;
		case "klecks":
			$image_save->save_klecks();
			break;
		case "tegaki":
			$image_save->save_klecks();
			break;
	}

}
//カタログ表示
function catalog(): void {
	global $home,$catalog_pagedef,$skindir,$display_link_back_to_home;
	global $boardname,$petit_ver,$petit_lot,$set_nsfw,$en,$mark_sensitive_image; 

	aikotoba_required_to_view();
	set_page_context_to_session();

	$page=(int)filter_input_data('GET','page',FILTER_VALIDATE_INT);
	$page=$page<0 ? 0 : $page;
	$pagedef=$catalog_pagedef;

	$fp=fopen(LOG_DIR."alllog.log","r");
	$count_alllog=0;
	$_res=[];
	$out=[];
	$oya=0;
	while ($line = fgets($fp)) {
		if(!trim($line)){
			continue;
		}
		if($page <= $count_alllog && $count_alllog < $page+$pagedef){
			$_res = create_res(explode("\t",trim($line)),['catalog'=>true]);//$lineから、情報を取り出す
			$out[$oya][] = $_res;//$lineから、情報を取り出す
			if(empty($out[$oya])){
				unset($out[$oya]);
			}
			++$oya;
		}
		++$count_alllog;
	}
	fclose($fp);

	//管理者判定処理
	$admindel=admindel_valid();
	$aikotoba = aikotoba_valid();

	$userdel=userdel_valid();
	$adminpost=adminpost_valid();

	$encoded_q='';//旧バージョンのテンプレート用

	//Cookie
	$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
	$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);
	//token
	$token=get_csrf_token();
	//misskey投稿用では無い
	$misskey_note=false;

	//ページング
	list($start_page,$end_page)=calc_pagination_range($page,$pagedef);
	list($prev,$next)=get_prev_next_pages($page,$pagedef,$count_alllog);

	$is_badhost=is_badhost();//管理者ログインリンクを表示するかどうかの判定

	$admin_pass= null;
	// HTML出力
	$templete='catalog.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}

//通常表示
function view(): void {
	global $use_upload,$home,$pagedef,$dispres,$allow_comments_only,$skindir,$descriptions,$max_kb,$root_url,$use_misskey_note;
	global $boardname,$max_res,$use_miniform,$use_diary,$petit_ver,$petit_lot,$set_nsfw,$use_sns_button,$deny_all_posts,$en,$mark_sensitive_image,$only_admin_can_reply; 
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$use_tegaki,$use_axnos,$display_link_back_to_home,$display_search_nav,$switch_sns,$sns_window_width,$sns_window_height,$sort_comments_by_newest,$use_url_input_field;
	global $disp_image_res,$nsfw_checked,$sitename,$fetch_articles_to_skip;

	aikotoba_required_to_view();
	set_page_context_to_session();
	//禁止ホスト
	$is_badhost = is_badhost();

	$page=(int)filter_input_data('GET','page',FILTER_VALIDATE_INT);
	$page=$page<0 ? 0 : $page;
	//管理者判定処理
	$adminpost=adminpost_valid();
	$admindel=admindel_valid();
	$userdel=userdel_valid();

	$max_byte = $max_kb * 1024*2;
	$denny_all_posts=$deny_all_posts;//互換性
	$allow_coments_only=$allow_comments_only;//互換性

	session_sta();
	unset ($_SESSION['enableappselect']);

	$fp=fopen(LOG_DIR."alllog.log","r");
	$article_nos=[];
	$count_alllog=0;
	while ($_line = fgets($fp)) {
		if(!trim($_line)){
			continue;
		}
		if($page <= $count_alllog && $count_alllog < $page+$pagedef){
			list($_no)=explode("\t",trim($_line),2);
			$article_nos[]=$_no;	
		}
		++$count_alllog;//処理の後半で記事数のカウントとして使用
	}
	fclose($fp);

	$index_cache_json = __DIR__.'/template/cache/index_cache.json';

	$out=[];
	if($page===0 && !$admindel && !$userdel && !$adminpost && !$is_badhost){
		$out = is_file($index_cache_json) ? json_decode(file_get_contents($index_cache_json),true) : [];
	}
	if(empty($out)){
		//oyaのループ
		foreach($article_nos as $oya => $no){

			//個別スレッドのループ
			if(!is_file(LOG_DIR."{$no}.log")){
				continue;	
			}
			$_res=[];
			$out[$oya]=[];
			$find_hide_thumbnail=false;
			check_open_no($no);
			$rp = fopen(LOG_DIR."{$no}.log", "r");//個別スレッドのログを開く
			$lines=create_array_from_fp($rp);
			fclose($rp);
			$countres=count($lines);
			$com_skipres= $dispres ? ($countres-($dispres+1)) : 0;

			if($userdel || $admindel){
				$com_skipres = 0;	//削除モードの時はレスを省略しない
			}

			foreach($lines as $i => $line){

				$_res=[];

				if($fetch_articles_to_skip ||($i===0 || $i>$com_skipres)){//省略するレスは処理しない
					$_res = create_res(explode("\t",trim($line)),['is_badhost'=>$is_badhost]);//$lineから、情報を取り出す
				}
				if(isset($_res['img']) && $_res['img']){
					if($_res['hide_thumbnail']){
						$find_hide_thumbnail=true;
					}
				}
				$out[$oya][]=$_res;
			}	
			$out[$oya][0]['find_hide_thumbnail']=$find_hide_thumbnail;
			$out[$oya][0]['countres']=$countres;
			if(empty($out[$oya])||$out[$oya][0]['oya']!=='oya'){
				unset($out[$oya]);
			}
		}
	}
	unset($lines);
	$aikotoba = aikotoba_valid();
	$adminpost=adminpost_valid();
	$resform = ((!$only_admin_can_reply && !$use_diary && !$is_badhost && $aikotoba)||$adminpost);
	$resform = $deny_all_posts ? false :$resform;

	//Cookie
	$namec=h((string)filter_input_data('COOKIE','namec'));
	$pwdc=h((string)filter_input_data('COOKIE','pwdc'));
	$urlc=h((string)filter_input_data('COOKIE','urlc',FILTER_VALIDATE_URL));
	$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
	$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);


	$arr_apps=app_to_use();
	$count_arr_apps=count($arr_apps);
	$use_paint=!empty($count_arr_apps);
	$select_app=($count_arr_apps>1);
	$app_to_use=($count_arr_apps===1) ? $arr_apps[0] : ''; 

	//token
	$token=get_csrf_token();

	$use_top_form = true;//互換性のために常にtrue;
	//ページング
	list($start_page,$end_page)=calc_pagination_range($page,$pagedef);
	//prev next 
	list($prev,$next)=get_prev_next_pages($page,$pagedef,$count_alllog);

	if($page===0 && !$admindel && !$adminpost && !$is_badhost){
		if(!is_file($index_cache_json)){
			file_put_contents($index_cache_json,json_encode($out),LOCK_EX);
			chmod($index_cache_json,0600);
		}
	}
	$use_misskey_note = $use_diary  ? ($adminpost||$admindel) : $use_misskey_note;
	$lightbox_gallery=false;
	$resmode=false;
	$resno=0;
	$sitename= preg_replace("/\A\s*\z/u","",$sitename);//連続する空文字を削除

	//PCHアップロードの投稿可能な最大値
	$upload_max_filesize = get_upload_max_filesize() * 1024 * 1024; //byte単位に変換
	//フォームの表示時刻をセット
	set_form_display_time();

	$admin_pass= null;
	// HTML出力
	$templete='main.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}
//レス画面
function res (): void {
	global $use_upload,$home,$skindir,$root_url,$use_res_upload,$max_kb,$mark_sensitive_image,$only_admin_can_reply,$use_misskey_note;
	global $boardname,$max_res,$petit_ver,$petit_lot,$set_nsfw,$use_sns_button,$deny_all_posts,$sage_all,$view_other_works,$en,$use_diary,$nsfw_checked;
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$use_tegaki,$use_axnos,$display_link_back_to_home,$display_search_nav,$switch_sns,$sns_window_width,$sns_window_height,$sort_comments_by_newest,$use_url_input_field,$set_all_images_to_nsfw;

	aikotoba_required_to_view();
	set_page_context_to_session();
	//禁止ホスト
	$is_badhost = is_badhost();

	$max_byte = $max_kb * 1024*2;

	$denny_all_posts=$deny_all_posts;
	$resno=(string)filter_input_data('GET','resno',FILTER_VALIDATE_INT);
	$misskey_note = $use_misskey_note ? (bool)filter_input_data('GET','misskey_note',FILTER_VALIDATE_BOOLEAN) : false;
	$res_catalog = $misskey_note || (bool)filter_input_data('GET','res_catalog',FILTER_VALIDATE_BOOLEAN);
	$resid = (string)filter_input_data('GET','resid');

	session_sta();

	$_SESSION['current_resid']	= $resid;

	unset ($_SESSION['enableappselect']);

	if(!is_file(LOG_DIR."{$resno}.log")){
		error($en?'Thread does not exist.':'スレッドがありません');	
	}
	$rresname = [];
	$resname = '';
	$oyaname='';
	$find_hide_thumbnail=false;	
	$og_sub = '';
	$og_name = '';

	check_open_no($resno);
	$rp = fopen(LOG_DIR."{$resno}.log", "r");//個別スレッドのログを開く

	$out[0]=[];

	$og_img="";
	$og_descriptioncom = ""; 
	$og_hide_thumbnail = "";
	while ($line = fgets($rp)) {
		if(!trim($line)){
			continue;
		}
		$_res = create_res(explode("\t",trim($line)),['is_badhost'=>$is_badhost]);//$lineから、情報を取り出す
		if($res_catalog && !$_res['img'] && $_res['oya']!=='oya'){
			continue;
		}
		if($_res['img']){
			if($_res['hide_thumbnail']){
				$find_hide_thumbnail=true;	
			}
		}
		if($_res['oya']==='oya'){

			$_res['time_left_to_close_the_thread'] = time_left_to_close_the_thread($_res['time']);
			$_res['descriptioncom']= $_res['com'] ? h(s(mb_strcut($_res['com'],0,300))) :"";

			$oyaname = $_res['name'];
		}
		// 投稿者名を配列にいれる
			if (($oyaname !== $_res['name']) && !in_array($_res['name'], $rresname)) { // 重複チェックと親投稿者除外
				$rresname[] = $_res['name'];
		} 
		if($_res['oya']==='oya' || $_res['first_posted_time'] === $resid){//親または最初の投稿時間と一致する時
			$og_img = $_res['img'];
			$og_descriptioncom = $_res['com'] ? h(s(mb_strcut($_res['com'],0,300))) :"";
			$og_hide_thumbnail = $_res['hide_thumbnail'];
			$og_sub = $_res['sub'];
			$og_name = $_res['name'];
		}
		$out[0][]=$_res;
		$out[0][0]['find_hide_thumbnail']=$find_hide_thumbnail;
	}	
	fclose($rp);
	if(empty($out[0])||$out[0][0]['oya']!=='oya'){
		error($en? 'The article does not exist.':'記事がありません。');
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
	$count_alllog=0;
	$i=0;
	$j=0;
	$find=false;
	$articles1=[];
	$articles2=[];

	while ($line = fgets($fp)) {
		if(!trim($line)){
			continue;
		}
		if (strpos(trim($line), $resno . "\t") === 0) {//現在のスレッド
			$find=true;
			$i=$j;
		}
		if($find && ($i<$j)){
			$articles2[$j]=$line;//現在のスレッドより20件後ろの行を取得
		}
		if($find && ($i+20)<=$j){
			break;
		}
		++$j;
	}
	rewind($fp);
	$j=0;
	while ($line = fgets($fp)) {//メモリ消費量を削減するため二度ループ
		if(!trim($line)){
			continue;
		}
		if(($i!==$j) && ($i-20) <= $j){//現在のスレッドより20件手前の行を取得
			$articles1[$j]=$line;
		}
		if($i===$j){
			break;
		}
		++$j;
	}
	fclose($fp);

	$next=$articles2[$i+1] ?? '';
	$prev=$articles1[$i-1] ?? '';
	$next=$next ? (create_res(explode("\t",trim($next)),['catalog'=>true])):[];
	$prev=$prev ? (create_res(explode("\t",trim($prev)),['catalog'=>true])):[];
	$next=(!empty($next) && is_file(LOG_DIR."{$next['no']}.log"))?$next:[];
	$prev=(!empty($prev) && is_file(LOG_DIR."{$prev['no']}.log"))?$prev:[];

	$rr1=[];
	$rr2=[];
	if($view_other_works){
		$view_other_works=[];
		$a=[];
		foreach($articles1 as $val){

			$r1=create_res(explode("\t",trim($val)),['catalog'=>true]);
			if(!empty($r1)&&$r1['img']&&$r1['no']!==$resno){
				$rr1[]=$r1;
			}
		}
		foreach($articles2 as $val){

			$r2=create_res(explode("\t",trim($val)),['catalog'=>true]);
			if(!empty($r2)&&$r2['img']&&$r2['no']!==$resno){
				$rr2[]=$r2;
			}
		}
		if((3<=count($rr1)) && (3<=count($rr2))  ){
			$rr1 = array_slice($rr1,-3);
			$rr2 = array_slice($rr2,0,3);
			$view_other_works= array_merge($rr1,$rr2);
		
		}elseif((6>count($rr2))&&(6<=count($rr1))){
			$view_other_works= array_slice($rr1,-6);
		}elseif((6>count($rr1))&&(6<=count($rr2))){
			$view_other_works= array_slice($rr2,0,6);
		}else{
			$view_other_works= array_merge($rr1,$rr2);
			$view_other_works= array_slice($view_other_works,0,6);
		}
	}
	//管理者判定処理
	$admindel=admindel_valid();
	$aikotoba = aikotoba_valid();
	$userdel=userdel_valid();
	$adminpost=adminpost_valid();
	$resform = ((!$only_admin_can_reply && !$is_badhost && $aikotoba)||$adminpost);
	$resform = $deny_all_posts ? false :$resform;
	$resform = ($userdel||$admindel) ? false :$resform;

	//Cookie
	$namec=h((string)filter_input_data('COOKIE','namec'));
	$pwdc=h((string)filter_input_data('COOKIE','pwdc'));
	$urlc=h((string)filter_input_data('COOKIE','urlc',FILTER_VALIDATE_URL));
	$nsfwc=(bool)filter_input_data('COOKIE','nsfwc',FILTER_VALIDATE_BOOLEAN);
	$set_nsfw_show_hide=(bool)filter_input_data('COOKIE','p_n_set_nsfw_show_hide',FILTER_VALIDATE_BOOLEAN);

	$arr_apps=app_to_use();
	$count_arr_apps=count($arr_apps);
	$use_paint=!empty($count_arr_apps);
	$select_app=($count_arr_apps>1);
	$app_to_use=($count_arr_apps===1) ? $arr_apps[0] : ''; 

	//token
	$token=get_csrf_token();

	$use_misskey_note = $use_diary  ? ($adminpost||$admindel) : $use_misskey_note;
	$resmode=true;

	$page=0;

	//PCHアップロードの投稿可能な最大値
	$upload_max_filesize = get_upload_max_filesize() * 1024 * 1024; //byte単位に変換
	
	//フォームの表示時刻をセット
	set_form_display_time();

	$admin_pass= null;
	$templete= $res_catalog ? 'res_catalog.html' : 'res.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}
