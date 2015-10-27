<?

if (!defined('PHP_INT_MIN')) {
	define('PHP_INT_MIN', ~PHP_INT_MAX);
}

if (!function_exists('boolval')) {
	function boolval($val) {
		return (bool)$val;
	}
}

function starts_with($str, $sub) {
	return (strncmp($str, $sub, strlen($sub)) == 0);
}

function ends_with($str, $sub) {
	return (strcmp(substr($str, -strlen($sub)), $sub) == 0);
}

function array_last($array, $default = null) {
	if (count($array))
		return $array[count($array) - 1];
	return $default;
}

function array_value($array, $key, $default = null) {
	if (!is_array($array)) return $default;
	if (array_key_exists($key, $array)) return $array[$key];
	else return $default;
}

function array_value_or_key($array, $key) {
	if (!is_array($array)) return $key;
	if (array_key_exists($key, $array)) return $array[$key];
	else return $key;
}

/**
 * array_coalesce((array)$array, (string)$key1, (string)$key2, ...)
 */
function array_coalesce() {
	$args = func_get_args();
	$array = array_shift($args);
	while ($key = array_shift($args))
		if (array_key_exists($key, $array))
			return $array[$key];
	return null;
}

function array_add(&$result) {
	$args = func_get_args();
	array_shift($args);

	while (($array = array_shift($args)) !== null) {
		for ($i = 0; $i < count($array); $i++)
			$result[$i] += $array[$i];
	}

	return $result;
}

function array_add_copy() {
	$args = func_get_args();
	$result = array_shift($args);

	while (($array = array_shift($args)) !== null) {
		for ($i = 0; $i < count($array); $i++)
			$result[$i] += $array[$i];
	}

	return $result;
}

function array_remove_value(&$array, $remove) {
	foreach ($array as $key => $value) {
		if ($array[$key] == $remove) unset($array[$key]);
	}
}

function array_single_quote_keys($array) {
	$result = array();
	foreach ($array as $key => $value)
		$result["'$key'"] = $value;
	return $result;
}

function array_single_quote_values($array) {
	$result = array();
	foreach ($array as $key => $value)
		$result[$key] = "'$value'";
	return $result;
}

function array_double_quote_keys($array) {
	$result = array();
	foreach ($array as $key => $value)
		$result["\"$key\""] = $value;
	return $result;
}

function array_double_quote_values($array) {
	$result = array();
	foreach ($array as $key => $value)
		$result[$key] = "\"$value\"";
	return $result;
}

/**
 * array_intersect_value(['index', 'members'], 'show') -> 'index'
 * array_intersect_value(['index', 'members'], 'show', 'members') -> 'members'
 * array_intersect_value(['index', 'members'], 'members', 'index') -> 'members'
 */
function array_intersect_value($array, $value, $if_empty = null) {
	$intersection = array_intersect($array, [$value]);
	$result = array_shift($intersection);
	if ($result === null)
		return $if_empty === null ? reset($array) : $if_empty;
	return $result;
}

/**
 * array_intersect_keys((array)$array, (array)$keys)
 * array_intersect_keys((array)$array, (string)$key1, (string)$key2, ...)
 */
function array_intersect_keys() {
	$args = func_get_args();
	$array = array_shift($args);
	if (is_array($args[0]))
		$args = $args[0];
	$result = array();
	foreach ($array as $key => $value) {
		if (in_array($key, $args))
			$result[$key] = $value;
	}
	return $result;
}

/**
 * array_subtract_keys((array)$array, (array)$keys)
 * array_subtract_keys((array)$array, (string)$key1, (string)$key2, ...)
 */
function array_subtract_keys() {
	$args = func_get_args();
	$array = array_shift($args);
	if (is_array($args[0]))
		$args = $args[0];
	$result = array();
	foreach ($array as $key => $value) {
		if (!in_array($key, $args))
			$result[$key] = $value;
	}
	return $result;
}

function array_walk_copy($array, $function) {
	array_walk($array, $function);
	return $array;
}

function flash($id, $message) {
	$_SESSION['flash'][$id] = $message;
}

function get_flash($id) {
	return array_value($_SESSION['flash'], $id, '');
}

function clear_flash($id) {
	$flash = get_flash($id);
	unset($_SESSION['flash'][$id]);
	return $flash;
}

function flash_empty($id) {
	return array_value($_SESSION['flash'] ?: [], $id, '') == '';
}

function url_query_string($params = array()) {
	$query_string = '';
	$separator = '?';
	foreach ($params as $key => $value) {
		$query_string .= $separator.$key.'='.urlencode($value);
		$separator = '&';
	}
	return $query_string;
}

function get_params() {
	$get = $_GET;
	unset($get['uri']);
	unset($get['request_uri']);
	return $get;
}

function url_query_string_merge_with_get($params = array()) {
	return url_query_string(array_merge(get_params(), $params));
}

function url($url, $params = array()) {
	return REQUEST_BASE.$url.url_query_string($params);
}

function external_url($url) {
    if (preg_match("/^\w+:\/\//", $url))
        return $url;
    return 'http://'.$url;
}

function camelize($string) {
	return preg_replace('/(?:^|_)(.?)/e', "strtoupper('$1')", $string);
}

function underscore($string) {
	return strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $string));
}

function truncate($string, $length, $end = '...', $encoding = 'UTF-8') {
	if (mb_strlen($string, $encoding) <= $length)
		return $string;
	return rtrim(mb_substr($string, 0, $length - mb_strlen($end, $encoding), $encoding)).$end;
}

function simplify($string, $charlist = ' \t') {
	return trim(preg_replace("/[$charlist]+/", ' ', $string));
}

function slugify($text, $separator = '-') {
	// replace non-letters or digits with -
	$text = preg_replace('~[^\\pL\d]+~u', $separator, $text);

	// trim
	if (!blank($separator))
		$text = trim($text, $separator);

	// workaround
	$text = str_replace(['á', 'ä', 'č', 'ď', 'é', 'ě', 'í', 'ĺ', 'ľ', 'ň', 'ó', 'ô', 'ŕ', 'ř', 'š', 'ť', 'ú', 'ý', 'ž'],
						['a', 'a', 'c', 'd', 'e', 'e', 'i', 'l', 'l', 'n', 'o', 'o', 'r', 'r', 's', 't', 'u', 'y', 'z'], $text);
	$text = str_replace(['Á', 'Ä', 'Č', 'Ď', 'É', 'Ě', 'Í', 'Ĺ', 'Ľ', 'Ň', 'Ó', 'Ô', 'Ŕ', 'Ř', 'Š', 'Ť', 'Ú', 'Ý', 'Ž'],
						['a', 'a', 'c', 'd', 'e', 'e', 'i', 'l', 'l', 'n', 'o', 'o', 'r', 'r', 's', 't', 'u', 'y', 'z'], $text);

	// transliterate
	$text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

	// lowercase
	$text = strtolower($text);

	// remove unwanted characters
	$text = preg_replace('~[^'.$separator.'\w]+~', '', $text);

	return $text;
}

function blank($string, $trim = false) {
	if ($trim)
		$string = trim($string);
	return $string === null || $string === '';
}

function coalesce($value, $alt_value = '') {
	if (empty($value))
		return $alt_value;
	return $value;
}

function coalesce_blank($value, $alt_value = '') {
	if (blank($value))
		return $alt_value;
	return $value;
}

function coalesce_with_closure($value, $closure) {
	if (empty($value))
		return $closure();
	return $value;
}

function coalesce_blank_with_closure($value, $closure) {
	if (blank($value))
		return $closure();
	return $value;
}

function sql_implode_fields($fields, $separator = ' ') {
	if (count($fields)) {
		return 'COALESCE('.implode(", '') || '$separator' || COALESCE(", $fields).', \'\')';
	}
	return '';
}

function sql_apply_function_to_fields($function, $fields) {
	$result = '';
	foreach ($fields as $field)
		$result .= (empty($result) ? '' : ', ')."$function($field) AS $field";
	return $result;
}

/*
 * sql_query_by_expanding_arrays('SELECT ARRAY[1, 2] AS array', ['array' => ['column1', 'column2']])
 */
function sql_query_by_expanding_arrays($query, $arrays) {
	$columns_string = '';
	foreach ($arrays as $array => $columns) {
		$i = 1;
		foreach ($columns as $column) {
			$columns_string .= ", {$array}[$i] AS $column";
			$i++;
		}
	}
	return "SELECT *$columns_string FROM ($query) AS t";
}

function tr() {
	$args = func_get_args();
	global $i18n;
	$environment_is_debug = DEBUG && (!defined('TEST') || !TEST);
	if ($environment_is_debug && $i18n !== null && !array_key_exists($args[0], $i18n)) {
		global $controller;
		global $missing_translations;
		$filename = "missing_translations.$controller->i18n.php";
		if ($missing_translations[$controller->i18n] === null)
			$missing_translations[$controller->i18n] = explode("\n", @file_get_contents($filename));
		$missing_translation = "\$i18n['$args[0]'] = '$args[0]';";
		if (!in_array($missing_translation, $missing_translations[$controller->i18n])) {
			$phptag = '<?';
			if (!in_array($phptag, $missing_translations[$controller->i18n])) {
				$missing_translations[$controller->i18n][] = $phptag;
				file_put_contents($filename, "$phptag\n", FILE_APPEND | LOCK_EX);
			}
			$timestamp = '// '.date('Y-m-d');
			if (!in_array($timestamp, $missing_translations[$controller->i18n])) {
				$missing_translations[$controller->i18n][] = $timestamp;
				file_put_contents($filename, "$timestamp\n", FILE_APPEND | LOCK_EX);
			}
			$action = "// $controller->module/$controller->controller/$controller->action";
			if (!in_array($action, $missing_translations[$controller->i18n])) {
				$missing_translations[$controller->i18n][] = $action;
				file_put_contents($filename, "$action\n", FILE_APPEND | LOCK_EX);
			}
			$missing_translations[$controller->i18n][] = $missing_translation;
			file_put_contents($filename, "$missing_translation\n", FILE_APPEND | LOCK_EX);
		}
	}
	$string = array_value_or_key($i18n, $args[0]);
	for ($i = 1; $i < count($args); $i++)
		$string = preg_replace("/(^|[^%]+)%$i/", '${1}'.$args[$i], $string);
	return $string;
}

function autoload($class_name) {
	$class_name = underscore($class_name);
	if (ends_with($class_name, 'controller'))
		require_once "controllers/$class_name.php";
	else if (ends_with($class_name, 'trait'))
		require_once "traits/$class_name.php";
	else if (ends_with($class_name, 'svc') || ends_with($class_name, 'service'))
		require_once "services/$class_name.php";
	else if (file_exists("models/$class_name.php"))
		require_once "models/$class_name.php";
}

spl_autoload_register('autoload');

function ob($command, &$output) {
	ob_start();
	$result = eval($command);
	$output = ob_get_contents();
	ob_end_clean();
	return $result;
}

function ob_f($function, &$output) {
	ob_start();
	$result = $function();
	$output = ob_get_contents();
	ob_end_clean();
	return $result;
}

function ob_m($object, $method, &$output) {
	ob_start();
	$result = $object->$method();
	$output = ob_get_contents();
	ob_end_clean();
	return $result;
}

function rgb2hex($rgb, $uppercase = true, $shorten = true) {
	$out = "";

	if ($shorten && array_sum($rgb) % 17 !== 0)
		$shorten = false;

	foreach ($rgb as $c) {
		$hex = base_convert($c, 10, 16);

		if ($shorten) $out .= $hex[0];
		else $out .= ($c < 16) ? ("0$hex") : $hex;
	}

	return $uppercase ? strtoupper($out) : $out;
}

?>
