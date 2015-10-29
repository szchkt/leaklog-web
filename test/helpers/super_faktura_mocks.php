<?

define('SUPERFAKTURA_EMAIL_CO_CHKT', 'mail@test.com');
define('SUPERFAKTURA_API_TOKEN_CO_CHKT', 'api token');

class SFApiClient {
	 public $email = '';
	 public $api_token = '';
	 public $client = [];
	 public $invoice = [];
	 public $items = [];

	public function __construct($email, $api_token) {
		$this->email = $email;
		$this->api_token = $api_token;
	}

	public function setClient($client) {
		$this->client = $client;
	}

	public function addItem($item) {
		$this->items[] = $item;
	}

	public function setInvoice($invoice) {
		$this->invoice = $invoice;
	}

	public function save() {
		return new MockResponse;
	}
}

class MockResponse {
	public static $errors = null;
	public $error = 0;
	public $data = null;
	public $error_message = 0;

	public function __construct() {
		if (self::$errors != null) {
			$this->error = 1;
			$this->error_message = self::$errors;
			self::$errors = null;
		} else {
			$this->data = new MockData;
		}
	}
}

class MockData {
	public $Invoice = null;
	public $PaymentLink = 'http://payment.link';

	public function __construct() {
		$this->Invoice = new MockInvoice;
	}
}

class MockInvoice {
	public $id = 1;
	public $token = 'token';
}

?>
