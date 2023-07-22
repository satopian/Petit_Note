<?php
//Petit Note 2021-2023 (c)satopian MIT Licence
//https://paintbbs.sakura.ne.jp/

//Misskey APIに接続

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/functions.php');

$lang = ($http_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
? explode( ',', $http_langs )[0] : '';
$en= (stripos($lang,'ja')!==0);
$skindir='template/'.$skindir;

session_sta();

if((!isset($_SESSION['sns_api_session_id']))||(!isset($_SESSION['sns_api_val']))){
	return header( "Location: ./ ") ;
};

$baseUrl = isset($_SESSION['misskey_server_radio']) ? $_SESSION['misskey_server_radio'] : "https://misskey.io";
if(!filter_var($baseUrl,FILTER_VALIDATE_URL)){
	return error($en ? "This is not a valid server URL.":"サーバのURLが無効です。" ,false);
}
// 認証チェック
$sns_api_session_id = $_SESSION['sns_api_session_id'];
list($com,$src_image,$tool,$painttime,$hide_thumbnail,$no,$article_url_link) = $_SESSION['sns_api_val'];
$src_image=basename($src_image);
$checkUrl = $baseUrl . "/api/miauth/{$sns_api_session_id}/check";

$checkCurl = curl_init();
curl_setopt($checkCurl, CURLOPT_URL, $checkUrl);
curl_setopt($checkCurl, CURLOPT_POST, true);
curl_setopt($checkCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($checkCurl, CURLOPT_POSTFIELDS, json_encode([]));//空のData
curl_setopt($checkCurl, CURLOPT_RETURNTRANSFER, true);

$checkResponse = curl_exec($checkCurl);
curl_close($checkCurl);

if (!$checkResponse) {
	return error($en ? "Authentication failed." :"認証に失敗しました。" ,false);	
}

$responseData = json_decode($checkResponse, true);
$accessToken = $responseData['token'];
$user = $responseData['user'];

// 画像のアップロード
$imagePath = __DIR__.'/src/'.$src_image;

if(!is_file($imagePath)){
	return error($en ? "Image does not exist." : "画像がありません。" ,false);
};
$img_type = mime_content_type($imagePath);
if (!in_array($img_type, ["image/gif", "image/jpeg", "image/png","image/webp"])) {
	return error($en? "This file is an unsupported format.":"対応していないファイル形式です。" ,false);
}

$uploadUrl = $baseUrl . "/api/drive/files/create";
$uploadHeaders = array(
	'Authorization: Bearer ' . $accessToken,
	'Content-Type: multipart/form-data'
);
$uploadFields = array(
	'i' => $accessToken,
	'file' => new CURLFile($imagePath),
);
// var_dump($uploadFields);
$uploadCurl = curl_init();
curl_setopt($uploadCurl, CURLOPT_URL, $uploadUrl);
curl_setopt($uploadCurl, CURLOPT_POST, true);
curl_setopt($uploadCurl, CURLOPT_HTTPHEADER, $uploadHeaders);
curl_setopt($uploadCurl, CURLOPT_POSTFIELDS, $uploadFields);
curl_setopt($uploadCurl, CURLOPT_RETURNTRANSFER, true);
$uploadResponse = curl_exec($uploadCurl);
$uploadStatusCode = curl_getinfo($uploadCurl, CURLINFO_HTTP_CODE);
curl_close($uploadCurl);
// var_dump($uploadResponse);
if (!$uploadResponse) {
	// var_dump($uploadResponse);
	return error($en ? "Failed to upload the image." : "画像のアップロードに失敗しました。" ,false);
}

// アップロードしたファイルのIDを取得

$responseData = json_decode($uploadResponse, true);
$fileId = isset($responseData['id']) ? $responseData['id']:'';

if(!$fileId){
	// var_dump($responseData);
	return error($en ? "Failed to upload the image." : "画像のアップロードに失敗しました。" ,false);
}

// ファイルの更新
$updateUrl = $baseUrl . "/api/drive/files/update";
$updateHeaders = array(
	'Authorization: Bearer ' . $accessToken,
	'Content-Type: application/json'
);
$updateData = array(
	'i' => $accessToken,
	'fileId' => $fileId,
	'isSensitive' => (bool)($hide_thumbnail), // isSensitiveフィールドを更新する場合はここで指定
	// 他に更新したいパラメータがあればここに追加
);

$updateCurl = curl_init();
curl_setopt($updateCurl, CURLOPT_URL, $updateUrl);
curl_setopt($updateCurl, CURLOPT_POST, true);
curl_setopt($updateCurl, CURLOPT_HTTPHEADER, $updateHeaders);
curl_setopt($updateCurl, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($updateCurl, CURLOPT_RETURNTRANSFER, true);
$updateResponse = curl_exec($updateCurl);
$updateStatusCode = curl_getinfo($updateCurl, CURLINFO_HTTP_CODE);
curl_close($updateCurl);

if (!$updateResponse) {
	return error($en ? "Failed to update the file." : "ファイルの更新に失敗しました。" ,false);
}
// var_dump($updateResponse);

$uploadResult = json_decode($uploadResponse, true);

if (!$fileId) {
	return error($en ? "Failed to post the content." : "投稿に失敗しました。" ,false);
}
	
sleep(10);

// 投稿
$tool= $tool ? 'Tool:'.$tool.' ' :'';
$painttime= $painttime ? 'Paint time:'.$painttime.' ' :'';
$fixed_link = ' '.$root_url.'?resno='.$no;
$fixed_link = $article_url_link ? $fixed_link :'';
$status = $tool.$painttime.$com.$fixed_link;

$postUrl = $baseUrl . "/api/notes/create";
$postHeaders = array(
	'Authorization: Bearer ' . $accessToken,
	'Content-Type: application/json'
);
$postData = array(
	'i' => $accessToken,
	'text' => $status,
	'fileIds' => array($fileId),
);

$postCurl = curl_init();
curl_setopt($postCurl, CURLOPT_URL, $postUrl);
curl_setopt($postCurl, CURLOPT_POST, true);
curl_setopt($postCurl, CURLOPT_HTTPHEADER, $postHeaders);
curl_setopt($postCurl, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($postCurl, CURLOPT_RETURNTRANSFER, true);
$postResponse = curl_exec($postCurl);
$postStatusCode = curl_getinfo($postCurl, CURLINFO_HTTP_CODE);
curl_close($postCurl);
// var_dump($postResponse);
if ($postResponse) {
	$postResult = json_decode($postResponse, true);
	if (!empty($postResult['createdNote']["fileIds"])) {

		unset($_SESSION['misskey_server_radio']);
		unset($_SESSION['sns_api_session_id']);
		unset($_SESSION['sns_api_val']);
							
		// var_dump($uploadResponse,$postResponse,$uploadResult,$postResult);
		return header('Location: '.$baseUrl);
	} 
	else {
		return error($en ? "Failed to post the content." : "投稿に失敗しました。" ,false);
		}
} else {
	return error($en ? "Failed to post the content." : "投稿に失敗しました。" ,false);
}
		// var_dump($uploadResponse);
				// unset($_SESSION['sns_api_val']);
// var_dump($uploadResponse,$postResponse,$uploadResult,$postResult, $accessToken );
// var_dump($postResult['createdNote']["fileIds"],array($mediaId),$uploadStatusCode,$postStatusCode,$postResult,$uploadResponse, $accessToken );
