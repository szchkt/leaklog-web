<?

class LoggerServiceTest extends DBTestCase {

	public function test_log_creates_a_log_model() {
		LoggerService::log(Log::TYPE_WARNING,
			'this is a message',
			'users',
			1000,
			9999);
		$log = Log::find()->order_by('id desc');
		$this->assertEquals(Log::TYPE_WARNING, $log->type);
		$this->assertEquals('this is a message', $log->message);
		$this->assertEquals('users', $log->table_name);
		$this->assertEquals(1000, $log->item_id);
		$this->assertEquals(9999, $log->user_id);
	}

}

?>
