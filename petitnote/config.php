<?php
/*設定項目*/

//管理者パスワード 必ず変更してください。
$admin_pass = 'kanripass';
//合言葉 必ず変更してください
$aikotoba = 'あいうえお';
//掲示板名
$boardname = 'Petit Note';
//ホームページ(掲示板からの戻り先)
$home = './'; //相対パス、絶対パス、URLどれでもOK 
//1ページに表示するスレッド数
$pagedef = 5;
//最大スレッド数
$max_log = 1000;
//1スレッドに返信できるレスの数
$max_res = 5;
//1スレッドに表示するレスの数
//返信画面で全件表示
$dispres=5;
//投稿できる画像のサイズ単位kb
$max_kb = 2048;
//お絵かき最大サイズ
$pmax_w = 800;//幅
$pmax_h = 800;//高さ
//表示する最大サイズ
$max_w = 800;
$max_h = 500;
//表示する最大サイズを超える時はサムネイルを作成する
// する: true しない: false
$use_thumb = true;
// $use_thumb = false;

//合言葉機能を使って投稿を制限する
// する: true しない: false
$use_aikotoba = true;
// $use_aikotoba=false;

//日記モードを使う
// する: true しない: false
$use_diary = true;
// $use_diary = false;

//画像アップロード機能を使う
// 使う:true 使わない:false
$use_upload = true;
// $use_upload = false;

/*スパム対策*/
//本文に日本語がなければ拒絶 する:true しない:false
$use_japanesefilter = true;
// $use_japanesefilter=false;


//拒絶する文字列 正規表現
$badstring = ["example.example.com","未承諾広告"];

//使用できない名前 正規表現
$badname = ["ブランド","通販","販売","口コミ"];

//AとBが両方あったら拒絶 正規表現
$badstr_A = ["激安","低価","コピー","品質を?重視","大量入荷"];
$badstr_B = ["シャネル","シュプリーム","バレンシアガ","ブランド"];

/*変更しないでください*/
//テンポラリ
define('TEMP_DIR','temp/');
//ログ
define('LOG_DIR','log/');
//画像
define('IMG_DIR','src/');
//画像
define('THUMB_DIR','thumbnail/');

//ペイント画面の$pwdの暗号化
define('CRYPT_PASS','v25Xc9nZ82a5JPT');//暗号鍵初期値
define('CRYPT_METHOD','aes-128-cbc');
define('CRYPT_IV','T3pkYxNyjN7Wz3pu');//半角英数16文字

