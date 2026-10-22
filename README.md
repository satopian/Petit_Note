# お絵かき掲示板PHPスクリプト Petit Note
- 1スレッド1ログファイル形式のスレッド式の画像掲示板です。  
- PaintBBS NEO,tegaki.js,litaChix,Klecksが使えるお絵かき掲示板です。

## English version is here
- [Petit Note EN](https://github.com/satopian/Petit_Note_EN) 

### PHPに脆弱性
- 2026年10月、認証なしでリモートからコードを実行される深刻なPHPの脆弱性(CVE-2026-109618、CVE-2026-109619)が公表されました。  
  修正版は PHP 8.5.12 / 8.4.27 / 8.3.36 / 8.2.35 です。PHP 7.4〜8.1 は PHP 公式の更新対象外です。   
  レンタルサーバーをお使いの場合、修正の適用はサーバー会社が行います。   
サーバコントロールパネルなどでPHPのバージョンをご確認ください。  
[PHPの脆弱性対策について(CVE-2026-109618、CVE-2026-109619：PHP2shell) | 情報セキュリティ | IPA 独立行政法人 情報処理推進機構](https://www.ipa.go.jp/security/security-alert/2026/alert20261022.html)
- Petit NotePHP7.4以上で動作しますが、セキュリティパッチが提供されているサポートされているバージョンでの使用を強く推奨します。


## Petit Noteを使った交流サイト

- [お絵かき掲示板交流サイト Petit Note](https://paintbbs.sakura.ne.jp/)  

## DEMO
- [Petit Note サンプル掲示板](https://paintbbs.sakura.ne.jp/cgi/neosample/petitnote/)  

# お絵かき掲示板PHPスクリプト Petit Note のダウンロードと設置

## ダウンロード

- [リリース](https://github.com/satopian/Petit_Note/releases/latest)から安定版をダウンロードできます。

### 設置方法

- 設置するサーバのPHPのバージョンが7.4以上になっている事を確認します。
- [リリース](https://github.com/satopian/Petit_Note/releases/latest)のページの一番下からzipファイルをダウンロードします。
- `petitnote`フォルダ内の`config.php`の管理者パスワードを他の人にはわからないパスワードに変更します。
- `petitnote`フォルダをアップロードします。
- サーバ上の`petitnote`ディレクトリにブラウザでアクセスすると設置が完了します。

#### 設置しても動作しない場合

#### パーミッションを手動で設定すると動作しなくなる事があります。
- 設置時にディレクトリやファイルのパーミッションを変更すると正常に動作しない事があります。  
必要なディレクトリの作成･パーミッションの設定はPHPスクリプトが自動的に行います。
#### PHPのバージョンがPHP7.4未満の時は500エラーになります。
- PHPのバージョンが7.4未満の時は500エラーになります。  
PHPのバージョンが切り替え可能な場合はPHP7.4以上への変更をお願いします。  
このスクリプトの動作環境はPHP7.4-PHP8.xです。
#### template/ ディレクトリのcssが表示されない時は。
- 設置したサーバによっては、同梱している.htaccessが原因でエラーになる事があります。  
その場合は`petitnote/` ディレクトリ、`petitnote/template/`ディレクトリにある`.htaccess`を削除すると動作するようになります。  
ただし、問題なく動作している場合は削除してはいけません。このファイルはセキュリティリスクを低減させるためのものです。 

#### それでも動作しない場合は

[設置サポート掲示板](https://paintbbs.sakura.ne.jp/cgi/neosample/support/)をご利用ください。

  
![image](https://user-images.githubusercontent.com/44894014/134553433-d50e05be-a483-4b94-a575-3cead96b6720.png)

## BBSNoteやPOTI-boardのログファイルをPetit Noteで使うためのログコンバータ

- [Petit Note プラグイン](https://github.com/satopian/PetitNote_plugin)  

BBSNoteとPOTI-boardのログファイルをPetit Note形式に変換できます。  
ただし新しく設置したPetit Noteで変換したログを使う事しかできません。   
変換して新しくできたログファイルで上書きすると既存の投稿は消えてしまいますのでご注意ください。

## 履歴
最新情報は[リリースノート](https://github.com/satopian/Petit_Note/releases/latest)に記載しています。  
  
- [2026/01/04 - ](./chengelog/2026.md)
- [2025/1/1 - 2025/12/31](./chengelog/2025.md)
- [2024/01/07 - 2024/12/24](./chengelog/2024.md)
- [2023/01/02 - 2023/12/30](./chengelog/2023.md)
- [2022/01/06 - 2022/12/27](./chengelog/2022.md)
- [2021/09/03 - 2021/12/05](./chengelog/2021.md)
