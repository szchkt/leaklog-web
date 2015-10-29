<?

class Log extends Model {

	const TYPE_ERROR = 0;
	const TYPE_WARNING = 1;
	const TYPE_MESSAGE = 2;

	function __construct() {
		parent::__construct();
		$this->set_table('logs');
	}

}

?>
