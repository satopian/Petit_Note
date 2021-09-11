<?php
$alllog='./log/alllog.log';
$fp=fopen($alllog,"r");
$new_arr=[];
while ($line = fgetcsv($fp, 0, "\t")) {
	list($no,$sub,$name,$com,$imgfile,$w,$h,$img_md5,$time,$tool,$host,$res)=$line;

	$new_arr[]="$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$img_md5\t$tool\t$time\t$host\t\t$res\n";

	$new_r_arr=[];
	$reslog='./log/'.$no.'.log';
	if(!is_file($reslog)){
		continue;
	}
	$rp=fopen($reslog,"r");
	while ($r_line = fgetcsv($rp, 0, "\t")) {
		list($no,$sub,$name,$com,$imgfile,$w,$h,$img_md5,$time,$tool,$host,$res)=$r_line;
	$new_r_arr[]
	="$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$img_md5\t$tool\t$time\t$host\t\t$res\n";
	}
	file_put_contents($reslog,$new_r_arr,LOCK_EX);
	// unset($no,$sub,$name,$com,$imgfile,$w,$h,$time,$tool,$res);
}
file_put_contents($alllog,$new_arr,LOCK_EX);
