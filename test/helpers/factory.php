<?php

class Factory {

	static protected $definitions = [];

	public static function define($name, $model_name, $values) {
		self::$definitions[$name] = [
			'model_name' => $model_name,
			'values' => $values
		];
	}

	public static function get($name, $custom_values = []) {
		if (!array_key_exists($name, self::$definitions))
			return null;

		$model_name = self::underscore_to_camel_case(self::$definitions[$name]['model_name']);

		$values = self::$definitions[$name]['values'];
		$values = array_replace($values, $custom_values);

		$instance = new $model_name();
		foreach ($values as $key => $value) {
			if (method_exists($value, 'get_value'))
				$instance->$key = $value->get_value();
			else
				$instance->$key = $value;
		}
		return $instance;
	}

	public static function create($name, $custom_values = []) {
		$instance = self::get($name, $custom_values);
		$instance->save('*');
		return $instance;
	}

	public static function incremented() {
		return new FactoryIncrementer;
	}

	public static function faked($key) {
		return new FactoryFaker($key);
	}

	public static function referenced($name, $field = 'id', $custom_values = []) {
		return new FactoryReferencedCreator($name, $field, $custom_values);
	}

	// PRIVATE

	private function underscore_to_camel_case($string) {
		$string[0] = strtoupper($string[0]);
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $string);
	}

}

class FactoryFaker {

	public function __construct($key) {
		$this->key = $key;
	}

	public function get_value() {
		return call_user_func([Faker\Factory::create(), $this->key]);
	}

}

class FactoryIncrementer {

	public function __construct() {
		$this->i = 1;
	}

	public function get_value() {
		return $this->i++;
	}

}

class FactoryReferencedCreator {

	public function __construct($name, $field = 'id', $custom_values = []) {
		$this->name = $name;
		$this->field = $field;
		$this->custom_values = $custom_values;
	}

	public function get_value() {
		$field = $this->field;
		return Factory::create($this->name, $this->custom_values)->$field;
	}

}


?>
