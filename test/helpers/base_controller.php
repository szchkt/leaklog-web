<?

include "lib/system/controller.php";

class BaseController extends Controller
{
	public $output = null;

	public function redirect_to($url = null, $params = null) {
		throw new RedirectException($url, $params);
	}

	public function redirect_to_url($url) {
		throw new RedirectException($url);
	}

	public function redirect_back() {
		throw new RedirectException('back');
	}

	protected function render_layout() {
		ob_start();
		parent::render_layout();
		$this->output = ob_get_contents();
		ob_end_clean();
	}
}

class RedirectException extends Exception
{
	public $url = null;
	public $params = null;

	public function __construct($url, $params = nil) {
		$url_string = is_string($url) ? $url : serialize($url);

		parent::__construct("Redirected to {$url_string}");
		$this->url = $url;
		$this->params = $params;
	}
}

?>
