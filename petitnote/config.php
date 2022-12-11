<?php
/*設定項目*/

/*-----絶対に変更が必要な項目-----*/

//管理者パスワード 必ず変更してください。
$admin_pass = "kanripass";

//第2パスワード 必ず変更してください。
//管理者投稿や管理者削除の時に管理者である事を再確認する為に使うパスワード。
//内部で処理するため覚えておく必要はありません。
//管理パスと同じパスワードは使えません。
$second_pass = "mGeL5dhpQ8e9ugd";

//この掲示板の名前
$boardname = "Petit Note";

//ホームページ(掲示板からの戻り先)
$home = "./"; //相対パス、絶対パス、URLどれでもOK 

//最大スレッド保存件数 この数値以上のスレッドは削除されます
//最低500スレッド。
$max_log = 3000;

// 設置場所のurl `/`まで。
$root_url = "http://example.com/oekaki/";

//名前を必須にする
// しない: false の時に名前を空欄で投稿すると、｢anonymous｣になります。
// する: true しない: false

$name_input_required = true;
// $name_input_required = false;

//本文の制限文字数。半角で
$max_com=1000;

/*-----絶対に変更が必要な項目ここまで-----*/

/*SNS連携*/

// ツイートボタンを使う
// スレッドのurlと内容をツイートするリンクがでます。
// 使う: true 使わない: false

$use_sns_button = true;
// $use_sns_button = false;

/*テンプレート切り替え*/
//テンプレートのディレクトリ`/`まで 初期値 "basic/"
$skindir="basic/";

/*掲示板の説明文*/

// テンプレートに直接記入しても構いませんが、ここで入力する事もできます。
// 説明文が1行なら ["説明そのいち"]
// 説明文が3行なら ["説明そのいち","説明そのに","説明そのさん"]
// 文字をダブルクオートで囲って、カンマで区切ります。
// 説明文が不要なら []で。

// $descriptions = ["iPadやスマートフォンでも描けるお絵かき掲示板です。"];	
$descriptions = ["iPadやスマートフォンでも描けるお絵かき掲示板です。","楽しくお絵かき。"];	


/*メール通知*/

// 投稿をメールで通知する
// する: true しない: false

// $send_email = true;
$send_email = false;

//投稿があった事を通知するメールアドレス
$to_mail = "example@example.com";

/*スパム対策*/
//本文に日本語がなければ拒絶 する:true しない:false
$use_japanesefilter = true;
// $use_japanesefilter=false;

//拒絶する文字列 正規表現
// 設定しないなら[]で。
$badstring = ["example.example.com","未承諾広告"];

//拒絶するurl
$badurl = ["example.com","www.example.com"];

//使用できない名前 正規表現
$badname = ["ブランド","通販","販売","口コミ"];

//AとBが両方あったら拒絶 正規表現
$badstr_A = ["激安","低価","コピー","品質を?重視","大量入荷"];
$badstr_B = ["シャネル","シュプリーム","バレンシアガ","ブランド"];

//禁止ホスト
$badhost =["example.com","example.org"];

/*使用目的別設定*/

// 本文へのURLの書き込みを許可する
// URLを書き込むスパムを排除する時は しない: false
// 管理者は設定に関わらず本文にURLを書き込めます。
// する: true しない: false

// $allow_comments_url = true; 
$allow_comments_url = false; 

// URLを自動リンクする
// マークダウン記法も使えます。[リンクの文字](https://example.com/)
// する: true しない: false

$use_autolink = true;
// $use_autolink = false;

//トップページの入力フォームを使用する
// しない: false でお絵かきボタンのみの表示になります。
// する: true しない: false

$use_top_form = true;
// $use_top_form = false;

//画像アップロード機能を使う
//管理者投稿モード(日記)でログインしている時は使わないに設定しても、ファイルアップロードが可能です。
// 使う:true 使わない:false

$use_upload = true;
// $use_upload = false;

//レスで画像アップロード機能を使う
//管理者投稿モード(日記)でログインしている時は使わないに設定しても、レスでお絵かきやレス画像のファイルアップロードが可能です。
// 使う: true 使わない: false

$use_res_upload = true;
// $use_res_upload =  false;

//コメントのみの新規投稿を許可する、しない。
//しない: false で、スレ立てに画像が必須になります。
// する: true しない: false

// $allow_coments_only = true;
$allow_coments_only = false;

//日記モードを使用する
//する: true でスレッド立ては管理者のみになります。
// する: true しない: false

// $use_diary = true;
$use_diary = false;

//返信を管理者のみに限定する
//する: true で管理者以外返信ができなくなります。
//日記モードと併用すれば、すべての書き込みが管理者のみになります。

// $only_admin_can_reply = true;
$only_admin_can_reply = false;

//合言葉機能を使って投稿を制限する
// する: true しない: false

// $use_aikotoba = true;
$use_aikotoba=false;

//合言葉機能で投稿を制限する時の合言葉
//この合言葉が入力されていない時には書き込むことができません。
// 必要に応じて変更してください。
$aikotoba = "あいうえお";

//年齢制限付きの掲示板として設定する
//する: trueに設定すると確認ボタンを押すまで画像にぼかしが入ります。
// する: true しない: false

// $set_nsfw = true;
$set_nsfw = false;

// 閲覧注意を設定する
//する: trueに設定すると閲覧注意の設定ができるようになります。閲覧注意画像にぼかしが入ります。
// する: true しない: false

// $mark_sensitive_image = true;
$mark_sensitive_image = false;

//編集しても投稿日時を変更しないようにする 
//日記などで日付が変わると困る人のための設定
//する: trueに設定すると編集しても投稿日時が変わりません。 通常は しない: false 。
// する: true しない: false

// $do_not_change_posts_time = true;
$do_not_change_posts_time = false;

//レスがついてもスレッドがあがらないようにする
//する: trueに設定するとレスがついてもスレッドがあがりません。(全てsage)。
//初期値 false

//$sage_all = true;
$sage_all = false;

//管理者を認証する
//する: true で、管理者の投稿の時は認証マークが出ます。初期テンプレートではチェックマーク。
//管理者モードでログイン、またはパスワード一致の時に管理者と判定します。
// する: true しない: false

$verified_adminpost = true; 
// $verified_adminpost = false; 

//レス画面に前後のスレッドの画像を表示する する:1 しない:0
// する: true しない: false

$view_other_works = true;
// $view_other_works = false;


//管理者ページに最新のリリースのバージョンとリンクを表示する
// する: true しない: false

$latest_var = true;
// $latest_var = false;

/*表示件数*/

//1ページに表示するスレッド数

$pagedef = 10;

//1スレッドに返信できるレスの数
//管理者による投稿はこの制限を受けません。

$max_res = 100;

//1スレッドに表示するレスの数
//返信画面では全て表示します。

$dispres= 5;

//カタログモード時の1ページあたりの表示件数
//6の倍数で設定すると画面にきれいにおさまります。

$catalog_pagedef = 24;

/*画像関連*/

//投稿できる画像の容量上限 単位kb

$max_kb = 2048;

//投稿できる画像の幅と高さの上限 単位px これ以上は縮小
//縮小されるのはアップロード画像のみ。お絵かきの制限値はここのすぐ下の設定項目で。

$max_px = 1024;

//お絵かきできる幅と高さの最大サイズ

$pmax_w = 800;//幅
$pmax_h = 800;//高さ

//スレッドの親の表示する幅と高さの最大サイズ

$max_w = 800;//幅
$max_h = 800;//高さ

//スレッドのレスの表示する幅と高さの最大サイズ

$res_max_w = 300;
$res_max_h = 300;

//表示する幅と高さの最大サイズを超える時はサムネイルを作成する
// する: true しない: false

$use_thumb = true;
// $use_thumb = false;

/*セキュリティ*/

// 管理者パスワードを5回連続して間違えた時は拒絶する
// する: true しない: false
// trueにするとセキュリティは高まりますが、ログインページがロックされた時の解除に手間がかかります。

// $check_password_input_error_count = true;
$check_password_input_error_count = false;

//ftp等でアクセスして、
// `template/errorlog/error.log`
// を削除すると、再度ログインできるようになります。
//このファイルには、間違った管理者パスワードを入力したクライアントのIPアドレスが保存されています。

/*詳細設定*/

//古いスレッドを自動的に閉じる日数 単位 日
//古いスレッドへのスパム防止
//初期設定の180で、半年前に立てられたスレッドに返信できなくなります。
//日数による制限をしない時は 0 。 
//管理者投稿はこの制限を受けません。

$elapsed_days=180;

//すべての投稿を拒否する
//管理人長期不在、展示のみなど。
//する: trueで、すべての投稿ができなくなります。 初期値 false。
// する: true しない: false

// $deny_all_posts = true;
$deny_all_posts = false;


//タイムゾーン 日本時間で良ければ初期値 "asia/tokyo"

date_default_timezone_set("asia/tokyo");

//iframe内での表示を 拒否する:true 許可する:false
//セキュリティリスクを回避するため "拒否する:true" を強く推奨。

$x_frame_options_deny=true;
// $x_frame_options_deny=false;

//通常は変更しません
//ペイント画面の$pwdの暗号化

define("CRYPT_PASS","v25Xc9nZ82a5JPT");//暗号鍵初期値
define("CRYPT_METHOD","aes-128-cbc");
define("CRYPT_IV","T3pkYxNyjN7Wz3pu");//半角英数16文字

/*変更不可*/

//変更しないでください
//テンポラリ
define("TEMP_DIR","temp/");
//ログ
define("LOG_DIR","log/");
//画像
define("IMG_DIR","src/");
//画像
define("THUMB_DIR","thumbnail/");
