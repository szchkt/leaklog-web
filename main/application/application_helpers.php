<?

function user_authenticate($company_id, $passwd_plaintext, $passwd, $passwd_salt) {
	$success = hash('sha256', "$passwd_plaintext::$passwd_salt") == $passwd ||
		hash('sha256', "$company_id::$passwd_plaintext::$passwd_salt") == $passwd;
	return $success;
}

/**
 * Returns user type, true if type equals 0, false if not logged in.
 * Returns PHP_INT_MAX if user is admin.
 */
function is_logged_in($recheck = false) {
	static $type = -1;

	if ($type >= 0 && !$recheck)
		return $type;

	if (!isset($_SESSION['s_user']) || !isset($_SESSION['s_passwd']))
		return false;

	if ($_SESSION['s_user'] == DB_USER && $_SESSION['s_passwd'] == DB_PASSWD)
		return PHP_INT_MAX;

	$username = pg_escape_string($_SESSION['s_user']);
	if (array_key_exists('s_admin_user', $_SESSION))
		$username = pg_escape_string($_SESSION['s_admin_user']);

	$passwd_correct = ($username == DB_USER && $_SESSION['s_passwd'] == DB_PASSWD);
	$company_id = null;
	$user_type = 0;

	if ($passwd_correct) {
		$company_id = 1;
		$user_type = 1;
	} else {
		$result = pg_query("SELECT company_id, passwd, passwd_salt, type FROM users WHERE username = '$username'");

		if ($row = pg_fetch_assoc($result)) {
			$company_id = $row['company_id'];
			$user_type = $row['type'];

			if (user_authenticate($company_id, $_SESSION['s_passwd'], $row['passwd'], $row['passwd_salt']))
				$passwd_correct = true;
		}
	}

	if (!$passwd_correct)
		return false;

	$logged_in_user = pg_escape_string($_SESSION['s_user']);
	$result = pg_query("SELECT company_id, type FROM users WHERE username = '$logged_in_user'");

	if ($row = pg_fetch_assoc($result)) {
		$type = $row['type'];
		if ($type == 0)
			$type = true;
		if ($username != DB_USER && $company_id != $row['company_id'])
			$type = false;
		if ($username != $logged_in_user && (($user_type & 1) == 0 || !$company_id))
			$type = false;
	} else {
		$type = false;
	}

	return $type;
}

function is_admin($recheck = false) {
	return $_SESSION['s_user'] == DB_USER && is_logged_in($recheck) == PHP_INT_MAX;
}

?>
