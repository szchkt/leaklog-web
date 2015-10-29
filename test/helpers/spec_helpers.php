<?

function toCamelCase($string) {
	// underscored to upper-camelcase 
	// e.g. "this_method_name" -> "ThisMethodName" 
	return preg_replace('/(?:^|_)(.?)/e', "strtoupper('$1')", $string); 
}

function session_restart() {
}

function log_in_as_admin() {
	$_SESSION['s_user'] = DB_USER;
	$_SESSION['s_passwd'] = DB_PASSWD;
}

function log_in_as($user) {
	$_SESSION['s_user'] = $user->username;
	$_SESSION['s_passwd'] = 'pass';
}

?>
