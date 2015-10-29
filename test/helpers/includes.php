<?

error_reporting(E_ERROR | E_WARNING | E_PARSE);
date_default_timezone_set('Europe/Bratislava');
setlocale(LC_TIME, 'sk_SK');
define('DEBUG', true);
define('TEST', true);
define('MODULES_PATH', '');
define('REQUEST_BASE', '');
define('APP_ROOT', getcwd());

define('DB_NAME', 'szchkt');
define('DB_USER', 'szchkt');
define('DB_PASSWD', 'zvazchkt');

// DATABASE
require_once 'test/database_config.php';
$dbconn = pg_pconnect('host=localhost port=5432 dbname='.TEST_DB_NAME.
	' user='.TEST_DB_USER.
	' password='.TEST_DB_PASSWD)
	or die('Nepodarilo sa spoji&#357; s datab&aacute;zou: ' . pg_last_error());

global $i18n;
$i18n = [];

require_once 'test/helpers/spec_helpers.php';
include_once('test/helpers/auto_loader.php');
require_once 'lib/system/helpers.php';
require_once 'lib/system/model.php';
require_once 'lib/system/html_helpers.php';
require_once 'main/application/application_helpers.php';

AutoLoader::registerDirectory('services');
AutoLoader::registerDirectory('models');
AutoLoader::registerDirectory('traits');

require_once 'lib/faker/autoload.php';
require_once 'test/helpers/factory.php';
require_once 'test/factories.php';
require_once 'test/helpers/silent_logger.php';

include_once('lib/countries.php');

// Router with an empty URL
require_once 'lib/system/router.php';
global $map;
$map = new Router('');
include 'config/routes.php';

?>
