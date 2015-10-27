<?

function message($id) {
	global $message;
	return $message[$id];
}

function error($code = '404') {
	include "public/$code.html";
	die();
}

function quit($error) {
	flash('system_error', $error);
	die();
}

function h($string) {
	return htmlspecialchars($string);
}

function attributes($array) {
	$attributes = '';
	foreach ($array as $key => $value) {
		$attributes .= " $key=\"".h($value).'"';
	}
	return $attributes;
}

function attr_if($condition, $attribute, $if_true, $if_false = null) {
	if ($condition) {
		if (is_array($attribute))
			return attributes(array_combine($attribute, $if_true));
		else
			return attributes([$attribute => $if_true]);
	} else if ($if_false !== null) {
		if (is_array($attribute))
			return attributes(array_combine($attribute, $if_false));
		else
			return attributes([$attribute => $if_false]);
	}
	return '';
}

function stylesheet_link_tag($name, $options = array()) {
	return '<link href="'.
		(strpos($name, '.') === false ? url("/public/stylesheets/$name.css") : url("/$name")).
		'" rel="stylesheet" type="text/css"'.attributes($options).">\n";
}

function javascript_include_tag($src, $public = false) {
	return '<script src="'.
		(strpos($src, '.') === false || $public ? url("/public/javascripts/$src.js") : url("/$src")).
		'" type="text/javascript"></script>'."\n";
}

function link_tag($content, $href, $options = array()) {
	return '<a href="'.$href.'"'.attributes($options).'>'.$content.'</a>';
}

function script_link_tag($content, $script, $options = array()) {
	return '<a href="#" onclick="'.$script.' return false;"'.attributes($options).'>'.$content.'</a>';
}

function checkbox_tag($name, $checked, $options = array()) {
	if (!array_key_exists('value', $options))
		$options['value'] = '1';
	return '<input type="checkbox" name="'.$name.'"'.($checked ? ' checked="checked"' : '').attributes($options).'>';
}

function form_tag($action, $content, $method = 'post', $options = array()) {
	?><form action="<?= $action ?>" method="<?= $method ?>" accept-charset="utf-8"<?= attributes($options) ?>>
<?php
	$content();
	?></form>
<?php
}

function nav_tab_link($link) {
	return '<span class="lborder"></span>'.$link.'<span class="rborder"></span>';
}

?>