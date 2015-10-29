<?php

if (!class_exists('BaseController', false))
	class_alias('Controller', 'BaseController');

class ApplicationController extends BaseController {

	protected function app_init() {
		$this->before_filter('set_default_layout');
	}

	protected function set_default_layout() {
		$this->set_layout('layout/layout');
	}

}

?>
