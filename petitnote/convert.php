<?php
$alllog='./log/alllog.txt';
$fp=fopen($alllog,"r");
$new_arr=[];
while ($line = fgetcsv($fp, 0, "\t")) {
	list($no,$sub,$name,$com,$imgfile,$w,$h,$time,$tool,$res)=$line;

	$new_arr[]="$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$tool\t$time\t$host\t$res\n";

	$new_r_arr=[];
	$reslog='./log/'.$no.'.txt';
	$rp=fopen($reslog,"r");
	while ($r_line = fgetcsv($rp, 0, "\t")) {
		list($no,$sub,$name,$com,$imgfile,$w,$h,$time,$tool,$res)=$r_line;
	$new_r_arr[]
	="$no\t$sub\t$name\t$com\t$imgfile\t$w\t$h\t$tool\t$time\t\t$res\n";
	}
	file_put_contents($reslog,$new_r_arr,LOCK_EX);
	// unset($no,$sub,$name,$com,$imgfile,$w,$h,$time,$tool,$res);
}
file_put_contents($alllog,$new_arr,LOCK_EX);
