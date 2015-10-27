<?php

class EndActionException extends Exception {
}

class ActionFilter {
	public $filter;
	public $options;

	function __construct($filter, $options) {
		$this->filter = $filter;
		$this->options = $options;
	}

	function has_option($option) {
		return array_key_exists($option, $this->options);
	}

	function applies_to($action) {
		if ($this->has_option('only') && !in_array($action, $this->options['only']))
			return false;
		if ($this->has_option('except') && in_array($action, $this->options['except']))
			return false;
		return true;
	}
}

class Controller {
	public $params = array();
	public $module;
	public $controller;
	public $action;
	public $render_action;
	public $request_method = REQUEST_METHOD;
	private $layout_path;
	private $view_path;
	public $render_format;
	public $page_title;
	private $before_filters = array();
	private $around_filters = array();
	private $after_filters = array();
	private $get_variables = [];
	private $action_output = '';
	private $data = array();
	public $i18n = 'default';

	function __construct() {
		register_shutdown_function(array($this, 'end_action_output_buffering'));
	}

	public function i18n_init() {}
	protected function app_init() {}
	protected function module_init() {}
	protected function init() {}

	public function init_all() {
		$this->app_init();
		$this->module_init();
		$this->init();
	}

	public function get_controller_name() {
		return underscore(preg_replace('/Controller$/', '', get_class($this)));
	}

	public function page_title($default, $append_default = true) {
		if (blank($this->page_title) && !blank($this->heading()))
			$this->page_title = $this->heading();
		if (!blank($this->page_title))
			return $this->page_title.($append_default ? ' — '.$default : '');
		if (!blank($this->data['heading']))
			return $this->data['heading'].($append_default ? ' — '.$default : '');
		return $default;
	}

	protected function before_filter($filter, $options = array()) {
		$this->before_filters[] = new ActionFilter($filter, $options);
	}

	protected function prepend_before_filter($filter, $options = array()) {
		array_unshift($this->before_filters, new ActionFilter($filter, $options));
	}

	protected function around_filter($filter, $options = array()) {
		$this->around_filters[] = new ActionFilter($filter, $options);
	}

	protected function prepend_around_filter($filter, $options = array()) {
		array_unshift($this->around_filters, new ActionFilter($filter, $options));
	}

	protected function after_filter($filter, $options = array()) {
		$this->after_filters[] = new ActionFilter($filter, $options);
	}

	protected function prepend_after_filter($filter, $options = array()) {
		array_unshift($this->after_filters, new ActionFilter($filter, $options));
	}

	protected function register_get_variable($variable, $function = null) {
		$this->get_variables[$variable] = $function;
		$this->$variable = $function === null ? $_GET[$variable] : $function($_GET[$variable]);
	}

	public function required_locales($action) {
		return [$this->i18n];
	}

	public function set_locale($locale) {
		global $i18n_all;
		global $i18n;
		$i18n = $i18n_all[$locale];
		$this->i18n = $locale;
	}

	protected function set_layout($layout) {
		if (strpos($layout, '/') === false) {
			$this->layout_path = MODULES_PATH."$this->module/$this->controller/$layout.$this->render_format.php";
		} else {
			$this->layout_path = MODULES_PATH."$layout.$this->render_format.php";
		}
	}

	protected function render_partial($partial, $variables = array()) {
		if (strpos($partial, '/') === false) {
			$partial = MODULES_PATH."$this->module/$this->controller/_$partial.php";
		} else {
			$partial = explode('/', $partial);
			if ($partial[0] != $this->module) {
				if (file_exists(MODULES_PATH.$partial[0].'/'.$partial[0].'_helpers.php'))
					include_once MODULES_PATH.$partial[0].'/'.$partial[0].'_helpers.php';
			}
			if ($partial[1] != $this->controller && count($partial) > 2) {
				if (file_exists(MODULES_PATH.$partial[0].'/'.$partial[1].'/'.$partial[1].'_helpers.php'))
					include_once MODULES_PATH.$partial[0].'/'.$partial[1].'/'.$partial[1].'_helpers.php';
			}
			$partial[count($partial) - 1] = '_'.$partial[count($partial) - 1];
			$partial = MODULES_PATH.implode('/', $partial).'.php';
		}
		foreach ($variables as $key => $value) {
			if ($key != 'partial')
				$$key = $value;
		}
		include $partial;
	}

	public function render($view, $format = '') {
		if ($view == 'nothing') {
			$this->end_action_output_buffering();
			$this->view_path = '';
			return;
		}
		if (!empty($format))
			$this->render_format = $format;
		if (strpos($view, '/') === false) {
			$this->view_path = MODULES_PATH."$this->module/$this->controller/$view.$this->render_format.php";
			$this->render_action = $view;
		} else {
			$this->view_path = MODULES_PATH."$view.$this->render_format.php";
			$this->render_action = array_last(explode('/', $view));
		}
	}

	public function view() {
		$js_path = "public/javascripts/{$this->module}/{$this->controller}/{$this->render_action}.js";
		if (file_exists($js_path)) {
			echo "<script type=\"text/javascript\" src=\"/{$js_path}\"></script>\n";
		}
		$js_path .= '.php';
		if (file_exists($js_path)) {
			echo "<script type='text/javascript'>\n";
			include $js_path;
			echo "</script>\n";
		}
		include $this->view_path;
	}

	protected function action_output() {
		return $this->action_output;
	}

	protected function action_output_empty() {
		return blank($this->action_output);
	}

	protected function process_action_output($action_output) {}

	public function render_layout_with_action($layout, $action) {
		$this->layout_path = $layout;
		$this->view_path = MODULES_PATH."$this->module/$this->controller/$action.$this->render_format.php";
		$this->action = $action;
		$this->render_action = $action;
		$this->call_action();
		$this->render_layout();
	}

	protected function render_layout() {
		if (empty($this->view_path))
			return;
		if (getcwd() != APP_ROOT)
			chdir(APP_ROOT);
		if (!file_exists($this->view_path))
			error();
		if (empty($this->layout_path)) {
			$this->view();
		} else {
			include $this->layout_path;
		}
	}

	protected function call_action() {
		static $first_call = true;
		if ($first_call) {
			$first_call = false;
			ob_start();
		}
		while ($around_filter = array_shift($this->around_filters)) {
			if ($around_filter->applies_to($this->action)) {
				call_user_func(is_callable($around_filter->filter) ? $around_filter->filter : [$this, $around_filter->filter]);
				return;
			}
		}
		while ($before_filter = array_shift($this->before_filters)) {
			if ($before_filter->applies_to($this->action))
				call_user_func(is_callable($before_filter->filter) ? $before_filter->filter : [$this, $before_filter->filter]);
		}
		if (method_exists($this, $this->action)) {
			$method = new ReflectionMethod($this, $this->action);
			if ($method->isPublic()) {
				$args = array();
				$parameters = $method->getParameters();
				foreach ($parameters as $param) {
					$args[] = $this->params[$param->name];
				}
				try {
					$method->invokeArgs($this, $args);
				} catch (EndActionException $e) {}
			}
		}
		while ($after_filter = array_shift($this->after_filters)) {
			if ($after_filter->applies_to($this->action))
				call_user_func(is_callable($after_filter->filter) ? $after_filter->filter : [$this, $after_filter->filter]);
		}
		$this->end_action_output_buffering();
	}

	public function end_action($id = '', $message = '') {
		if (!empty($id))
			flash($id, $message);
		throw new EndActionException;
	}

	public function end_action_output_buffering() {
		static $done = false;
		if ($done) return;
		$this->action_output = ob_get_contents();
		ob_end_clean();
		$done = true;
		if (!blank($this->action_output, true))
			$this->process_action_output($this->action_output);
	}

	public function current_url($params = []) {
		return REQUEST_URI.url_query_string_merge_with_get($params);
	}

	public function referer_url($params = []) {
		$url = parse_url($_SERVER['HTTP_REFERER']);
		$url_params = [];
		if (array_key_exists('query', $url))
			parse_str($url['query'], $url_params);
		return $url['path'].url_query_string(array_merge($url_params, $params));
	}

	public function current_page_url($params = []) {
		if ($this->render_format == 'ajax')
			return $this->referer_url($params);
		return $this->current_url($params);
	}

	public function url_query_string_with_locale($params = array()) {
		if ($this->i18n != 'default' && !array_key_exists('locale', $params))
			$params['locale'] = $this->i18n;
		return url_query_string($params);
	}

	public function url($url = array(), $params = array()) {
		global $map;
		$url_r = '';
		$controller = array_value($url, 'controller', $this->controller);
		$module = $map->module_for_controller($controller);
		if (empty($module)) {
			$module = array_value($url, 'module', $this->module);
			if (!empty($module)) $url_r .= '/'.$module;
		}
		$prefix = array_value($url, 'prefix', '');
		if (!empty($prefix)) $url_r .= '/'.$prefix;
		if (!empty($controller)) $url_r .= '/'.$controller;
		$id = array_value($url, 'id', '');
		if (!empty($id)) $url_r .= '/'.$id;
		$action = array_value($url, 'action', '');
		if (!empty($action)) $url_r .= '/'.$action;
		$format = array_value($url, 'format', '');
		if (!empty($format)) $url_r .= '.'.$format;
		if ($this->i18n != 'default' && !array_key_exists('locale', $params))
			$params['locale'] = $this->i18n;
		if ($module == $this->module && $controller == $this->controller) {
			foreach ($this->get_variables as $variable => $function) {
				$params[$variable] = array_value($params, $variable, $this->$variable);
				if (($function === null && $params[$variable] === null) ||
					($function == 'intval' && intval($params[$variable]) == 0) ||
					($function == 'strval' && strval($params[$variable]) == '') ||
					(($function == 'floatval' || $function == 'doubleval') && floatval($params[$variable]) == 0.0) ||
					($function == 'boolval' && boolval($params[$variable]) == false))
					unset($params[$variable]);
			}
		}
		return url($url_r, $params);
	}

	function external_url($url = array(), $params = array()) {
		if (is_array($params)) {
			if (is_array($url)) {
				return 'http://'.$_SERVER['SERVER_NAME'].$this->url($url, $params);
			} else {
				if ($this->i18n != 'default' && !array_key_exists('locale', $params))
					$params['locale'] = $this->i18n;
				return 'http://'.$_SERVER['SERVER_NAME'].url($url, $params);
			}
		} else {
			return 'http://'.$_SERVER['SERVER_NAME'].$url;
		}
	}

	protected function redirect_to($url = array(), $params = array()) {
		$this->end_action_output_buffering();
		if (is_array($params)) {
			if (is_array($url)) {
				header('Location: '.$this->url($url, $params));
			} else {
				if ($this->i18n != 'default' && !array_key_exists('locale', $params))
					$params['locale'] = $this->i18n;
				header('Location: '.url($url, $params));
			}
		} else {
			header('Location: '.$url);
		}
		exit;
	}

	protected function redirect_to_url($url) {
		$this->end_action_output_buffering();
		header('Location: '.$url);
		exit;
	}

	protected function redirect_back($url = array(), $params = array()) {
		if (blank($_SERVER['HTTP_REFERER']))
			$this->redirect_to($url, $params);
		$this->end_action_output_buffering();
		header('Location: '.$_SERVER['HTTP_REFERER']);
		exit;
	}

	public function __call($name, $arguments) {
		global $map;
		$path = false; $url = true;

		if (preg_match('/^(?<path>\w+)_url$/', $name, $matches)) {
			$path = $map->path($matches['path']);
		} else if (preg_match('/^(?<path>\w+)_path$/', $name, $matches)) {
			$path = $map->path($matches['path']);
			$url = false;
		}

		if ($path === false) {
			$trace = debug_backtrace();
			trigger_error(
				'Undefined method via __call(): ' . $name .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
			return null;
		}

		$get_arguments = array_value($arguments, 1, array());
		$arguments = array_value($arguments, 0, array());
		if (!is_array($arguments))
			$arguments = array('\w+' => $arguments);
		$params = array_merge(array_merge(array(
			'module' => coalesce($map->module_for_controller($this->controller), $this->module),
			'controller' => $this->controller,
			'action' => $this->action,
			'format' => $this->render_format),
			array_value($path, 1, array())), $arguments);
		$path = $path[0];
		foreach ($params as $key => $value)
			$path = preg_replace("/\\(\\?<$key>[^\\)]*\\)/", $value, $path);
		while (preg_match('/\(\?<(?<key>\w+)>[^\)]*\)/', $path, $matches))
			$path = preg_replace("/\\(\\?<{$matches['key']}>[^\\)]*\\)/", $this->params[$matches['key']], $path);
		if ($url) {
			$path = url($path);
			if ($this->i18n != 'default')
				$get_arguments = array_merge(array('locale' => $this->i18n), $get_arguments);
			if (count($get_arguments))
				$path .= url_query_string($get_arguments);
		}
		return $path;
	}

	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->data))
			return $this->data[$name];

		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
		return null;
	}

	public function __isset($name) {
		return array_key_exists($name, $this->data);
	}

	public function __unset($name) {
		unset($this->data[$name]);
	}
}

?>
