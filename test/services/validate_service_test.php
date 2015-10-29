<?

class SampleModel extends Model {
	public function __construct() {
		parent::__construct();
		$this->set_table('sample_models');
	}
}

class ValidateServiceTest extends DBTestCase {

	public function test_validate_presence_returns_true() {
		$service = new ValidateService('name', [
			'presence' => true
		]);
		$instance = new SampleModel;
		$instance->name = 'Name';
		$this->assertTrue($service->validate($instance));
	}

	public function test_validate_presence_returns_false() {
		$service = new ValidateService('name', [
			'presence' => true
		]);
		$instance = new SampleModel;
		$this->assertFalse($service->validate($instance));
		$this->assertContains('Name is a required field!', LoggerService::$logger->errors);
	}

	public function test_validate_email_returns_false_if_not_present_and_required() {
		$service = new ValidateService('email', [
			'email' => true,
			'presence' => true
		]);
		$instance = new SampleModel;
		$this->assertFalse($service->validate($instance));
	}

	public function test_validate_email_returns_true_if_not_present() {
		$service = new ValidateService('email', [
			'email' => true
		]);
		$instance = new SampleModel;
		$this->assertTrue($service->validate($instance));
	}

	public function test_validate_email_returns_true() {
		$service = new ValidateService('email', [
			'email' => true
		]);
		$instance = new SampleModel;
		$instance->email = 'john@brown.com';
		$this->assertTrue($service->validate($instance));
	}

	public function test_validate_email_returns_false() {
		$service = new ValidateService('email', [
			'email' => true
		]);
		$instance = new SampleModel;
		$instance->email = 'email@bs';
		$this->assertFalse($service->validate($instance));
	}

	public function test_validate_postal_code_returns_true() {
		$service = new ValidateService('postal_code', [
			'postal_code' => true
		]);
		$instance = new SampleModel;
		$instance->postal_code = '910 01';
		$this->assertTrue($service->validate($instance));
	}

	public function test_validate_postal_code_returns_false() {
		$service = new ValidateService('postal_code', [
			'postal_code' => true
		]);
		$instance = new SampleModel;
		$instance->postal_code = 'ahfjh982 9';
		$this->assertFalse($service->validate($instance));
	}

}

?>
