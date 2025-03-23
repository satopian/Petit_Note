<?php
//Petit Note (C)2021-2025 さとぴあ(@satopian)
//MIT License
$search_inc_ver = 20250320;
class processsearch
{

	private static bool $imgsearch; // `true` か `false` を保持する
	private static int $page;       // `ページ番号 (整数)` を保持する
	private static string $q;       // `検索キーワード (文字列)` を保持する
	private static int $radio;      // `検索オプション (整数)` を保持する

	private static function init(): void
	{
		self::$imgsearch = (bool)filter_input_data('GET', 'imgsearch', FILTER_VALIDATE_BOOLEAN);
		self::$page = (int)filter_input_data('GET', 'page', FILTER_VALIDATE_INT);
		self::$q = (string)filter_input_data('GET', 'q');
		self::$radio = (int)filter_input_data('GET', 'radio', FILTER_VALIDATE_INT);;
	}

	//検索画面
	public static function search(): void
	{

		global $use_aikotoba, $home, $skindir;
		global $boardname, $petit_ver, $petit_lot, $set_nsfw, $en, $mark_sensitive_image;
		global $search_images_pagedef, $search_comments_pagedef;

		aikotoba_required_to_view();
		set_page_context_to_session();

		self::init();
		$imgsearch = self::$imgsearch;
		$page = self::$page;
		$q = self::$q;
		$radio = self::$radio;

		$page = $page < 0 ? 0 : $page;
		$q = urldecode($q);
		$q_len = strlen((string)$q);
		$q = 1000 < $q_len ? "" : $q;

		//画像検索の時の1ページあたりの表示件数
		$search_images_pagedef = $search_images_pagedef ?? 60;
		//通常検索の時の1ページあたりの表示件数
		$search_comments_pagedef = $search_comments_pagedef ?? 30;

		if ($imgsearch) {
			$pagedef = $search_images_pagedef; //画像検索の時の1ページあたりの表示件数
		} else {
			$pagedef = $search_comments_pagedef; //通常検索の時の1ページあたりの表示件数
		}
		//検索結果の配列を取得
		$arr = self::create_search_array();
		krsort($arr);
		//検索結果の出力
		$j = 0;
		$out = [];
		if (!empty($arr)) {
			//ページ番号から1ページ分をとりだす
			$articles = array_slice($arr, (int)$page, $pagedef, false);
			$articles = array_values($articles); //php5.6 32bit 対応

			$txt_search = !$imgsearch ? ['search' => true] : []; //本文の検索の時にtrue
			foreach ($articles as $i => $line) {

				$out[0][$i] = create_res($line, (['catalog' => true] + $txt_search)); //$lineから、情報を取り出す

				// マークダウン
				$pattern = "{\[((?:[^\[\]\\\\]|\\\\.)+?)\]\((https?://[^\s\)]+)\)}";
				$com = preg_replace_callback($pattern, function ($matches) {
					// エスケープされたバックスラッシュを特定の文字だけ解除
					return str_replace(['\\[', '\\]', '\\(', '\\)'], ['[', ']', '(', ')'], $matches[1]);
				}, $out[0][$i]['com']);

				$com = h(strip_tags($com));
				$com = mb_strcut($com, 0, 180);
				$out[0][$i]['com'] = $com;

				$j = $page + $i + 1; //表示件数
			}
		}

		if ($imgsearch) {
			$img_or_com = $en ? 'Images' : 'イラスト';
			$mai_or_ken = $en ? ' ' : '枚';
		} else {
			$img_or_com = $en ? 'Comments' : 'コメント';
			$mai_or_ken = $en ? ' ' : '件';
		}
		$imgsearch = (bool)$imgsearch;

		//ラジオボタンのチェック
		$radio_chk1 = false; //作者名
		$radio_chk2 = false; //完全一致
		$radio_chk3 = false; //本文題名	

		switch ($radio) {
			case 0:
				$radio_chk1 = true;
				break;
			case 1:
				$radio_chk1 = true;
				break;
			case 2:
				$radio_chk2 = true;
				break;
			case 3:
				$radio_chk3 = true;
				break;
		}

		$page = (int)$page;
		$en_q = h(urlencode($q));
		$q = h($q);

		$pageno = 0;
		if ($j && $page >= 2) {
			$pageno = ($page + 1) . '-' . $j . $mai_or_ken;
		} else {
			$pageno = $j . $mai_or_ken;
		}
		if ($q !== '' && $radio === 3) {
			$result_subject = ($en ? $img_or_com . ' of ' . $q : $q . "の"); //h2タグに入る
		} elseif ($q !== '') {
			$result_subject = $en ? 'Posts by ' . $q : $q . 'さんの';
		} else {
			$result_subject = $en ? 'Recent ' . $pageno . ' Posts' : $boardname . 'に投稿された最新の';
			$pageno = $en ? '' : $pageno;
		}

		$count_alllog = count($arr); //配列の数
		$countarr = $count_alllog; //古いテンプレート互換

		//ページング
		list($start_page, $end_page) = calc_pagination_range($page, $pagedef);

		//prev next 
		$next = (($page + $pagedef) < $count_alllog) ? $page + $pagedef : false; //ページ番号がmaxを超える時はnextのリンクを出さない
		$prev = ((int)$page <= 0) ? false : ($page - $pagedef); //ページ番号が0の時はprevのリンクを出さない
		$prev = ($prev < 0) ? 0 : $prev;

		//最終更新日時を取得
		$postedtime = '';
		$lastmodified = '';
		if (!empty($arr)) {

			$time = key($arr);
			$postedtime = microtime2time($time);
			$lastmodified = date("Y/m/d G:i", (int)$postedtime);
		}

		unset($arr);
		unset($no, $sub, $name, $verified, $com, $url, $imgfile, $w, $h, $thumbnail, $painttime, $log_img_hash, $tool, $pchext, $time, $first_posted_time, $host, $userid, $hash, $oya);

		$admindel = admindel_valid();
		$nsfwc = (bool)filter_input_data('COOKIE', 'nsfwc', FILTER_VALIDATE_BOOLEAN);
		$set_nsfw_show_hide = (bool)filter_input_data('COOKIE', 'p_n_set_nsfw_show_hide', FILTER_VALIDATE_BOOLEAN);
		$admin_pass = null;
		//HTML出力
		$templete = 'search.html';
		include __DIR__ . '/' . $skindir . $templete;
		exit();
	}
	//検索結果の配列を取得
	private static function create_search_array(): array
	{
		global $max_search, $search_images_pagedef, $search_comments_pagedef;

		self::init();
		$imgsearch = self::$imgsearch;
		$q = self::$q;
		$radio = self::$radio;

		//検索可能最大数
		$max_search = $max_search ?? 300;

		$q = urldecode($q);
		$q_len = strlen((string)$q);
		$q = 1000 < $q_len ? "" : $q;
		$check_q = self::create_formatted_text_for_search($q);


		//ログの読み込み
		$arr = [];

		session_sta();

		$cache_file = __DIR__ . '/template/cache/index_cache.json';

		// キャッシュのタイムスタンプ取得
		$cache_last_modified = is_file($cache_file) ? filemtime($cache_file) : 0;
		// キャッシュが更新されていたらSESSIONクリア
		if (
			!$cache_last_modified || //キャッシュがない
			$cache_last_modified > ($_SESSION['search_start_time'] ?? 0)
		) { //キャッシュが更新されている
			unset($_SESSION['search_result']); //検索結果のキャッシュをクリア
		}
		//同じ検索条件での検索結果をキャッシュする
		if (
			isset($_SESSION['imgsearch']) && $_SESSION['imgsearch'] === $imgsearch
			&& isset($_SESSION['search_q']) && $_SESSION['search_q'] === $q
			&& isset($_SESSION['search_radio']) && $_SESSION['search_radio'] === $radio
		) {
			$arr = $_SESSION['search_result'] ?? [];
		}
		//検索条件をセッションに保存	
		$_SESSION['imgsearch'] = $imgsearch;
		$_SESSION['search_q'] = $q;
		$_SESSION['search_radio'] = $radio;
		$_SESSION['search_start_time'] = time();

		if ($arr && is_array($arr)) {
			return $arr; //SESSIONにキャッシュした配列を返す
		}
		$i = 0;
		$j = 0;
		$fp = fopen("log/alllog.log", "r");
		while ($log = fgets($fp)) {
			if (!trim($log)) {
				continue;
			}
			list($resno) = explode("\t", $log, 2);
			$resno = basename($resno);
			//個別スレッドのループ
			if (!is_file(LOG_DIR . "{$resno}.log")) {
				continue;
			}
			$rp = fopen("log/{$resno}.log", "r");
			while ($line = fgets($rp)) {

				$lines = explode("\t", $line);
				//ホスト名とパスワードハッシュは含めない
				list($no, $sub, $name, $verified, $com, $url, $imgfile, $w, $h, $thumbnail, $painttime, $log_img_hash, $tool, $pchext, $time, $first_posted_time,, $userid,, $oya) = $lines;

				if (!$name && !$com && !$url && !$imgfile && !$userid) { //この記事はありませんの時は表示しない
					continue;
				}
				$continue_to_search = true;
				if ($imgsearch) { //画像検索の場合
					$continue_to_search = (bool)$imgfile; //画像があったら
				}

				if ($continue_to_search) {
					if ($radio === 1 || $radio === 2 || $radio === 0) {
						$s_name = self::create_formatted_text_for_search($name);
					} else {
						$s_sub = self::create_formatted_text_for_search($sub);
						$s_com = self::create_formatted_text_for_search($com);
					}

					//ログとクエリを照合
					if (
						$check_q === '' || //空白なら
						$check_q !== '' && $radio === 3 && strpos($s_com, $check_q) !== false || //本文を検索
						$check_q !== '' && $radio === 3 && strpos($s_sub, $check_q) !== false || //題名を検索
						$check_q !== '' && ($radio === 1 || $radio === 0) && strpos($s_name, $check_q) === 0 || //作者名が含まれる
						$check_q !== '' && ($radio === 2 && $s_name === $check_q) //作者名完全一致
					) {
						$arr[$time] = $lines;
						++$i;
						if ($i >= $max_search && $j > 10) {
							break 2;
						} //1掲示板あたりの最大検索数 最低でも10スレッド分は取得
					}
				}
			}
			fclose($rp);
			if ($j >= 10000) {
				break;
			} //1掲示板あたりの最大行数
			++$j;
		}
		fclose($fp);

		$_SESSION['search_result'] = $arr;
		return $arr;
	}
	//検索文字列をフォーマット
	private static function create_formatted_text_for_search($str): string
	{

		$s_str = mb_convert_kana($str, 'rn', 'UTF-8'); //全角英数を半角に
		$s_str = str_replace([" ", "　"], "", $s_str);
		$s_str = str_replace("〜", "～", $s_str); //波ダッシュを全角チルダに
		$s_str = strtolower($s_str); //小文字に

		return $s_str;
	}
}
