<?php
$clap_inc_ver=20260803;
class clap{
	public static function create_claplog_array(int $no):array{
		global $use_clap;
		if(!$use_clap){
			return [];
		}
		$claps=[];
		check_open_no($no);
		if(is_file("claplog/{$no}.log")){
		$fp= fopen("claplog/{$no}.log","r");
		while ($cline = fgets($fp)) {
			if(!trim($cline)){
				continue;
			}
			[$c_id,$c_cont] = explode("\t",trim($cline));
			$claps[$c_id]=$c_cont;
		}
		fclose($fp);
		}
		return $claps;
	}

	public static function addclap(): void {

		global $use_clap;
		if(!$use_clap){
			exit();
		}

		$no=t(filter_input_data('POST','no',FILTER_VALIDATE_INT));
		$id=t(filter_input_data('POST','id'));

		check_open_no($no);

		//ログファイルにIDが存在するか確認する
		$rp = fopen(LOG_DIR."{$no}.log", "r");//個別スレッドのログを開く
		$flag=false;
		while ($resline = fgets($rp)) {
			if(!trim($resline)){
				continue;
			}
			if(strpos($resline,"\t".$id."\t")!==false){
			$res=create_res(explode("\t",trim($resline)));
			if($res['first_posted_time']===$id && $res['img']){
				$flag=true;
			} 	
				break;
			}
		}
		fclose($rp);
		if(!$flag){
			header('Content-type: text/plain');
			echo ""; // このIDは存在しない	
			exit();
		}
		//ログファイルにIDが存在する場合は、拍手ログを更新する
		$calplog="claplog/{$no}.log";
		$userip = get_uip();
		if(!is_file($calplog)){
			$bits = self::setBit("", $userip);
			file_put_contents($calplog,"$id\t1\t" . base64_encode($bits) . "\n",LOCK_EX);
			chmod($calplog,0600);
			delete_res_cache();
			header('Content-type: text/plain');
			echo "1";
			exit();
		}
		chmod($calplog,0600);
		$cp=fopen($calplog,"r+");
		file_lock($cp, LOCK_EX);

		$lines = create_array_from_fp($cp);
		$flag=false;
		foreach($lines as $i => $line){
			if(strpos($line,$id."\t")!==false){
				$flag=true;
				[$_id,$_clap,$_bitsB64]=explode("\t",trim($line));
				$bits = base64_decode($_bitsB64);
				if(self::hasBit($bits, $userip)){
					closeFile($cp);
					header('Content-type: text/plain');
					echo ""; // このIPは拍手済み
					exit(); // このIPは拍手済み
				}
				$_clap = min($_clap+1, 100000);
				$newBits = self::setBit($bits, $userip);
				$lines[$i]="$_id\t$_clap\t" . base64_encode($newBits) . "\n";
				break;
			}
		}	
		 if(!$flag){
			$bits = self::setBit("", $userip);
			 $newline="$id\t1\t" . base64_encode($bits) . "\n";
			 $_clap = 1;
		 }else{
			$newline="";
		 }
		$newline.=implode("",$lines);
		writeFile($cp,$newline);
		closeFile($cp);
		delete_res_cache();
		header('Content-type: text/plain');
		echo h($_clap);
	}

	// IPのビットが立っているか見る
	private static function hasBit(string $bits, string $ip): bool {
			$pos  = self::bitPosition($ip);
			$byte = intdiv($pos, 8);
			$bit  = $pos % 8;
			return (bool)((ord($bits[$byte] ?? "\0") >> $bit) & 1);
	}

	// ビットを立てた「新しい」文字列を返す(元の$bitsは変更しない)
	private static function setBit(string $bits, string $ip): string {
			$pos  = self::bitPosition($ip);
			$byte = intdiv($pos, 8);
			$bit  = $pos % 8;
			if(strlen($bits) <= $byte){
					$bits = str_pad($bits, $byte+1, "\0");
			}
			$bits[$byte] = chr(ord($bits[$byte]) | (1 << $bit));
			return $bits;
	}

	private static function bitPosition(string $ip): int {
			$SIZE_BITS = 4096; // 512バイト固定
			return crc32($ip) % $SIZE_BITS;
	}
}