<?

require_once 'test/helpers/includes.php';

$service = new FixturesService();
echo var_dump($service->load());

echo "done";

?>
