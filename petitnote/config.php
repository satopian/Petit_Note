<?php
/*設定項目*/

//管理者パスワード 必ず変更してください。
$admin_pass='kanripass';
//掲示板名
$boardname='Petit Note';
//1ページに表示するスレッド数
$pagedef=5;
//最大スレッド数
$max_log=30;
//1スレッドに返信できるレスの数
$max_res=10;
//投稿できる画像のサイズ単位kb
$max_kb=1024;
//お絵かき最大サイズ
$pmax_w=800;//幅
$pmax_h=800;//高さ
//表示する最大サイズ
$max_w = 800;
$max_h = 500;

/*スパム対策*/
//本文に日本語がなければ拒絶 する:true しない:false
$use_japanesefilter=true;

//拒絶する文字列 正規表現
$badstring = ["example.example.com","未承諾広告"];

//使用できない名前 正規表現
$badname = ["ブランド","通販","販売","口コミ"];

//AとBが両方あったら拒絶 正規表現
$badstr_A = ["激安","低価","コピー","品質を?重視","大量入荷"];
$badstr_B = ["シャネル","シュプリーム","バレンシアガ","ブランド"];

/*変更しないでください*/
//テンポラリ
define('TEMP_DIR','./temp/');
