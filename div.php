<?php
/**
 * divタグが正しいかチェックする
 */

//パラメータに入力ディレクトリ名と出力ファイル名
$input_dir = $argv[1];
if ($input_dir == false) {
	echo "php div.php [input_dir]\n";
	exit;
}

//ディレクトリが存在するか
if (file_exists($input_dir) == false) {
	echo "dir not exist($input_dir)\n";
	exit;
}

//それはディレクトリか？
if (is_dir($input_dir) == false) {
	echo "is not dir($input_dir)\n";
	exit;
}

check_in_dir($input_dir);

//１ディレクトリ配下を調べる
function check_in_dir($input_path) {

	$dh = opendir($input_path);
	if ($dh == false) {
		echo "opendir failed($input_path)\n";
		exit;
	}
	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") continue;
		$file_path = $input_path . "\\" .$file;
		if (is_dir($file_path) == true) {
			check_in_dir($file_path);
		} else {
			check_file($file_path);
		}
	}

}

//このファイルのdivタグが正しいかチェックする
function check_file($file_path) {

	$path_parts = pathinfo($file_path);
	if (isset($path_parts['extension']) == false) return;
	if ($path_parts['extension'] != "html") return;
	echo "$file_path";

	$contents = file_get_contents($file_path);
	$ret = check_div($contents);
	echo " $ret";

	echo "\n";

}

//このhtmlのdivタグが正しいかチェックする
function check_div($html) {

	$div_count = 0;
	$pos_start = 0;
	$div_start = strpos($html, "<div", $pos_start);
	$div_end = strpos($html, "</div>", $pos_start);
	//if ($div_start === false && $div_end === false) return "OK";
	while ($div_start || $div_end) {
		if ($div_start > 0 && $div_start < $div_end) {
			$div_count++;
			$pos_start = $div_start + 4;
		} else if ($div_end > 0 && $div_start > $div_end) {
			$div_count--;
			$pos_start = $div_end + 6;
		} else if ($div_end > 0 && $div_start == 0) {
			$div_count--;
			$pos_start = $div_end + 6;
		} else if ($div_start > 0 && $div_end == 0) {
			$div_count++;
			$pos_start = $div_start + 4;
		} else {
			//echo "[$div_start] [$div_end]\n";
			//exit;
			//$pos_start = $pos_start + 6;
		}
		if ($div_count < 0) return "NG";
		$div_start = strpos($html, "<div", $pos_start);
		$div_end = strpos($html, "</div>", $pos_start);
		if ($div_start == false && $div_end == false) break;
	}
	if ($div_count != 0) {
		echo " $div_count ";
		return "NG";
	}
	return "OK";

}

?>
