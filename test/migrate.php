<?

require_once 'test/helpers/includes.php';

$service = new MigrationsService();
$service->execute();

echo "DONE";

?>
