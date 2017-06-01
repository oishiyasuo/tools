<?php
/**
 * HTMLを読み込みphpとテンプレートを出力する
 *
 * @created by Ooishi Yasuo
 * @update 2017-01-20
 */
$since = "2017-01-20";
$author = "ooishiyasuo@gmail.com";
$input_list = array();
$error_list = array();
$routs_list = array('product', 'news');

//パラメータに入力ディレクトリ名と出力ファイル名
$input_dir = $argv[1];
$output_dir = $argv[2];

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

//ディレクトリが存在するか
if (file_exists($output_dir) == false) {
	echo "dir not exist($output_dir)\n";
	exit;
}

//それはディレクトリか？
if (is_dir($output_dir) == false) {
	echo "is not dir($output_dir)\n";
	exit;
}

check_in_dir($input_dir, $output_dir);
//echo "input_list=";
//print_r($input_list);

//１ディレクトリ配下を調べる
function check_in_dir($input_path, $output_dir) {

	$dh = opendir($input_path);
	if ($dh == false) {
		echo "opendir failed($input_path)\n";
		exit;
	}
	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") continue;
		$file_path = $input_path . "\\" .$file;
		if (is_dir($file_path) == true) {
			find_first_dir($file_path, $output_dir);
			check_in_dir($file_path, $output_dir);
		} else {
			convert_file($file_path);
		}
	}

}

//パスを置換して出力する
function convert_file($file_path) {

	global $input_dir;

	//echo "$file_path\n";
	$path = str_replace($input_dir . "\\" , "", $file_path);
	//echo "$path\n";

}

//最初のディレクトリならば
function find_first_dir($input_path, $output_dir) {

	global $input_dir;

	$path = str_replace($input_dir . "\\" , "", $input_path);
	$dir_list = explode("\\", $path);
	if (count($dir_list) == 1) {
		create_controler($path);
	}
	//print_r($dir_list);

}

//コントローラーを作成する
function create_controler($name) {

	global $error_list;

	if ($name == "common") return;
	if ($name == "css") return;
	if ($name == "fonts") return;
	if ($name == "img") return;
	if ($name == "js") return;
	$error_list = array();
	create_view($name);
	create_model($name);
	create_controller($name);
	echo $name;
	echo "\n";

}

//コントローラーを作成する
function create_controller($name) {

	global $input_dir;
	global $output_dir;
	global $since;
	global $author;
	global $error_list;

	$dir_name = $output_dir . "\\Controller";
	@mkdir($dir_name);

	$title_name = strtoupper(substr($name,0,1)) . substr($name,1);
	$file_name = $title_name . "Controller.php";

	$file_path = $dir_name . "\\". $file_name;

	$text = "<?php\n";
	$text .= "/**\n";
	$text .= " * $title_name\n";
	$text .= " *\n";
	$text .= " * @since $since\n";
	$text .= " * @author $author\n";
	$text .= " */\n";

	$text .= "class ${title_name}Controller extends AppController {\n";
	$text .= "\n";
	$text .= "\t//使用するmodel\n";
	$text .= "\tvar ".'$'."uses = array('${title_name}');\n";
	if ($error_list) {
$text .= '
	var $session_data = array();
';
	}

	$text .= add_function($name);
	$text .= add_function_clear_error();
	$text .= add_function_session();

	$text .= "\n";
	$text .= "}\n";

	$text .= "?>\n";
	file_put_contents($file_path, $text);

}

function add_function($name) {

	global $input_dir;

	$input_path = $input_dir . "\\" . $name;
	$dh = opendir($input_path);
	if ($dh == false) {
		echo "opendir failed($input_path)\n";
		exit;
	}

	$text = "";
	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") continue;
		$file_path = $input_path . "\\" .$file;
		if (is_file($file_path) == true) {
			$path_parts = pathinfo($file_path);
			if ($path_parts['extension'] == "html") {
				$text .= make_function($path_parts['filename']);
				//echo $file_path;
				//echo "\n";
			}
		} else if (is_dir($file_path) == true) {
			$path_parts = pathinfo($file_path);
			if ($path_parts['filename'] == "css") continue;
			if ($path_parts['filename'] == "img") continue;
			$text .= make_function($path_parts['filename'], $file_path, $name);
		}
	}

	return $text;

}

function make_function($filename, $file_path = null, $app_name = null) {

	global $routs_list;

	$routs_flag = false;
	$file_list = array();
	foreach ($routs_list as $routs) {
		if ($routs == $app_name) $routs_flag = true;
	}
	if ($file_path) {
		if ($routs_flag == false) {
			$file_list = get_file_list($file_path);
		}
	}
	$text = "";

	if ($routs_flag && $file_path) return "";

	$text .= "\n";
	$text .= "\t//function comment\n";
	$text .= "\tpublic function ${filename}() {\n";
	if ($file_path) {
		$text .= "\t\t";
		$text .= '$mode = $this->params["pass"][0];';
		$text .= "\n";
		$c = 0;
		foreach($file_list as $name) {
			$text .= "\t\t";
			if ($c > 0) $text .= "} else ";
			$text .= 'if ($mode == "' . $name . '") {';
			$text .= "\n";
			$c++;
		}
		$text .= "\t\t}\n";
	}
	if ($file_list == false) {
		if ($filename == "input") {
			$text .= add_read_session($filename);
			$text .= "\t\t" . '$' . "this->clear_error();\n";
			$text .= add_confirm_back($filename);
		} else if ($filename == "confirm") {
			$text .= add_validates($app_name);
		}
	}
	$text .= "\t}\n";

	if ($file_list) {
		foreach($file_list as $name) {
			$text .= make_function_1($filename, $name);
			if ($name == "input") {
				$text .= make_function_input_back($filename."_".$name);
			}
		}
	}

	return $text;

}

function make_function_1($filename, $name) {

	$text  = "\n";
	$text .= "\t//function comment\n";
	$text .= "\tpublic function ${filename}_${name}() {\n";
	if ($name == "input") {
		$text .= add_read_session($filename."_".$name);
		$text .= "\t\t" . '$' . "this->clear_error();\n";
		$text .= add_confirm_back($filename."_".$name);
	} else if ($name == "confirm") {
		$text .= add_validates($appname);
	}
	$text .= "\t\t" . '$' . "this->render('${filename}_${name}');\n";
	$text .= "\t}\n";

	return $text;

}

function make_function_input_back($input) {
$text = '
	//確認画面＞戻る＞入力画面
	private function ' . $input . '_back() {
		$this->clear_error();
		$this->render("' . $input . '");
	}
';
	return $text;
}

function get_file_list($input_path) {

	$file_list = array();
	$dh = opendir($input_path);
	if ($dh == false) {
		echo "opendir failed($input_path)\n";
		exit;
	}
	$text = "";
	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") continue;
		$file_path = $input_path . "\\" .$file;
		if (is_file($file_path) == true) {
			$path_parts = pathinfo($file_path);
			if ($path_parts['extension'] == "html") {
				$filename = $path_parts['filename'];
				if (strpos($filename, "error") !== false) continue;
				$file_list[] = $filename;
			}
		}
	}
	return $file_list;

}

//ビューを作成する
function create_view($name) {

	global $input_dir;
	global $output_dir;
	global $model_name;

	$model_name = strtoupper(substr($name,0,1)) . substr($name,1);
	$dir_name = $output_dir . "\\View";
	@mkdir($dir_name);

	$title_name = strtoupper(substr($name,0,1)) . substr($name,1);
	$dir_name .= "\\$title_name";
	@mkdir($dir_name);

	$input_path = $input_dir . "\\" . $name;
	$dh = opendir($input_path);
	if ($dh == false) {
		echo "opendir failed($input_path)\n";
		exit;
	}

	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") continue;
		$file_path = $input_path . "\\" .$file;
		if (is_file($file_path) == true) {
			$path_parts = pathinfo($file_path);
			if ($path_parts['extension'] == "html") {
				output_template($file_path, $title_name, $path_parts['filename']);
				//echo $file_path;
				//echo "\n";
			}
		} else if (is_dir($file_path) == true) {
			$path_parts = pathinfo($file_path);
			$filename1 = $path_parts['filename'];
			$input_path2 = $file_path;
			$dh2 = opendir($input_path2);
			if ($dh2 == false) {
				echo "opendir failed($input_path2)\n";
				exit;
			}
			while (($file2 = readdir($dh2)) !== false) {
				if ($file2 == "." || $file2 == "..") continue;
				$file_path = $input_path2 . "\\" .$file2;
				if (is_file($file_path) == true) {
					$path_parts = pathinfo($file_path);
					if ($path_parts['extension'] == "html") {
						$filename = $filename1 . "_" . $path_parts['filename'];
						output_template($file_path, $title_name, $filename);
						//echo $file_path;
						//echo "\n";
					}
				}
			}
		}
	}

}

//テンプレートファイルを編集して適切な場所に保存する
function output_template($file_path, $title_name, $filename) {

	global $output_dir;
	global $head_php;

	$text = file_get_contents($file_path);

	$head_php = "";
	$text = edit_template($text);

	$head_php = "<?php\n" . $head_php . "?>\n";
	$text = $head_php . $text;

	if (strpos($filename, "input") !== false) {
		$action = "confirm";
	} else if (strpos($filename, "confirm") !== false) {
		$action = "complete";
	} else {
		$action = "xxxxx";
	}
	$text = str_replace('<form>', '<form action="/' . strtolower($title_name) . '/' . $action . '" method="POST">', $text);

	$out_file_path = $dir_name = $output_dir . "\\View\\" . $title_name. "\\" . $filename . ".ctp";
	file_put_contents($out_file_path, $text);

}

//テンプレートファイルを加工する
function edit_template($text) {

	$text = error_display($text);
	$text = input_type_text($text);
	$text = input_type_select($text);
	$text = input_type_textarea($text);
	return $text;

}

//テキスト入力の部分を加工する
function input_type_text($text) {

	$c = 0;
	do {
		$text_prev = $text;
		$text = change_text_once($text);
		$c++;
		if ($c > 100) break;
	} while($text_prev != $text);
	return $text;

}

//テキスト入力の部分を１つ加工する
function change_text_once($text) {

	$change_text = false;
	$pos_start = 0;
	$c = 0;
	do {
		$pos_start = strpos($text, "<input ", $pos_start);
		if ($pos_start !== false) {
			$pos_end = strpos($text, ">", $pos_start+1);
			$input_length = $pos_end - $pos_start + 1;
			$input_text = substr($text, $pos_start, $input_length);
			$change_text = get_change_text($input_text);
			if ($change_text) {
				$text = substr_replace($text, $change_text, $pos_start, $input_length);
			}
			$pos_start += 7;
		}
		$c++;
		if ($c > 100) break;
	} while ($change_text == false && $pos_start !==false);
	return $text;

}

//テキスト入力フィールド
function get_change_text($input_text) {

	global $model_name;
	global $input_list;

	$input_text = str_replace('<input', '', $input_text);
	$input_text = str_replace('>', '', $input_text);
	$array_text = explode(" ", $input_text);
	$element_list = array();
	foreach($array_text as $text1) {
		$array_text1 = explode("=", $text1);
		if (count($array_text1) == 2) {
			$element_list[$array_text1[0]] = str_replace('"', '', $array_text1[1]);
		}
	}
	//echo $input_text;
	//echo "\n";
	//print_r($element_list);
	$name = $element_list['id'];
	$type = $element_list['type'];
	if ($type != "text" && $type != "email" && $type != "password" && $type != "tel") return false;

	$change_text = "<?php ";
	if ($type == "password") {
		$change_text .= 'echo $this->Form->password(';
	} else {
		$change_text .= 'echo $this->Form->text(';
	}
	$input_list[$model_name][$name] = $name;
	$change_text .= "'$model_name.$name'";
	$change_text .= ", array(";
	$array_text = "";
	foreach($element_list as $key => $value) {
		if ($key == "type") continue;
		if ($key == "value") continue;
		if ($array_text) $array_text .= ", ";
		if ($key == "class") {
			$array_text .= "'class'=>'" . '$' . "class'";
		} else {
			$array_text .= "'$key'=>'$value'";
		}
	}
	$change_text .= $array_text;
	$change_text .= ")";
	$change_text .= ");";
	$change_text .= " ?>";
	return $change_text;

}

//selectの部分を加工する
function input_type_select($text) {

	$c = 0;
	do {
		$text_prev = $text;
		$text = change_select_once($text);
		$c++;
		if ($c > 100) break;
	} while($text_prev != $text);
	return $text;

}

//selectの部分を１つ加工する
function change_select_once($text) {

	$change_text = false;
	$pos_start = 0;
	$c = 0;
	do {
		$pos_start = strpos($text, "<select ", $pos_start);
		if ($pos_start !== false) {
			$pos_end = strpos($text, "</select>", $pos_start+1);
			$input_length = $pos_end - $pos_start + 1;
			$input_text = substr($text, $pos_start, $input_length);
			$change_text = get_change_select($input_text);
			if ($change_text) {
				$text = substr_replace($text, $change_text, $pos_start, $input_length + 8);
			}
			$pos_start += 7;
		}
		$c++;
		if ($c > 100) break;
	} while ($change_text == false && $pos_start !==false);
	return $text;

}

//select入力フィールド
function get_change_select($input_text) {

	global $model_name;
	global $head_php;

	$option_list = array();
	$array_line = explode("\n", $input_text);
	foreach($array_line as $line_text) {
		if (strpos($line_text, "<select") !== false) {
			$line_text = str_replace('<select ', '', $line_text);
			$line_text = str_replace('>', '', $line_text);
			$array_text = explode(" ", $line_text);
			$element_list = array();
			foreach($array_text as $text1) {
				$array_text1 = explode("=", $text1);
				if (count($array_text1) == 2) {
					$element_list[$array_text1[0]] = str_replace('"', '', $array_text1[1]);
				}
			}
print_r($element_list);
			$name = $element_list['id'];
			$name = chop($name);
		} else if (strpos($line_text, "<option") !== false) {
			$line_text = str_replace('<option>', '', $line_text);
			$line_text = str_replace('</option>', '', $line_text);
			$pos_start = strpos($line_text, "<option");
			if ($pos_start !== false) {
				$pos_end = strpos($line_text, ">", $pos_start + 1);
				$option_length = $pos_end - $pos_start + 1;
				$line_text = substr_replace($line_text, "", $pos_start, $option_length);
			}
			$option_list[] = $line_text;
		}
	}

	$head_add = '$option_' . $name . " = array(\n";
	foreach($option_list as $option) {
		$option = chop($option);
		$head_add .= "\t'" . $option ."' => '" . $option . "',\n";
	}
	$head_add .= ");\n";

	$head_add .= '$attributes_' . $name . " = array(\n";
	foreach ($element_list as $key => $value) {
		$head_add .= "\t'$key' => '$value',\n";
	}
	$head_add .= ");\n";

	$head_php .= $head_add;

	$change_text = "<?php ";
	$change_text .= 'echo $this->Form->select(';
	$change_text .= "'$model_name.$name'";
	$change_text .= ', $option_' . $name;
	$change_text .= ', $attributes_' . $name;
	$change_text .= ");";
	$change_text .= " ?>";
	return $change_text;

}

//error表示部分
function error_display($text) {

	global $error_list;

	$array_line = explode("\n", $text);
	foreach($array_line as $key => $line) {
		$pos = strpos($line, '<p class="error">');
		if ($pos === false) {
			$error_count =0;
			continue;
		}
		if ($error_count > 0) {
			$array_line[$key] = "delete";
		} else {
			$name = get_name($line, $text);
			$error_list[$name] = $name;
			$line = '<?php $class = "inputtxt"; if ($' . $name . '_error) { $class = "inputtxt error"; ?>';
			$line .= "\n";
			$line .= '<p class="error">* <?php echo $' . $name . '_error; ?></p>';
			$line .= "\n";
			$line .= '<?php } ?>';
			$array_line[$key] = $line;
		}
		$error_count++;
	}
	$text = implode("\n", $array_line);
	$text = str_replace("delete\n", "", $text);
	return $text;

}

function get_name($line, $text) {

	$pos = strpos($text, $line);
	$pos_start = strpos($text, 'id="', $pos) + 4;
	$pos_end = strpos($text, '"', $pos_start);
	$name = substr($text, $pos_start, $pos_end - $pos_start);
	return $name;

}

//モデルを作成する
function create_model($name) {

	global $input_dir;
	global $output_dir;
	global $since;
	global $author;

	$dir_name = $output_dir . "\\Model";
	@mkdir($dir_name);

	$title_name = strtoupper(substr($name,0,1)) . substr($name,1);
	$file_name = $title_name . "Model.php";

	$file_path = $dir_name . "\\". $file_name;

	$text = "<?php\n";
	$text .= "/**\n";
	$text .= " * $title_name\n";
	$text .= " *\n";
	$text .= " * @since $since\n";
	$text .= " * @author $author\n";
	$text .= " */\n";

	$text .= "class ${title_name} extends AppModel {\n";
	$text .= "\n";

	$text .= add_validate($title_name);

	$text .= "\n";
	$text .= "}\n";

	$text .= "?>\n";
	file_put_contents($file_path, $text);

}

//バリデーションチェック
function add_validate($title_name) {

	global $input_list;

	$validate = "\tpublic " . '$' ."validate = array(\n";
	if (isset($input_list[$title_name])) {
		$list = $input_list[$title_name];
		foreach($list as $bean) {
			$validate .= "\t\t'$bean' => array(\n";

			$validate .= "\t\t\t'rule1' => array(\n";
			$validate .= "\t\t\t\t'rule'    => 'notEmpty',\n";
			$validate .= "\t\t\t\t'message' => 'xxxxxxxxxx.',\n";
			$validate .= "\t\t\t),\n";

			$validate .= "\t\t\t'rule2' => array(\n";
			$validate .= "\t\t\t\t'rule'    => array('maxLength', 80),\n";
			$validate .= "\t\t\t\t'message' => 'xxxxxxxxxx.',\n";
			$validate .= "\t\t\t),\n";

			if ($bean == "email") {
				$validate .= "\t\t\t'rule3' => array(\n";
				$validate .= "\t\t\t\t'rule'    => 'email',\n";
				$validate .= "\t\t\t\t'message' => 'xxxxxxxxxx.',\n";
				$validate .= "\t\t\t),\n";
			}

			$validate .= "\t\t),\n";
		}
	}
	$validate .= "\t);\n";
	return $validate;

}

//テキスト入力の部分を加工する
function input_type_textarea($text) {

	$c = 0;
	do {
		$text_prev = $text;
		$text = change_textarea_once($text);
		$c++;
		if ($c > 100) break;
	} while($text_prev != $text);
	return $text;

}

//テキスト入力の部分を１つ加工する
function change_textarea_once($text) {

	$change_text = false;
	$pos_start = 0;
	$c = 0;
	do {
		$pos_start = strpos($text, "<textarea ", $pos_start);
		if ($pos_start !== false) {
			$pos_end = strpos($text, "</textarea>", $pos_start+1);
			$input_length = $pos_end - $pos_start + 11;
			$input_text = substr($text, $pos_start, $input_length);
			$change_text = get_change_textarea($input_text);
			if ($change_text) {
				$text = substr_replace($text, $change_text, $pos_start, $input_length);
			}
			$pos_start += 7;
		}
		$c++;
		if ($c > 100) break;
	} while ($change_text == false && $pos_start !==false);
	return $text;

}

//テキストエリア入力フィールド
function get_change_textarea($input_text) {

	global $model_name;
	global $input_list;

	$input_text = str_replace('<textarea ', '', $input_text);
	$input_text = str_replace('</textarea>', '', $input_text);
	$pos = strpos($input_text, ">");
	$input_text = substr($input_text, 0, $pos);
	$array_text = explode(" ", $input_text);
	$element_list = array();
	foreach($array_text as $text1) {
		$array_text1 = explode("=", $text1);
		if (count($array_text1) == 2) {
			$element_list[$array_text1[0]] = str_replace('"', '', $array_text1[1]);
		}
	}
	echo $input_text;
	echo "\n";
	print_r($element_list);
	$name = $element_list['name'];
	if ($name == false) return false;

	$change_text = "<?php ";
	$change_text .= 'echo $this->Form->textarea(';
	$input_list[$model_name][$name] = $name;
	$change_text .= "'$model_name.$name'";
	$change_text .= ", array(";
	$array_text = "";
	foreach($element_list as $key => $value) {
		if ($array_text) $array_text .= ", ";
		$array_text .= "'$key'=>'$value'";
	}
	$change_text .= $array_text;
	$change_text .= ")";
	$change_text .= ");";
	$change_text .= " ?>";
	return $change_text;

}

function add_function_clear_error() {

	global $error_list;

	if ($error_list == false) return;
	$text = "\n";
	$text .= "\tprivate function clear_error() {\n";
	foreach ($error_list as $error) {
		$text .= "\t\t". '$' . "this->set_data('${error}_error', '');\n";
	}
	$text .= "\t}\n";
	return $text;

}

function add_read_session($input) {
$text =
'		$this->session_data = $this->readSession("' . $input . '");
		if ($this->session_data) {
			$this->render(' . $input . ');
			return;
		}
';
	return $text;
}

function add_confirm_back($input) {
$text =
'		if ($this->request->data["mode"] == "confirm") {
			//確認画面から戻った
			$this->' . $input . '_back();
			return;
		}
';
	return $text;
}

function add_validates($appname) {
$text =
'		if ($this->' . $appname . '->validates()) {
			//validates ok
			$this->set_data("' . $appname . '_data",$this->data["' . $appname . '"]);
			$check = $this->_check_input_data();
			if ($check) {
				//入力画面に戻る
				$this->redirect_create_input();
			} else {
				$this->_confirm_product();
				$this->render("confirm");
			}
		} else {
			//validates ng
			$this->_check_validation();
			$check = $this->_check_input_data();
			//入力画面に戻る
			$this->redirect_create_input();
		}
';
	return $text;
}

function add_function_session() {

	global $error_list;

	if ($error_list == false) return;

$text ='
	//データを変数とセッション保存用にセットする
	private function set_data($key, $data) {
		$this->set($key, $data);
		$this->session_data[$key] = $data;
	}

	//セッションに保存する
	private function saveSession($key) {
		$this->Session->write($key, $this->session_data);
	}

	//セッションを読む
	private function readSession($key) {
		$data_list = $this->Session->read($key);
		if ($data_list == false) return false;
		foreach ($data_list as $key => $data) {
			$this->set($key, $data);
			if ($key == "data") {
				$this->data = $data;
			}
		}
		//読んだらセッションを削除する
		$this->Session->delete($key);
		return $data_list;
	}
';

	return $text;

}

?>
