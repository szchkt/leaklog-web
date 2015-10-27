<?php

session_start();
header('Expires: 0');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

chdir('../..');
define('APP_ROOT', getcwd());
define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
define('REQUEST_URI', $_GET['request_uri']);
define('REQUEST_BASE', substr(REQUEST_URI, 0, -strlen($_GET['uri'])));

include_once 'config/config.php';
include 'config/database.php';
$message = array();
include 'config/messages.php';

function session_restart($init_only = false) {
	$flash = $_SESSION['flash'];
	if (!$init_only) {
		session_destroy();
		session_start();
	}
	if (!array_key_exists('flash', $_SESSION))
		$_SESSION['flash'] = is_array($flash) ? $flash : array();
}
session_restart(true);

// Helpers
require_once 'lib/system/helpers.php';
require_once 'lib/system/html_helpers.php';
if (file_exists(MODULES_PATH.'main/application/application_helpers.php'))
	include MODULES_PATH.'main/application/application_helpers.php';

// Models
require_once 'lib/system/model.php';

// Controllers
require_once 'lib/system/controller.php';
if (!file_exists(MODULES_PATH.'main/application/application_controller.php'))
	error();
include MODULES_PATH.'main/application/application_controller.php';

// Router
require_once 'lib/system/router.php';

$map = new Router($_GET['uri']);

include 'config/routes.php';

$map->run();

?>
