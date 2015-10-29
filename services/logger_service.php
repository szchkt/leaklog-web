<?

class LoggerService {

	public static $logger;

	public static function error($message) {
		self::$logger->error($message);
	}

	public static function information($message) {
		self::$logger->information($message);
	}

	public static function success($message) {
		self::$logger->success($message);
	}

	public static function log($type, $message, $table_name = null, $item_id = null, $current_user_id = null) {
		$log = new Log;
		$log->type = $type;
		$log->message = $message;
		$log->table_name = $table_name;
		$log->item_id = $item_id;
		$log->user_id = $current_user_id;
		return $log->save();
	}

	public static function dump() {
		self::$logger->dump();
	}
}

class FlashLogger {
	public function error($message) {
		$this->flash('error', $message);
	}

	public function information($message) {
		$this->flash('message', $message);
	}

	public function success($message) {
		$this->flash('success', $message);
	}

	public function dump() {
	}

	private function flash($id, $message) {
		if (flash_empty($id)) {
			flash($id, $message);
		} else {
			$current_flash = get_flash($id);
			flash($id, $current_flash.'<br>'.$message);
		}
	}
}

LoggerService::$logger = new FlashLogger;

?>
