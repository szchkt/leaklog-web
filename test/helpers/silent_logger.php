<?

class SilentLogger {
	public $errors = [];
	public $informations = [];
	public $successes = [];

	public function error($message) {
		$this->errors[] = $message;
	}

	public function information($message) {
		$this->informations[] = $message;
	}

	public function success($message) {
		$this->successes[] = $message;
	}

	public function dump() {
		foreach ([
				'errors' => $this->errors,
				'information' => $this->informations,
				'successes' => $this->successes
			] as $type => $logs) {
			if (count($logs)) {
				echo $type.':';
				echo "\n";
				echo implode("\n", $logs);
			}
		}
	}
}

LoggerService::$logger = new SilentLogger;

?>
