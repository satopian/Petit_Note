<?php
//Petit Note (c)さとぴあ @satopian 2021-2026 MIT License
//https://paintbbs.sakura.ne.jp/

$clap_inc_ver = 20260818;
class clap
{
	/**
	 * 指定されたスレッドの拍手ログを配列で返す
	 * @return array 拍手ログの配列
	 */
	public static function create_claplog_array(int $no): array
	{
		global $use_clap;
		if (!$use_clap) {
			return [];
		}
		$claps = [];
		check_open_no($no);
		if (is_file("claplog/{$no}.log")) {
			$fp = fopen("claplog/{$no}.log", "r");
			while ($cline = fgets($fp)) {
				if (!trim($cline)) {
					continue;
				}
				[$c_id, $c_cont] = explode("\t", trim($cline));
				$claps[$c_id] = $c_cont;
			}
			fclose($fp);
		}
		return $claps;
	}
	/**
	 * 拍手を追加して拍手数を非同期通信のレスポンスとして返す
	 */
	public static function addclap(): void
	{

		global $use_clap;
		if (!$use_clap) {
			header('Content-type: text/plain');
			echo "";
			exit();
		}

		$no = t(filter_input_data('POST', 'no', FILTER_VALIDATE_INT));
		$id = t(filter_input_data('POST', 'id'));

		check_open_no($no);

		session_sta();
		if (!isset($_SESSION['clapped'])) {
			$_SESSION['clapped'] = [];
		}
		if ($_SESSION['clapped']["{$no}_{$id}"] ?? false) {
			header('Content-type: text/plain');
			echo ""; // 拍手済み	
			exit();
		}
		//ログファイルにIDが存在するか確認する
		$rp = fopen(LOG_DIR . "{$no}.log", "r"); //個別スレッドのログを開く
		$flag = false;
		while ($resline = fgets($rp)) {
			if (!trim($resline)) {
				continue;
			}
			if (strpos($resline, "\t" . $id . "\t") !== false) {
				$res = create_res(explode("\t", trim($resline)));
				//IDが一一致、画像あり、投稿から一定日数以内であれば拍手可能
				if ($res['first_posted_time'] === $id && $res['img'] && $res['check_elapsed_days']) {
					$flag = true;
				}
				break;
			}
		}
		fclose($rp);
		if (!$flag) {
			header('Content-type: text/plain');
			echo ""; // このIDは存在しない	
			exit();
		}
		//ログファイルにIDが存在する場合は、拍手ログを更新する
		$calplog = "claplog/{$no}.log";
		$userip = get_uip();
		$cp = fopen($calplog, "c+");
		chmod($calplog, 0600);
		file_lock($cp, LOCK_EX);

		$lines = create_array_from_fp($cp);
		$flag = false;
		foreach ($lines as $i => $line) {
			if (strpos($line, $id . "\t") !== false) {
				$flag = true;
				[$_id, $_clap, $_bitsB64] = explode("\t", trim($line));

				$bits = base64_decode($_bitsB64);

				[$alreadyClapped, $newBits] = self::checkAndSetChecksum($bits, $userip);

				if ($alreadyClapped || $_clap > 1000) {
					closeFile($cp);
					header('Content-type: text/plain');
					echo "";
					exit(); // このIPは拍手済み
				}
				$_clap = min($_clap + 1, 1000);
				$lines[$i] = "$_id\t$_clap\t" . base64_encode($newBits) . "\n";
				break;
			}
		}
		if (!$flag) {
			[, $bits] = self::checkAndSetChecksum("", $userip);
			$newline = "$id\t1\t" . base64_encode($bits) . "\n";
			$_clap = 1;
		} else {
			$newline = "";
		}
		$newline .= implode("", $lines);
		writeFile($cp, $newline);
		closeFile($cp);
		delete_res_cache();
		$_SESSION['clapped']["{$no}_{$id}"] = true;
		header('Content-type: text/plain');
		echo h($_clap);
	}

	/**
	 * IPに対応するスロット位置を求める(ダイレクトマップ方式)
	 * @param string $ip IPアドレス
	 * @return int スロット番号(0〜SLOTS-1)
	 */
	private static function slotIndex(string $ip): int
	{
		$SLOTS = 48; // スロット数(1スロット=1byte。48byteは従来の384bitと同サイズ)
		return crc32($ip . '_slot') % $SLOTS;
	}

	/**
	 * IPに対応するチェックサム(1byte, 1〜255。0は「空き」用に予約)を求める
	 * @param string $ip IPアドレス
	 * @return int チェックサム
	 */
	private static function checksum(string $ip): int
	{
		return (crc32($ip . '_cs') % 255) + 1;
	}

	/**
	 * チェックサムを確認し、一致すれば「拍手済み」として弾く。
	 * 一致しない場合(空きスロット、または別IPのチェックサムで埋まっている場合)は
	 * 自分のチェックサムで上書きして「拍手を許可」。
	 * 不明な場合は、押せるようにする。
	 * @param string $bits スロット配列(バイト列)
	 * @param string $ip IPアドレス
	 * @return array{0: bool, 1: string} [拍手済みかどうか, 更新後のバイト列]
	 */
	private static function checkAndSetChecksum(string $bits, string $ip): array
	{
		$slot = self::slotIndex($ip);
		$cs   = self::checksum($ip);
		$existing = ord($bits[$slot] ?? "\0");

		if ($existing === $cs) {
			return [true, $bits]; // 拍手済み
		}
		if (strlen($bits) <= $slot) {
			$bits = str_pad($bits, $slot + 1, "\0");
		}
		$bits[$slot] = chr($cs);
		return [false, $bits]; // 新規許可(上書き)
	}
}
