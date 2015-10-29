<?php

require_once 'test/helpers/base_controller.php';
require_once 'main/application/application_controller.php';

class ControllerTestCase extends DBTestCase
{
	private $expected_redirects = [];

	function setUp() {
		parent::setUp();
		$_GET = [];
		$_POST = [];
		$this->expected_redirects = [];
	}

	function set_get_arguments($args) {
		$_GET = $args;
	}

	function set_post_arguments($args) {
		$_POST = $args;
	}

	function get($uri) {
		$this->call_action($uri, 'GET');
	}

	function post($uri) {
		$this->call_action($uri, 'POST');
	}

	private function call_action($uri, $method) {
		global $map;
		$map = new Router($uri);

		include 'config/routes.php';

		try {
			$this->controller = $map->run($method);
		} catch (RedirectException $ex) {
			$this->handle_redirect_exception($ex);
		}

		if (count($this->expected_redirects) > 0) {
			$this->fail("Didn't redirect as expected");
		}
	}

	function should_redirect_to($url = null, $params = null) {
		$this->expected_redirects[] = serialize([$url, $params]);
	}

	function should_redirect_back() {
		$this->should_redirect_to('back');
	}

	function should_redirect_to_url($url) {
		$this->should_redirect_to($url);
	}

	private function handle_redirect_exception($ex) {
		$key = serialize([$ex->url, $ex->params]);
		if (in_array($key, $this->expected_redirects)) {
			$array_key = array_search($key, $this->expected_redirects);
			unset($this->expected_redirects[$array_key]);
		} else {
			throw $ex;
		}
	}

	function assertResponseContains($needle) {
		$this->assertContains($needle, $this->controller->output);
	}

}

?>
