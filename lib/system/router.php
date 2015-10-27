<?php

class Router {
	private $uri;
	private $route = array();
	private $layouts = array();
	private $paths = array();
	private $module_for_controller = array();

	public function __construct($uri) {
		$this->uri = $uri;
	}

	public function set_layout_for_controller($controller, $layout) {
		$this->layouts[$controller] = $layout;
	}

	public function set_module_for_controller($controller, $module) {
		$this->module_for_controller[$controller] = $module;
	}

	public function module_for_controller($controller, $default = '') {
		return array_value($this->module_for_controller, $controller, $default);
	}

	public function root($params = array()) {
		$this->connect('', $params);
	}

	public function connect($regexp, $params = array()) {
		if (!empty($this->route) || !preg_match("|^$regexp/?$|", $this->uri, $matches))
			return;
		$this->route = array_merge($params, $matches);
		if (array_value($this->route, 'module', '') === false &&
			!array_key_exists($this->route['controller'], $this->module_for_controller))
			$this->route = array();
	}

	public function __call($name, $arguments) {
		if (count($arguments)) {
			$this->paths[$name] = $arguments;
			$this->connect($arguments[0], array_value($arguments, 1, array()));
			return;
		}

		$trace = debug_backtrace();
		trigger_error(
			'Undefined method via __call(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
		return null;
	}

	public function path($name) {
		return $this->paths[$name];
	}

	public function run($request_method = REQUEST_METHOD) {
		global $controller;

		if (empty($this->route) ||
			!array_key_exists('controller', $this->route) ||
			!array_key_exists('action', $this->route))
			error();

		// Module
		$controller = $this->route['controller'];
		$module = array_value($this->module_for_controller, $controller, array_value($this->route, 'module', 'main'));

		// Layout
		$format = array_value($this->route, 'format', 'html');
		$layout_path = MODULES_PATH.array_value($this->route, 'layout', "$module/$controller/$controller").".$format.php";
		if (!file_exists($layout_path)) {
			$layout_path = MODULES_PATH.array_value($this->layouts, $controller, "$module/$module").".$format.php";
			if (!file_exists($layout_path)) {
				$layout_path = MODULES_PATH."main/application/application.$format.php";
				if (!file_exists($layout_path))
					$layout_path = '';
			}
		}

		// Controller
		$helpers_path = MODULES_PATH."$module/${module}_helpers.php";
		if (file_exists($helpers_path))
			include_once $helpers_path;
		$controller_path = MODULES_PATH."$module/${module}_controller.php";
		if (file_exists($controller_path))
			include_once $controller_path;
		if ($controller != 'application') {
			$helpers_path = MODULES_PATH."$module/$controller/${controller}_helpers.php";
			if (file_exists($helpers_path))
				include_once $helpers_path;
			$controller_path = MODULES_PATH."$module/$controller/${controller}_controller.php";
			if (file_exists($controller_path))
				include_once $controller_path;
			else
				error();
		}
		$controller = camelize($controller).'Controller';
		$controller = new $controller;
		$controller->params = $this->route;
		$controller->module = $module;
		$controller->controller = $this->route['controller'];
		$controller->render_format = $format;
		$controller->request_method = $request_method;

		// Action
		$action = $this->route['action'];

		$controller->i18n_init();

		// Translations
		global $i18n_all;
		$i18n_all = [];
		$translations = $controller->required_locales($action);
		if (!in_array('default', $translations))
			$translations[] = 'default';
		$translation_found = false;
		foreach ($translations as $locale) {
			$i18n_all[$locale] = [];
			$i18n = &$i18n_all[$locale];
			$translations_path = "i18n/application.$locale.i18n.php";
			if (file_exists($translations_path)) {
				$translation_found = true;
				include_once $translations_path;
			}
			$translations_path = "i18n/$module/$module.$locale.i18n.php";
			if (file_exists($translations_path)) {
				$translation_found = true;
				include_once $translations_path;
			}
			$translations_path = "i18n/$module/$controller->controller/$controller->controller.$locale.i18n.php";
			if (file_exists($translations_path)) {
				$translation_found = true;
				include_once $translations_path;
			}
			if (!$translation_found)
				array_remove_value($translations, $locale);
		}
		$controller->set_locale(array_value($translations, 0, 'default'));

		$controller->init_all();

		$controller->render_layout_with_action($layout_path, $action);
		return $controller;
	}
}

?>
