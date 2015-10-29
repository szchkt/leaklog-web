<?

class ValidateService {

	private $attribute = null;
	private $options = null;

	public function __construct($attribute, $options, $readable_attribute = null) {
		$this->attribute = $attribute;
		$this->options = $options;
		$this->readable_attribute = $readable_attribute;
	}

	public function validate($instance) {
		foreach ($this->options as $key => $value) {
			$method_name = "validate_{$key}";
			$error = $this->$method_name($instance, $value);
			if (!is_null($error)) {
				if (empty($error))
					$error = tr("%1 is not valid!", $this->readable_attribute());
				LoggerService::error($error);
				return false;
			}
		}

		return true;
	}

	private function validate_presence($instance, $check) {
		if ($check && blank($this->value($instance)))
			return tr("%1 is a required field!", $this->readable_attribute());
		return null;
	}

	private function validate_email($instance, $check) {
		if (is_null($this->value($instance)) || !$check ||
			filter_var($this->value($instance), FILTER_VALIDATE_EMAIL))
			return null;
		return tr("The e-mail you entered is not a valid address!");
	}

	private function validate_postal_code($instance, $check) {
		if (is_null($this->value($instance)) || !$check ||
			filter_var($this->value($instance), FILTER_VALIDATE_REGEXP, [
				'options' => [
					'regexp' => "/^[0-9 -]+$/"
				]
			]))
			return null;
		return '';
	}

	private function validate_unique($instance, $check) {
		if (is_null($this->value($instance)) || !$check)
			return null;

		$classname = get_class($instance);
		$other = $classname::find()
			->where($this->attribute.' = ?', $this->value($instance));
		if ($instance->identifier)
			$other->where($classname::identifier_column().' <> ?', $instance->identifier);
		if ($other->exists())
			return tr("The %1 is already taken!", $this->readable_attribute());

		return null;
	}

	private function validate_lambda($instance, $lambda) {
		return $lambda($instance);
	}

	private function value($instance) {
		$attribute = $this->attribute;
		return $instance->$attribute;
	}

	private function readable_attribute() {
		if ($this->readable_attribute !== null)
			return $this->readable_attribute;

		$string = $this->attribute;
		$string[0] = strtoupper($string[0]);
		$func = create_function('$c', 'return " ".strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $string);
	}


}

?>
