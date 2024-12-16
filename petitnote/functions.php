<?php
$functions_ver=20241216;
//編集モードログアウト
function logout(){
	$resno=(int)filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	session_sta();
	unset($_SESSION['admindel']);
	unset($_SESSION['userdel']);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	$page=(int)filter_input(INPUT_POST,'page',FILTER_VALIDATE_INT);
	$page= $page ? $page : (int)filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);

	return header('Location: ./?page='.$page);
}
//管理者モードログアウト
function logout_admin(){
	session_sta();
	unset($_SESSION['admindel']);
	unset($_SESSION['adminpost']);

	return branch_destination_of_location();
}

//合言葉認証
function aikotoba(){
	global $aikotoba,$en,$keep_aikotoba_login_status;

	check_same_origin();

	session_sta();
	if(!$aikotoba || $aikotoba!==(string)filter_input(INPUT_POST,'aikotoba')){
		if(isset($_SESSION['aikotoba'])){
			unset($_SESSION['aikotoba']);
		}
		if((string)filter_input(INPUT_COOKIE,'aikotoba')){
			setcookie('aikotoba', '', time() - 3600);
		} 
		return error($en?'The secret word is wrong':'合言葉が違います。');
	}
	if($keep_aikotoba_login_status){
		setcookie("aikotoba",$aikotoba, time()+(86400*30),"","",false,true);//1ヶ月
	}

	$_SESSION['aikotoba']='aikotoba';

	// 処理が終了したらJavaScriptでリロード

}
//記事の表示に合言葉を必須にする
function aikotoba_required_to_view($required_flag=false){

	global $use_aikotoba,$aikotoba_required_to_view,$skindir,$en,$petit_lot,$boardname;

	$required_flag=($use_aikotoba && $required_flag);

	if(!$aikotoba_required_to_view && !$required_flag){
	return;
	}

	$page=(int)filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$resno=(int)filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	$admin_pass= null;

	if(!aikotoba_valid()){
		$templete='aikotoba.html';
		include __DIR__.'/'.$skindir.$templete;
		exit();//return include では処理が止まらない。 
	}
}

//管理者パスワードを確認
function is_adminpass($pwd){
	global $admin_pass,$second_pass;
	$pwd=(string)$pwd;
	return ($admin_pass && $pwd && $second_pass !== $admin_pass && $pwd === $admin_pass);
}

function admin_in(){

	global $boardname,$use_diary,$use_aikotoba,$petit_lot,$petit_ver,$skindir,$en,$latest_var;

	aikotoba_required_to_view();

	$page=(int)filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$resno=(int)filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	$catalog=(bool)filter_input(INPUT_GET,'catalog',FILTER_VALIDATE_BOOLEAN);
	$res_catalog=(bool)filter_input(INPUT_GET,'res_catalog',FILTER_VALIDATE_BOOLEAN);
	$search=(bool)filter_input(INPUT_GET,'search',FILTER_VALIDATE_BOOLEAN);
	$radio=(int)filter_input(INPUT_GET,'radio',FILTER_VALIDATE_INT);
	$imgsearch=(bool)filter_input(INPUT_GET,'imgsearch',FILTER_VALIDATE_BOOLEAN);
	$q=(string)filter_input(INPUT_GET,'q');

	session_sta();
	$admindel=admindel_valid();
	$aikotoba=aikotoba_valid();
	$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
	$adminpost=adminpost_valid();
	if(!$use_aikotoba){
		$aikotoba=true;
	}
	$admin_pass= null;
	// HTML出力
	$templete='admin_in.html';
	return include __DIR__.'/'.$skindir.$templete;
	
}
//合言葉を再確認	
function check_aikotoba(){
	global $en;
	if(!aikotoba_valid()){
		return error($en?'The secret word is wrong.':'合言葉が違います。');
	}
	return true;
}
//管理者投稿モード
function adminpost(){
	global $second_pass,$en;

	check_same_origin();
	check_password_input_error_count();
	session_sta();
	if(!is_adminpass(filter_input(INPUT_POST,'adminpass'))){
		if(isset($_SESSION['adminpost'])){
			unset($_SESSION['adminpost']);
		} 
		return error($en?'password is wrong.':'パスワードが違います。');
	}
	session_regenerate_id(true);

	$_SESSION['aikotoba']='aikotoba';
	$_SESSION['adminpost']=$second_pass;

	return branch_destination_of_location();
}

//管理者削除モード
function admin_del(){
	global $second_pass,$en;

	check_same_origin();
	check_password_input_error_count();

	session_sta();
	if(!is_adminpass(filter_input(INPUT_POST,'adminpass'))){
		if(isset($_SESSION['admindel'])){
			unset($_SESSION['admindel']);
		} 
		return error($en?'password is wrong.':'パスワードが違います。');
	}
	session_regenerate_id(true);

	$_SESSION['aikotoba']='aikotoba';
	$_SESSION['admindel']=$second_pass;

	return branch_destination_of_location();
}
//ユーザー削除モード
function userdel_mode(){

	session_sta();

	$page=(int)filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT);
	$_SESSION['userdel']='userdel_mode';
	$resno=(int)filter_input(INPUT_GET,'resno',FILTER_VALIDATE_INT);
	if($resno){
		return header('Location: ./?resno='.$resno);
	}
	return header('Location: ./?page='.$page);
}

//sessionの確認
function adminpost_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['adminpost'])&&($second_pass && $_SESSION['adminpost']===$second_pass);
}
function admindel_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['admindel'])&&($second_pass && $_SESSION['admindel']===$second_pass);
}
function userdel_valid(){
	session_sta();
	return isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
}
//合言葉の確認
function aikotoba_valid(){
	global $keep_aikotoba_login_status,$aikotoba;
	session_sta();
	$keep=$keep_aikotoba_login_status ? ($aikotoba && ($aikotoba===(string)filter_input(INPUT_COOKIE,'aikotoba'))
	) : false;
	return ($keep||isset($_SESSION['aikotoba'])&&($_SESSION['aikotoba']==='aikotoba'));
}

//センシティブコンテンツ
function view_nsfw(){

	$view=(bool)filter_input(INPUT_POST,'view_nsfw',FILTER_VALIDATE_BOOLEAN);
	if($view){
		setcookie("nsfwc",'on',time()+(60*60*24*30),"","",false,true);
	}

	return branch_destination_of_location();
}

//閲覧注意画像を隠す隠さない
function set_nsfw_show_hide(){

	$view=(bool)filter_input(INPUT_POST,'set_nsfw_show_hide');
	if($view){
		setcookie("p_n_set_nsfw_show_hide",true,time()+(60*60*24*365),"","",false,true);
	}else{
		setcookie("p_n_set_nsfw_show_hide",false,time()+(60*60*24*365),"","",false,true);
	}
}
function set_darkmode(){

	$darkmode=(bool)filter_input(INPUT_POST,'darkmode');
	if($darkmode){
		setcookie("p_n_set_darkmode","1",time()+(60*60*24*365),"","",false,true);
	}else{
		setcookie("p_n_set_darkmode","0",time()+(60*60*24*365),"","",false,true);
	}
}

//ログイン・ログアウト時のLocationを分岐
function branch_destination_of_location(){
	$page=(int)filter_input(INPUT_POST,'postpage',FILTER_VALIDATE_INT);
	$resno=(int)filter_input(INPUT_POST,'resno',FILTER_VALIDATE_INT);
	$resno= $resno ? $resno : (int)filter_input(INPUT_POST,'postresno',FILTER_VALIDATE_INT);
	$catalog=(bool)filter_input(INPUT_POST,'catalog',FILTER_VALIDATE_BOOLEAN);
	$search=(bool)filter_input(INPUT_POST,'search',FILTER_VALIDATE_BOOLEAN);
	$paintcom=(bool)filter_input(INPUT_POST,'paintcom',FILTER_VALIDATE_BOOLEAN);
	$res_catalog=(bool)filter_input(INPUT_POST,'res_catalog',FILTER_VALIDATE_BOOLEAN);

	if($paintcom){
		return header('Location: ./?mode=paintcom');
	}
	if($resno){
		if(!is_file(LOG_DIR.$resno.'.log')){
			return header('Location: ./');
		}
		$res_catalog = $res_catalog ? '&res_catalog=on' : ''; 
		return header('Location: ./?resno='.h($resno).$res_catalog);
	}
	if($catalog){
		return header('Location: ./?mode=catalog&page='.h($page));
	}
	if($search){
		$radio=(int)filter_input(INPUT_POST,'radio',FILTER_VALIDATE_INT);
		$imgsearch=(bool)filter_input(INPUT_POST,'imgsearch',FILTER_VALIDATE_BOOLEAN);
		$imgsearch=$imgsearch ? 'on' : 'off';
		$q=(string)filter_input(INPUT_POST,'q');
		
		return header('Location: ./?mode=search&page='.h($page).'&imgsearch='.h($imgsearch).'&q='.h($q).'&radio='.h($radio));
	}
	return header('Location: ./?page='.h($page));
}
//非同期通信の時にpaintcom()を呼び出すためのリダイレクト
function location_paintcom(){
	header('Location: ./?mode=paintcom');
}
// コンティニュー認証
function check_cont_pass(){

	global $en;

	check_same_origin();

	$no = (string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
	$id = (string)filter_input(INPUT_POST, 'time');//intの範囲外
	$pwd=t(filter_input(INPUT_POST, 'pwd'));//パスワードを取得
	$pwd=$pwd ? $pwd : t(filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード

	if(is_file(LOG_DIR."$no.log")){
		check_open_no($no);
		$rp=fopen(LOG_DIR."$no.log","r");
		while ($line = fgets($rp)) {
			if(!trim($line)){
				continue;
			}
			list($_no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$painttime,$log_md5,$tool,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=explode("\t",trim($line));
			if($id===$time && $no===$_no && $pwd && password_verify($pwd,$hash)){
				closeFile ($rp);
				return true;
			}
		}
		closeFile ($rp);
	}

	return error($en?'password is wrong.':'パスワードが違います。');
}

//設定済みのペイントツール名かどうか調べる
function is_paint_tool_name($tool){
	return in_array($tool,['neo','chi','klecks','tegaki','axnos']) ? $tool : '???';
}

//ログ出力の前処理 行から情報を取り出す
function create_res($line,$options=[]){
	global $root_url,$boardname,$do_not_change_posts_time,$en,$mark_sensitive_image;
	list($no,$sub,$name,$verified,$com,$url,$imgfile,$w,$h,$thumbnail,$paintsec,$log_hash_img,$abbr_toolname,$pchext,$time,$first_posted_time,$host,$userid,$hash,$oya)=$line;

	$time = basename($time);

	$isset_catalog = isset($options['catalog']);
	$isset_search = isset($options['search']);
	$res=[];

	$continue = true;
	$upload_image = false;
	$tool=switch_tool($abbr_toolname);

	if($abbr_toolname==='upload'){
		$continue = false;
		$upload_image = true;
	}

	$anime = in_array($pchext,['.pch','.tgkr']); 
	$hide_thumbnail = $mark_sensitive_image ? (strpos($thumbnail,'hide_')!==false) :'';

	$_w=$w;
	$_h=$h;
	if($hide_thumbnail){
		list($w,$h)=image_reduction_display($w,$h,300,300);
	}
	$thumbnail_webp = ((strpos($thumbnail,'thumbnail_webp')!==false)) ? $time.'s.webp' : false; 
	$thumbnail_jpg = (!$thumbnail_webp && strpos($thumbnail,'thumbnail')!==false) ? $time.'s.jpg' : false; 

	$thumbnail_img = $thumbnail_webp ? $thumbnail_webp : $thumbnail_jpg;

	$link_thumbnail= ($thumbnail_img || $hide_thumbnail); 
	$painttime = !$isset_catalog ? calcPtime($paintsec) : false;

	$datetime = $do_not_change_posts_time ? microtime2time($first_posted_time) : microtime2time($time);
	$date=$datetime ? date('y/m/d',(int)$datetime):'';

	$check_elapsed_days = !$isset_catalog ? check_elapsed_days($time) : true;//念のためtrueに
	$verified = ($verified==='adminpost');
	$three_point_sub = ($isset_catalog && (mb_strlen($sub)>15)) ? '…' :'';
	$webpimg = is_file('webp/'.$time.'t.webp');
	$com = (!$isset_catalog || $isset_search) ? $com : '';

	$res=[
		'no' => $no,
		'sub' => $sub,
		'substr_sub' => $isset_catalog ? mb_substr($sub,0,15).$three_point_sub : $sub,
		'name' => $name,
		'verified' => $verified,
		'com' => $com,
		'url' => $url ? filter_var($url,FILTER_VALIDATE_URL) : '',
		'img' => $imgfile,
		'thumbnail' => $thumbnail_img,//webp or jpegのサムネイルのファイル名
		'painttime' => $painttime ? $painttime['ja'] : '',
		'painttime_en' => $painttime ? $painttime['en'] : '',
		'paintsec' => $paintsec,
		'w' => ($w && is_numeric($w)) ? $w :'',
		'h' => ($h && is_numeric($h)) ? $h :'',
		'_w' => ($_w && is_numeric($_w)) ? $_w :'',
		'_h' => ($_h && is_numeric($_h)) ? $_h :'',
		'tool' => $tool,
		'abbr_toolname' => $abbr_toolname,
		'upload_image' => $upload_image,
		'pchext' => $pchext,
		'anime' => $anime,
		'continue' => $check_elapsed_days ? $continue : (adminpost_valid() ? $continue : false),
		'time' => $time,
		'date' => $date,
		'datetime' => $datetime,
		'host' => admindel_valid() ? $host : '',
		'userid' => $userid,
		'check_elapsed_days' => $check_elapsed_days,
		'encoded_boardname' => $isset_catalog ? urlencode($boardname) : '',
		'encoded_name' => (!$isset_catalog || $isset_search) ? urlencode($name) : '',
		'encoded_no' => !$isset_catalog ? urlencode('['.$no.']') : '',
		'encoded_sub' => !$isset_catalog ? urlencode($sub) : '',
		'encoded_u' => !$isset_catalog ? urlencode($root_url.'?resno='.$no) : '',//tweet
		'encoded_t' => !$isset_catalog ? urlencode('['.$no.']'.$sub.($name ? ' by '.$name : '').' - '.$boardname) : '',
		'oya' => $oya,
		'webpimg' => $webpimg ? 'webp/'.$time.'t.webp' :false,
		'hide_thumbnail' => $hide_thumbnail, //サムネイルにぼかしをかける時
		'link_thumbnail' => $link_thumbnail, //サムネイルにリンクがある時
		'not_deleted' => !(!$name && !$com && !$url&& !$imgfile && !$userid), //表示する記事がある親
	];

	$res['com']= $com ? (!$isset_search ? str_replace('"\n"',"\n",$res['com']) : str_replace('"\n"'," ",$res['com'])) : '';

	foreach($res as $key=>$val){
		$res[$key]=h($val);
	}

	return $res;
}

function switch_tool($tool){
	global $en;
	switch($tool){
		case 'neo':
			$tool='PaintBBS NEO';
			break;
		case 'PaintBBS':
			$tool='PaintBBS';
			break;
		case 'shi-Painter':
			$tool='Shi-Painter';
			break;
		case 'chi':
			$tool='ChickenPaint';
			break;
		case 'klecks';
			$tool='Klecks';
			break;
		case 'tegaki';
			$tool='Tegaki';
			break;
		case 'axnos';
			$tool='Axnos Paint';
			break;
		case 'upload':
			$tool=$en?'Upload':'アップロード';
			break;
		default:
			$tool='';
			break;
	}
	return $tool;
}

//重複チェックのための配列を全体ログを元に作成
function create_chk_lins($chk_log_arr,$resno){

	$chk_resnos=[];
	foreach($chk_log_arr as $chk_log){
		list($chk_resno)=explode("\t",$chk_log);
		$chk_resnos[]=$chk_resno;
	}
	$chk_lines=[];
	//条件分岐で新規投稿に変更になった時のエラー回避
	foreach($chk_resnos as $chk_resno){
		//$resnoのログファイルは開かない
		if(($chk_resno!==$resno)&&is_file(LOG_DIR."{$chk_resno}.log")){
			check_open_no($chk_resno);
			$cp=fopen(LOG_DIR."{$chk_resno}.log","r");
			while($line=fgets($cp)){
				if(!trim($line)){
					continue;
				}
				$chk_lines[]=$line;
			}
			closefile($cp);
		}
	}
	return $chk_lines;
}

//ファイル名が重複しない投稿時刻を作成
function create_post_time(){
	$time = (string)(time().substr(microtime(),2,6));	//投稿時刻
	//画像重複チェック
	$testexts=['.gif','.jpg','.png','.webp'];
	foreach($testexts as $testext){
		if(is_file(IMG_DIR.$time.$testext)){
			$time=(string)(substr($time,0,-6)+1).(string)substr($time,-6);
		break;	
		}
	}
	//一時ファイル重複チェック
	$time= is_file(TEMP_DIR.$time.'.tmp') ?	(string)(substr($time,0,-6)+1).(string)substr($time,-6) : $time;
	$time=basename($time);
	return $time;
}

//ログファイルを1行ずつ読み込んで配列に入れる
function create_array_from_fp($fp){
	global $en;
	if(!$fp){
		return error($en?'This operation has failed.':'失敗しました。');
	}
	$arr=[];
	while ($lines = fgets($fp)) {
		if(!trim($lines)){
			continue;
		}
		$arr[]=$lines;
	}
	return $arr;
}

//ページング
function calc_pagination_range($page,$pagedef){

	$start_page=$page-$pagedef*8;
	$end_page=$page+($pagedef*8);
	if($page<$pagedef*17){
		$start_page=0;
		$end_page=$pagedef*17;
	}
	return [$start_page,$end_page];	
}	

//ユーザーip
function get_uip(){
	$ip = isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] :'';
	$ip = $ip ? $ip : (isset($_SERVER["HTTP_INCAP_CLIENT_IP"]) ? $_SERVER["HTTP_INCAP_CLIENT_IP"] : '');
	$ip = $ip ? $ip : (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : '');
	$ip = $ip ? $ip : (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '');
	if (strstr($ip, ', ')) {
		$ips = explode(', ', $ip);
		$ip = $ips[0];
	}
	return $ip;
}

//タブ除去
function t($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	$str=(string)$str;
	return str_replace("\t","",$str);
}
//タグ除去
function s($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	return strip_tags($str);
}
//エスケープ
function h($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	return htmlspecialchars($str,ENT_QUOTES,"utf-8",false);
}
//コメント出力
function com($str,$verified=false){
	global $use_autolink;

	if(!$str){
		return '';
	}

	if($use_autolink){
	$str=md_link($str,$verified);
	$str=auto_link($str,$verified);
	}
	return nl2br($str,false);

}

//マークダウン記法のリンクをHTMLに変換
function md_link($str, $verified = false) {
	$rel = $verified ? 'rel="noopener noreferrer"' : 'rel="nofollow noopener noreferrer"';

	// 正規表現パターンを使用してマークダウンリンクを検出
	$pattern = "{\[((?:[^\[\]\\\\]|\\\\.)+?)\]\((https?://[^\s\)]+)\)}";

	// 変換処理
	$str = preg_replace_callback($pattern, function($matches) use ($rel) {
			// エスケープされたバックスラッシュを特定の文字だけ解除
			$text = str_replace(['\\[', '\\]', '\\(', '\\)'], ['[', ']', '(', ')'], $matches[1]);
			$url = filter_var($matches[2], FILTER_VALIDATE_URL) ? $matches[2] : '';
			// 変換されたHTMLリンクを返す
			if(!$url){
				 // URLが無効ならテキストだけ返す
				return $text;
			}
			// URLが有効ならHTMLリンクを返す
			return '<a href="'.$url.'" target="_blank" '.$rel.'>'.$text.'</a>';
	}, $str);

	return $str;
}

// 自動リンク
function auto_link($str, $verified = false){
	if(strpos($str, '<a') === false){ // マークダウン記法がなかった時
		if($verified){
			$rel = 'rel="noopener noreferrer"';
		}else{
			$rel = 'rel="nofollow noopener noreferrer"';
		}
		$str= preg_replace("{(https?://[\w!\?/\+\-_~=;:\.,\*&@#\$%\(\)'\[\]]+)}",'<a href="$1" target="_blank" '.$rel.'>$1</a>',$str);
	}
		return $str;
}

//mime typeを取得して拡張子を返す
function get_image_type ($img_file) {

	$img_type = mime_content_type($img_file);

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
/**
 * 一連の画像ファイルを削除（元画像、サムネ、動画）
 * @param $path
 * @param $filename
 * @param $ext
 */
function delete_files ($imgfile, $time) {

	$imgfile=basename($imgfile);
	$time=basename($time);
	safe_unlink(IMG_DIR.$imgfile);
	safe_unlink(THUMB_DIR.$time.'s.jpg');
	safe_unlink(THUMB_DIR.$time.'s.webp');
	safe_unlink('webp/'.$time.'t.webp');
	safe_unlink(IMG_DIR.$time.'.pch');
	safe_unlink(IMG_DIR.$time.'.spch');
	safe_unlink(IMG_DIR.$time.'.chi');
	safe_unlink(IMG_DIR.$time.'.psd');
	safe_unlink(IMG_DIR.$time.'.tgkr');
	delete_res_cache();
}

function delete_res_cache () {
	safe_unlink(__DIR__.'/template/cache/index_cache.json');
}

//pngをwebpに変換してみてファイル容量が小さくなっていたら元のファイルを上書き
function convert_andsave_if_smaller_png2webp($is_upload_img,$fname,$time){
	global $max_kb,$max_file_size_in_png_format_paint,$max_file_size_in_png_format_upload;
	$upfile=TEMP_DIR.basename($fname);

	clearstatcache();
	$filesize=filesize($upfile);
	$max_kb_size_over = ($filesize > ($max_kb * 1024));
	if(mime_content_type($upfile)!=="image/png" && !$max_kb_size_over){
		return;//ファイルサイズが$max_kbを超えている時は形式にかかわらず処理続行
	}
	if(((!$is_upload_img && $filesize < ($max_file_size_in_png_format_paint * 1024))||	
	($is_upload_img && $filesize < ($max_file_size_in_png_format_upload * 1024))) && !$max_kb_size_over){
		return;
	}
	//webp作成が可能ならwebpに、でなければjpegに変換する。
	$im_webp = thumbnail_gd::thumb(TEMP_DIR,$fname,$time,null,null,['png2webp'=>true]);

	if($im_webp){
		clearstatcache();
		if(filesize($im_webp)<$filesize){//webpのほうが小さい時だけ
			rename($im_webp,$upfile);//webpで保存
			chmod($upfile,0606);
		} else{//pngよりファイルサイズが大きくなる時は
			unlink($im_webp);//作成したwebp画像を削除
		}
	}
}

//Exifをチェックして画像が回転している時と位置情報が付いている時は上書き保存
function check_jpeg_exif($upfile){
	global $max_px;

	if((exif_imagetype($upfile) !== IMAGETYPE_JPEG ) || !function_exists("imagecreatefromjpeg")){
		return;
	}

	//画像回転の検出
	$exif = exif_read_data($upfile);
	$orientation = isset($exif["Orientation"]) ? $exif["Orientation"] : 1;
	//位置情報はあるか?
	$gpsdata_exists =(isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])); 

	if ($orientation === 1 && !$gpsdata_exists) {
		//画像が回転していない、位置情報も存在しない
		return;
	}

	list($w,$h) = getimagesize($upfile);

	$im_in = imagecreatefromjpeg($upfile);
	if(!$im_in){
		return;
	}
	switch ($orientation) {
		case 3:
			$im_in = imagerotate($im_in, 180, 0);
			break;
		case 6:
			$im_in = imagerotate($im_in, -90, 0);
			break;
		case 8:
			$im_in = imagerotate($im_in, 90, 0);
			break;
		default:
			break;
	}
	if(!$im_in){
		return;
	}
	if ($orientation === 6 || $orientation === 8) {
		// 90度または270度回転の場合、幅と高さを入れ替える
		list($w, $h) = [$h, $w];
	}
	$w_ratio = $max_px / $w;
	$h_ratio = $max_px / $h;
	$ratio = min($w_ratio, $h_ratio);
	$out_w = ceil($w * $ratio);//端数の切り上げ
	$out_h = ceil($h * $ratio);
	$im_out = $im_in;//縮小しない時
	//JPEG形式で何度も保存しなおすのを回避するため、
	//指定範囲内にリサイズしておく。
	if(function_exists("ImageCreateTrueColor") && function_exists("ImageCopyResampled")){
		$im_out = ImageCreateTrueColor($out_w, $out_h);
		ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $w, $h);
	}
	// 画像を保存
	imagejpeg($im_out, $upfile,98);
	// 画像のメモリを解放
	imagedestroy($im_in);
	imagedestroy($im_out);
}

//サムネイル作成
function make_thumbnail($imgfile,$time,$max_w,$max_h){
	global $use_thumb; 
	$thumbnail='';
	if($use_thumb){//スレッドの画像のサムネイルを使う時
		if(thumbnail_gd::thumb(IMG_DIR,$imgfile,$time,$max_w,$max_h,['thumbnail_webp'=>true])){
			$thumbnail='thumbnail_webp';
		}
		//webpのサムネイルが作成できなかった時はjpegのサムネイルを作る
		if(!$thumbnail && thumbnail_gd::thumb(IMG_DIR,$imgfile,$time,$max_w,$max_h)){
			$thumbnail='thumbnail';
		}
	}
	//カタログ用webpサムネイル 
	thumbnail_gd::thumb(IMG_DIR,$imgfile,$time,300,800,['webp'=>true]);

	return $thumbnail;
}

//アップロード画像のファイルサイズが大きすぎる時は削除
function delete_file_if_sizeexceeds($upfile,$fp,$rp){
	global $max_kb,$en;
	clearstatcache();
	if(filesize($upfile) > $max_kb*1024){
		closeFile($fp);
		closeFile($rp);
		safe_unlink($upfile);
	return error($en? "Upload failed.\nFile size exceeds {$max_kb}kb.":"アップロードに失敗しました。\nファイル容量が{$max_kb}kbを超えています。");
	}
}

function error($str,$historyback=true){

	global $boardname,$skindir,$en,$aikotoba_required_to_view,$petit_lot;

	$asyncflag = (bool)filter_input(INPUT_POST,'asyncflag',FILTER_VALIDATE_BOOLEAN);
	$http_x_requested_with= (bool)(isset($_SERVER['HTTP_X_REQUESTED_WITH']));
	if($http_x_requested_with||$asyncflag){
		header('Content-type: text/plain');
		return die(h("error\n{$str}"));
	}
	$boardname = ($aikotoba_required_to_view && !aikotoba_valid()) ? '' : $boardname; 

	$admin_pass= null;

	$templete='error.html';
	include __DIR__.'/'.$skindir.$templete;
	exit();
}
//csrfトークンを作成
function get_csrf_token(){
	session_sta();
	$token=hash('sha256', session_id(), false);
	$_SESSION['token']=$token;

	return $token;
}
//csrfトークンをチェック	
function check_csrf_token(){
	global $en;

	if(($_SERVER["REQUEST_METHOD"]) !== "POST"){
		return error($en?'This operation has failed.':'失敗しました。');
	} 
	check_same_origin();
	session_sta();
	$token=(string)filter_input(INPUT_POST,'token');
	$session_token=isset($_SESSION['token']) ? (string)$_SESSION['token'] : '';
	if(!$session_token||$token!==$session_token){
		return error($en?"CSRF token mismatch.\nPlease reload.":"CSRFトークンが一致しません。\nリロードしてください。");
	}
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

function check_same_origin(){
	global $en,$usercode;

	session_sta();
	$c_usercode = t(filter_input(INPUT_COOKIE, 'usercode'));//user-codeを取得
	$session_usercode = isset($_SESSION['usercode']) ? t($_SESSION['usercode']) : "";
	if(!$c_usercode){
		return error($en?'Cookie check failed.':'Cookieが確認できません。');
	}
	if(!$usercode || ($usercode!==$c_usercode)&&($usercode!==$session_usercode)){
		return error($en?"User code mismatch.":"ユーザーコードが一致しません。");
	}
	if(!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_HOST'])){
		return error($en?'Your browser is not supported. ':'お使いのブラウザはサポートされていません。');
	}
	if(parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']){
		return error($en?"The post has been rejected.":'拒絶されました。');
	}
}

function check_open_no($no){
	global $en;
	if(!is_numeric($no)){
		return error($en?'This operation has failed.':'失敗しました。');
	}
}

function getId ($userip) {

	session_sta();
	return 
	(isset($_SESSION['userid'])&&$_SESSION['userid']) ?
	$_SESSION['userid'] :
	substr(hash('sha256', $userip, false),-8);

}

//Asyncリクエストの時は処理を中断
function check_AsyncRequest($upfile='') {
	//ヘッダーが確認できなかった時の保険
	$asyncflag = (bool)filter_input(INPUT_POST,'asyncflag',FILTER_VALIDATE_BOOLEAN);
	$paint_picrep = (bool)filter_input(INPUT_POST,'paint_picrep',FILTER_VALIDATE_BOOLEAN);
	$http_x_requested_with= (bool)(isset($_SERVER['HTTP_X_REQUESTED_WITH']));
	//Paintの画像差し換えの時はAsyncリクエストを継続
	if(!$paint_picrep && ($http_x_requested_with || $asyncflag)){//非同期通信ならエラーチェックだけすませて処理中断。通常フォームでやりなおし。
		safe_unlink($upfile);
		exit();
	}
}

// テンポラリ内のゴミ除去 
function deltemp(){
	global $check_password_input_error_count;
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file)) {
			$file=basename($file);
			//pchアップロードペイントファイル削除
			//仮差し換えアップロードファイル削除
			$lapse = time() - filemtime(TEMP_DIR.$file);
			if(strpos($file,'pchup-')===0){
				if($lapse > (300)){//5分
					safe_unlink(TEMP_DIR.$file);
				}
			}else{
				if($lapse > (3*24*3600)){//3日
					safe_unlink(TEMP_DIR.$file);
				}
			}
		}
	}
	closedir($handle);
	$_file=__DIR__.'/template/errorlog/error.log';
	if(!$check_password_input_error_count){
		safe_unlink($_file);
	}
	if(is_file($_file)){
		$lapse = time() - filemtime($_file);
		if($lapse > (3*24*3600)){//3日
			safe_unlink($_file);
		}
	}
}

// NGワードがあれば拒絶
function Reject_if_NGword_exists_in_the_post(){
	global $use_japanesefilter,$badstring,$badname,$badurl,$badstr_A,$badstr_B,$allow_comments_url,$max_com,$en;

	$admin =(adminpost_valid()||admindel_valid());

	$name = t(filter_input(INPUT_POST,'name'));
	$sub = t(filter_input(INPUT_POST,'sub'));
	$url = t(filter_input(INPUT_POST,'url',FILTER_VALIDATE_URL));
	$com = t(filter_input(INPUT_POST,'com'));
	$pwd = t(filter_input(INPUT_POST,'pwd'));

	if($admin || is_adminpass($pwd)){
		return;
	}
	if(is_badhost()){
		return error($en?'Post was rejected.':'拒絶されました。');
	}

	$com_len=strlen((string)$com);
	$name_len=strlen((string)$name);
	$sub_len=strlen((string)$sub);
	$url_len=strlen((string)$url);
	$pwd_len=strlen((string)$pwd);

	if($name_len > 30) return error($en?'Name is too long':'名前が長すぎます。');
	if($sub_len > 80) return error($en? 'Subject is too long.':'題名が長すぎます。');
	if($url_len > 100) return error($en? 'URL is too long.':'URLが長すぎます。');
	if($com_len > $max_com) return error($en? 'Comment is too long.':'本文が長すぎます。');
	if($pwd_len > 100) return error($en? 'Password is too long.':'パスワードが長すぎます。');

	//チェックする項目から改行・スペース・タブを消す
	$chk_name = $name_len ? preg_replace("/\s/u", "", $name ) : '';
	$chk_sub = $sub_len ? preg_replace("/\s/u", "", $sub ) : '';
	$chk_url = $url_len ? preg_replace("/\s/u", "", $url ) : '';
	$chk_com  = $com_len ? preg_replace("/\s/u", "", $com ) : '';

	//本文に日本語がなければ拒絶
	if ($use_japanesefilter) {
		mb_regex_encoding("UTF-8");
		if ($com_len && !preg_match("/[ぁ-んァ-ヶー一-龠]+/u",$chk_com)) return error($en?'Comment should have at least some Japanese characters.':'日本語で何か書いてください。');
	}

	//本文へのURLの書き込みを禁止
	if(!$allow_comments_url){
		if($com_len && preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) return error($en?'This URL can not be used in text.':'URLの記入はできません。');
	}

	// 使えない文字チェック
	if (is_ngword($badstring, [$chk_name,$chk_sub,$chk_url,$chk_com])) {
		return error($en?'There is an inappropriate string.':'不適切な表現があります。');
	}

	// 使えない名前チェック
	if (is_ngword($badname, $chk_name)) {
		return error($en?'This name cannot be used.':'この名前は使えません。');
	}
	// 使えないurlチェック
	if (is_ngword($badurl, $chk_url)) {
		return error($en?'There is an inappropriate URL.':'不適切なURLがあります。');
	}

	//指定文字列が2つあると拒絶
	$bstr_A_find = is_ngword($badstr_A, [$chk_com, $chk_sub, $chk_name]);
	$bstr_B_find = is_ngword($badstr_B, [$chk_com, $chk_sub, $chk_name]);
	if($bstr_A_find && $bstr_B_find){
		return error($en?'There is an inappropriate string.':'不適切な表現があります。');
	}
}
/**
 * NGワードチェック
 * @param $ngwords
 * @param string|array $strs
 * @return bool
 */
function is_ngword ($ngwords, $strs) {
	if (empty($ngwords)||empty($strs)) {
		return false;
	}
	$strs = (array)$strs;//配列に変換
	foreach($ngwords as $i => $ngword){//拒絶する文字列
		$ngwords[$i]  = str_replace([" ", "　"], "", $ngword);
		$ngwords[$i]  = str_replace("/", "\/", $ngwords[$i]);
	}
	foreach ($strs as $str) {
		foreach($ngwords as $ngword){//拒絶する文字列
			if ($ngword && preg_match("/{$ngword}/ui", $str)){
				return true;
			}
		}
	}
	return false;
}

/* 禁止ホストチェック */
function is_badhost(){
	global $badhost;
	//ホスト取得
	$userip = get_uip();
	$host = $userip ? gethostbyaddr($userip) :'';

	if($host === $userip){//ホスト名がipアドレスになる場合は
		foreach($badhost as $value){
			if (preg_match("/\A$value/i",$host)) {//前方一致
				return true;
			}
		}
		return false;
	}else{
		foreach($badhost as $value){
			if (preg_match("/$value\z/i",$host)) {
				return true;
			}
		}
		return false;
	}
}

//初期化
function init(){
	
	check_dir(__DIR__."/src");
	check_dir(__DIR__."/temp");
	check_dir(__DIR__."/thumbnail");
	check_dir(__DIR__."/log");
	check_dir(__DIR__."/webp");
	check_dir(__DIR__."/template/cache");
	if(!is_file(LOG_DIR.'alllog.log')){
	file_put_contents(LOG_DIR.'alllog.log','',FILE_APPEND|LOCK_EX);
	chmod(LOG_DIR.'alllog.log',0600);	
	}
}

//ディレクトリ作成
function check_dir ($path) {

	$msg=initial_error_message();

	if (!is_dir($path)) {
			mkdir($path, 0707);
			chmod($path, 0707);
	}
	if (!is_dir($path)) return die(h($path) . $msg['001']);
	if (!is_readable($path)) return die(h($path) . $msg['002']);
	if (!is_writable($path)) return die(h($path) . $msg['003']);
}

// ファイル存在チェック
function check_file ($path) {
	$msg=initial_error_message();

	if (!is_file($path)) return die(h($path) . $msg['001']);
	if (!is_readable($path)) return die(h($path) . $msg['002']);
}
function initial_error_message(){
	global $en;
	$msg['001']=$en ? ' does not exist.':'がありません。'; 
	$msg['002']=$en ? ' is not readable.':'を読めません。'; 
	$msg['003']=$en ? ' is not writable.':'を書けません。'; 
return $msg;	
}

// 一括書き込み（上書き）
function writeFile ($fp, $data) {
	ftruncate($fp,0);
	rewind($fp);
	stream_set_write_buffer($fp, 0);
	fwrite($fp, $data);
}
//fpクローズ
function closeFile ($fp) {
	if($fp){
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}

//縮小表示
function image_reduction_display($w,$h,$max_w,$max_h){
	if(!is_numeric($w)||!is_numeric($h)){
		return ['',''];
	}

	if ($w > $max_w || $h > $max_h) {
		$w_ratio = $max_w / $w;
		$h_ratio = $max_h / $h;
		$ratio = min($w_ratio, $h_ratio);
		$w = ceil($w * $ratio);
		$h = ceil($h * $ratio);
	}
	$reduced_size = [$w,$h];
	return $reduced_size;
}
/**
 * 描画時間を計算
 * @param $starttime
 * @return string
 */
function calcPtime ($psec) {

	if(!is_numeric($psec)){
		return false;
	}

	$D = floor($psec / 86400);
	$H = floor($psec % 86400 / 3600);
	$M = floor($psec % 3600 / 60);
	$S = $psec % 60;

	$en_day = ($D>1) ? ' days ' : ' day ';
	$en_hour = ($H>1) ? ' hours ' : ' hour '; 
	$result=[
		'ja'=>
			($D ? $D.'日' : '')
			. (($D||$H) ? $H.'時間' : '')
			. (($D||$H||$M) ? $M.'分' : '')
			. $S.'秒'
			,
		'en'=>
			($D ? $D.$en_day : '')
			. (($D||$H) ? $H.$en_hour : '')
			. (($D||$H||$M) ? $M.' min ' : '')
			. $S.' sec'
		];
	return $result;
}
/**
 * 残り時間を計算
 * @param $starttime
 * @return string
 */
function calc_remaining_time_to_close_thread ($sec) {
	global $en;

	$D = floor($sec / 86400);
	$H = floor($sec % 86400 / 3600);
	$M = floor($sec % 3600 / 60);

	if($D){
		$day = ($D>1) ? ' days' : ' day';
		$day = $en ? $day : '日';

		return (int)$D.$day;
	}
	if($H){
		$hour = ($H>1) ? ' hours' : ' hour'; 
		$hour = $en ? $hour : '時間';

		return  (int)$H.$hour;
	}
	$min = $en ? ' min' : '分';

	return  (int)$M.$min;
}

/**
 * pchかtgkrかchiかpsdか、それともファイルが存在しないかチェック
 * @param $filepath
 * @return string
 */
function check_pch_ext ($filepath,$options = []) {
	
	$exts=[".pch",".tgkr",".chi",".psd"];

	foreach($exts as $i => $ext){

		if (is_file($filepath . $ext)) {
			if(!in_array(mime_content_type($filepath . $ext),["application/octet-stream","image/vnd.adobe.photoshop"])){
				return '';
			}
			return $ext;
		}
		if(!isset($options['upload']) && $i === 1){
			return '';
		}
	}
	return '';
}

// 古いスレッドへの投稿を許可するかどうか
function check_elapsed_days ($postedtime) {
	global $elapsed_days;
	$postedtime=microtime2time($postedtime);//マイクロ秒を秒に戻す
	return $elapsed_days //古いスレッドのフォームを閉じる日数が設定されていたら
		? ((time() - (int)$postedtime) <= ((int)$elapsed_days * 86400)) // 指定日数以内なら許可
		: true; // フォームを閉じる日数が未設定なら許可
}
// スレッドを閉じるまでの残り時間
function time_left_to_close_the_thread ($postedtime) {
	global $elapsed_days;
	if(!$elapsed_days){
		return false;
	}
	$postedtime=microtime2time($postedtime);//マイクロ秒を秒に戻す
	$timeleft=((int)$elapsed_days * 86400)-(time() - (int)$postedtime);
	//残り時間が60日を切ったら表示
	return ($timeleft<(60 * 86400)) ? 
	calc_remaining_time_to_close_thread($timeleft) : false;
}	
// マイクロ秒を秒に戻す
function microtime2time($microtime){
	$microtime=(string)$microtime;
	$time=(strlen($microtime)>15) ? substr($microtime,0,-6) : substr($microtime,0,-3);
	return $time;
}

//POSTされた値をログファイルに格納する書式にフォーマット
function create_formatted_text_from_post($name,$sub,$url,$com){
	global $en,$name_input_required,$subject_input_required;

	if(!$name||preg_match("/\A\s*\z/u",$name)) $name="";
	if(!$sub||preg_match("/\A\s*\z/u",$sub))   $sub="";
	if(!$url||!(string)filter_var($url,FILTER_VALIDATE_URL)||!preg_match('{\Ahttps?://}', $url)||preg_match("/\A\s*\z/u",$url)) $url="";
	if(!$com||preg_match("/\A\s*\z/u",$com)) $com="";
	$com=str_replace(["\r\n","\r"],"\n",$com);
	$com = preg_replace("/(\s*\n){4,}/u","\n",$com); //不要改行カット
	$com=str_replace("\n",'"\n"',$com);
	if(!$name){
		if($name_input_required){
			return error($en?'Please enter your name.':'名前がありません。');
		}else{
			$name='anonymous';
		}
	}
	if(!$sub){
		if($subject_input_required){
			return error($en?'Please enter subject.':'題名がありません。');
		}else{
			$sub= $en ? 'No subject':'無題';
		}
	}
	$formatted_post=[
		'name'=>$name,
		'sub'=>$sub,
		'url'=>$url,
		'com'=>$com,
	];
	foreach($formatted_post as $key => $val){
		$formatted_post[$key]=str_replace(["\r\n","\n","\r","\t"],"",$val);//改行コード一括除去
	}
	return $formatted_post;

}

//検索文字列をフォーマット
function create_formatted_text_for_search($str){

	$s_str=mb_convert_kana($str, 'rn', 'UTF-8');//全角英数を半角に
	$s_str=str_replace([" ", "　"], "", $s_str);
	$s_str=str_replace("〜","～", $s_str);//波ダッシュを全角チルダに
	$s_str=strtolower($s_str);//小文字に

	return $s_str; 
}

//PaintBBS NEOのpchかどうか調べる
function is_neo($src) {
	$fp = fopen("$src", "rb");
	$is_neo=(fread($fp,3)==="NEO");
	fclose($fp);
	return $is_neo;
}
//pchデータから幅と高さを取得
function get_pch_size($src) {
	if(!$src){
		return;
	}
	$fp = fopen("$src", "rb");
	$is_neo=(fread($fp,3)==="NEO");//ファイルポインタが3byte移動
	$pch_data=(string)bin2hex(fread($fp,5));
	fclose($fp);
	if(!$is_neo || !$pch_data){
		return;
	}
	$width=null;
	$height=null;
	$w0=hexdec(substr($pch_data,2,2));
	$w1=hexdec(substr($pch_data,4,2));
	$h0=hexdec(substr($pch_data,6,2));
	$h1=hexdec(substr($pch_data,8,2));
	if(!is_numeric($w0)||!is_numeric($w1)||!is_numeric($h0)||!is_numeric($h1)){
		return;
	}
	$width=(int)$w0+((int)$w1*256);
	$height=(int)$h0+((int)$h1*256);
	if(!$width||!$height){
		return;
	}
	return[(int)$width,(int)$height];
}

//使用するペイントアプリの配列化
function app_to_use(){
	global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$use_tegaki,$use_axnos;
		$arr_apps=[];
		if($use_paintbbs_neo){
			$arr_apps[]='neo';
		}
		if($use_chickenpaint){
			$arr_apps[]='chi';
		}
		if($use_klecs){
			$arr_apps[]='klecks';
		}
		if($use_tegaki){
			$arr_apps[]='tegaki';
		}
		if($use_axnos){
			$arr_apps[]='axnos';
		}
		return $arr_apps;
	}

//パスワードを5回連続して間違えた時は拒絶
function check_password_input_error_count(){
	global $second_pass,$en,$check_password_input_error_count;
	if(!$check_password_input_error_count){
		return;
	}
	$file=__DIR__.'/template/errorlog/error.log';
	$userip = get_uip();
	check_dir(__DIR__.'/template/errorlog/');
	$arr_err=is_file($file) ? file($file):[];
	if(count($arr_err)>=5){
		error($en?'Rejected.':'拒絶されました。');
	}
	if(!is_adminpass(filter_input(INPUT_POST,'adminpass'))){

		$errlog=$userip."\n";
		file_put_contents($file,$errlog,FILE_APPEND);
		chmod($file,0600);
	}else{
			safe_unlink($file);
	}
}

// 優先言語のリストをチェックして対応する言語があればその翻訳されたレイヤー名を返す
function getTranslatedLayerName() {
	$acceptedLanguages = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
	$languageList = explode(',', $acceptedLanguages);

	foreach ($languageList as $language) {
		$language = strtolower(trim($language));
		if (strpos($language, 'ja') === 0) {
			return "レイヤー";
		}
		if (strpos($language, 'en') === 0) {
			return "Layer";
		}
		if (strpos($language, 'zh-tw') === 0) {
			return "圖層";
		}
		if (strpos($language, 'zh-cn') === 0) {
			return "图层";
		}
		if (strpos($language, 'ko') === 0) {
			return "레이어";
		}
		if (strpos($language, 'fr') === 0) {
			return "Calque";
		}
		if (strpos($language, 'de') === 0) {
			return "Ebene";
		}
	}

	return "Layer";
}
//SNSへ共有リンクを送信
function post_share_server(){
	global $en;

	$sns_server_radio=(string)filter_input(INPUT_POST,"sns_server_radio",FILTER_VALIDATE_URL);
	$sns_server_radio_for_cookie=(string)filter_input(INPUT_POST,"sns_server_radio");//directを判定するためurlでバリデーションしていない
	$sns_server_radio_for_cookie=($sns_server_radio_for_cookie === 'direct') ? 'direct' : $sns_server_radio;
	$sns_server_direct_input=(string)filter_input(INPUT_POST,"sns_server_direct_input",FILTER_VALIDATE_URL);
	$encoded_t=(string)filter_input(INPUT_POST,"encoded_t");
	$encoded_t=urlencode($encoded_t);
	$encoded_u=(string)filter_input(INPUT_POST,"encoded_u");
	$encoded_u=urlencode($encoded_u);
	setcookie("sns_server_radio_cookie",$sns_server_radio_for_cookie, time()+(86400*30),"","",false,true);
	setcookie("sns_server_direct_input_cookie",$sns_server_direct_input, time()+(86400*30),"","",false,true);
	$share_url='';
	if($sns_server_radio){
		$share_url=$sns_server_radio."/share?text=";
	}elseif($sns_server_direct_input){
		$share_url=$sns_server_direct_input."/share?text=";
	}
	if(in_array($sns_server_radio,["https://x.com","https://twitter.com"])){
		// $share_url="https://x.com/intent/post?text=";
		$share_url="https://twitter.com/intent/tweet?text=";
	}
	if(in_array("https://bsky.app",[$sns_server_radio,$sns_server_direct_input])){
		$share_url="https://bsky.app/intent/compose?text=";
	}
	if(in_array("https://www.threads.net",[$sns_server_radio,$sns_server_direct_input])){
		$share_url="https://www.threads.net/intent/post?text=";
	}
	$share_url.=$encoded_t.'%20'.$encoded_u;
	$share_url = filter_var($share_url, FILTER_VALIDATE_URL) ? $share_url : ''; 
	if(!$share_url){
		error($en ? "Please select an SNS sharing destination.":"SNSの共有先を選択してください。");
	}
	return header('Location:'.$share_url);
}
