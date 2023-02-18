<?php
// thumbnail_gd.php for PetitNote (C)さとぴあ 2021 - 2023
// originalscript (C)SakaQ 2005 >> http://www.punyu.net/php/
//230217 webpサムネイル作成オプションを追加。
//220729 処理が成功した時の返り値をtrueに変更。
//220321 透過GIF、透過PNGの時は透明を出力、または透明色を白に変換。
//220320 本体画像のリサイズにPNG→PNG、GIF→PNG、WEBP→JPEGの各処理を追加。
//210920 PetitNote版。
$thumbnail_gd_ver=20230218;
defined('PERMISSION_FOR_DEST') or define('PERMISSION_FOR_DEST', 0606); //config.phpで未定義なら0606
function thumb($path,$fname,$time,$max_w,$max_h,$options=[]){
	$path=basename($path).'/';
	$fname=basename($fname);
	$time=basename($time);
	$fname=$path.$fname;
	if(!is_file($fname)){
		return;
	}
	if(!gd_check()||!function_exists("ImageCreate")||!function_exists("ImageCreateFromJPEG")){
		return;
	}
	if(isset($options['webp']) &&!function_exists("ImageWEBP")){
		return;
	}

	$fsize = filesize($fname);    // ファイルサイズを取得
	list($w,$h) = GetImageSize($fname); // 画像の幅と高さとタイプを取得
	$w_h_size_over=($w > $max_w || $h > $max_h);
	$f_size_over=!isset($options['toolarge']) ? ($fsize>1024*1024) : false;
	$w_h_size_over = isset($options['webp']) ? true : $w_h_size_over; 
	if(!$w_h_size_over && !$f_size_over){
		return;
	}
	// リサイズ
	$w_ratio = $max_w / $w;
	$h_ratio = $max_h / $h;
	$ratio = min($w_ratio, $h_ratio);
	$out_w = $w_h_size_over ? ceil($w * $ratio):$w;//端数の切り上げ
	$out_h = $w_h_size_over ? ceil($h * $ratio):$h;

	switch ($mime_type = mime_content_type($fname)) {
		case "image/gif";
			if(!function_exists("ImageCreateFromGIF")){//gif
				return;
			}
				$im_in = @ImageCreateFromGIF($fname);
				if(!$im_in)return;
		
		break;
		case "image/jpeg";
			$im_in = @ImageCreateFromJPEG($fname);//jpg
				if(!$im_in)return;
			break;
		case "image/png";
			if(!function_exists("ImageCreateFromPNG")){//png
				return;
			}
				$im_in = @ImageCreateFromPNG($fname);
				if(!$im_in)return;
			break;
		case "image/webp";
			if(!function_exists("ImageCreateFromWEBP")){//webp
				return;
			}
			$im_in = @ImageCreateFromWEBP($fname);
			if(!$im_in)return;
		break;

		default : return;
	}
	// 出力画像（サムネイル）のイメージを作成
	$exists_ImageCopyResampled = false;
	if(function_exists("ImageCreateTrueColor")&&get_gd_ver()=="2"){
		$im_out = ImageCreateTrueColor($out_w, $out_h);
		if((isset($options['toolarge'])||isset($options['webp'])) && in_array($mime_type,["image/png","image/gif","image/webp"])){
			if(function_exists("imagealphablending") && function_exists("imagesavealpha")){
				imagealphablending($im_out, false);
				imagesavealpha($im_out, true);//透明
			}
			}else{
				if(function_exists("ImageColorAlLocate") && function_exists("imagefill")){
					$background = ImageColorAlLocate($im_out, 0xFF, 0xFF, 0xFF);//背景色を白に
					imagefill($im_out, 0, 0, $background);
				}
			}
		// コピー＆再サンプリング＆縮小
		if(function_exists("ImageCopyResampled")){
			ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $w, $h);
			$exists_ImageCopyResampled = true;
		}
	}else{$im_out = ImageCreate($out_w, $out_h);}
	// コピー＆縮小
	if(!$exists_ImageCopyResampled) ImageCopyResized($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $w, $h);
	if(isset($options['toolarge'])){
		$outfile=$fname;
	//本体画像を縮小
		switch ($mime_type) {
			case "image/gif";
				if(function_exists("ImagePNG")){
					ImagePNG($im_out, $outfile,3);
				}else{
					ImageJPEG($im_out, $outfile,98);
				}
				break;
			case "image/jpeg";
				ImageJPEG($im_out, $outfile,98);
				break;
			case "image/png";
				if(function_exists("ImagePNG")){
					ImagePNG($im_out, $outfile,3);
				}else{
					ImageJPEG($im_out, $outfile,98);
				}
				break;
			case "image/webp";
				if(function_exists("ImageWEBP")){
					ImageWEBP($im_out, $outfile,98);
				}else{
					ImageJPEG($im_out, $outfile,98);
				}
				break;

			default : return;
		}

	}elseif(isset($options['webp'])){
		$outfile='webp/'.$time.'t.webp';
		ImageWEBP($im_out, $outfile,90);

	}else{
		$outfile=THUMB_DIR.$time.'s.jpg';
		// サムネイル画像を保存
		ImageJPEG($im_out, $outfile,90);
	}
	// 作成したイメージを破棄
	ImageDestroy($im_in);
	ImageDestroy($im_out);

	if(!chmod($outfile,PERMISSION_FOR_DEST)){
		return;
	}

	return is_file($outfile);

}
