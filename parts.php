<?php
/**
 * �t�@�C���̒��g�𕪉����ăt�@�C���ɏo��
 * <title>������</title>
 * <div class="subNavigation-inner">������</div>
 * <!--main-->������<!--/main-->
 * <ol>������</ol>
 *
 * @created by y-oishi@netyear.net
 * @update 2017-02-22
 */

//�p�����[�^�ɓ��̓f�B���N�g�����Əo�̓t�@�C����
$input_dir = $argv[1];
$output_dir = $argv[2];

//�f�B���N�g�������݂��邩
if (file_exists($input_dir) == false) {
	echo "dir not exist($input_dir)\n";
	exit;
}

//����̓f�B���N�g�����H
if (is_dir($input_dir) == false) {
	echo "is not dir($input_dir)\n";
	exit;
}

//�f�B���N�g�������݂��邩
if (file_exists($output_dir) == false) {
	echo "dir not exist($output_dir)\n";
	exit;
}

//����̓f�B���N�g�����H
if (is_dir($output_dir) == false) {
	echo "is not dir($output_dir)\n";
	exit;
}

check_in_dir($input_dir);

//�P�f�B���N�g���z���𒲂ׂ�
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
			parts_file($file_path);
		}
	}

}

//�t�@�C���̒��g�𕪉�����
function parts_file($file_path) {

	global $input_dir;
	global $output_dir;

	$path_parts = pathinfo($file_path);
	if (isset($path_parts['extension']) == false) return;
	if ($path_parts['extension'] != "html") return;
	$dirname = $path_parts['dirname'] . "\\" . $path_parts['filename'];
	$dirname = str_replace($input_dir, $output_dir, $dirname);

	echo "$dirname\n";
	@mkdir($dirname, 0777, true);
	$contents = file_get_contents($file_path);

	//�^�C�g��
	$pos_start = strpos($contents, "<title>") + 7;
	$end_start = strpos($contents, "</title>", $pos_start);
	$title = substr($contents, $pos_start, $end_start - $pos_start);
	$array = explode(" | ", $title);
	$title = $array[0];

	$file_outout = $dirname . "\\". $path_parts['filename'] . "_title.txt";
	file_put_contents($file_outout, $title);

	//�T�u�i�r�Q�[�V����
	$pos_start = strpos($contents, '<div class="subNavigation-inner">') + 35;
	$end_start = strpos($contents, "</div>", $pos_start);
	$subNavigation = substr($contents, $pos_start, $end_start - $pos_start - 6);

	$file_outout = $dirname . "\\". $path_parts['filename'] . "_subNavigation.txt";
	file_put_contents($file_outout, $subNavigation);

	//�p������
	$pos_start = strpos($contents, '<ol>') + 6;
	$end_start = strpos($contents, "</ol>", $pos_start);
	$breadcrumb = substr($contents, $pos_start, $end_start - $pos_start - 6);

	$file_outout = $dirname . "\\". $path_parts['filename'] . "_breadcrumb.txt";
	file_put_contents($file_outout, $breadcrumb);

	//�{��
	$pos_start = strpos($contents, '<!--main-->');
	$end_start = strpos($contents, "<!--/main-->", $pos_start) + 12;
	$main = substr($contents, $pos_start, $end_start - $pos_start);

	$file_outout = $dirname . "\\". $path_parts['filename'] . "_main.txt";
	file_put_contents($file_outout, $main);

}

?>
