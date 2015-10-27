<?php

class Model {
	private $table;
	private $query = array('command' => 'INSERT');
	private $params = array();
	private $result = false;
	private $data = array();
	private $validators = [];

	protected function __construct() {
	}

	function __destruct() {
		if ($this->result !== false)
			pg_free_result($this->result);
	}

	public function set_table($table) {
		$this->table = $table;
		return $this;
	}

	public function table() {
		return $this->table;
	}

	public static function count($field = '*') {
		$model = get_called_class();
		$model = new $model;
		$model->data = false;
		$model->select("COUNT($field) AS count");
		return intval($model->value('count'));
	}

	public static function max($field, $default = 0) {
		$model = get_called_class();
		$model = new $model;
		$model->data = false;
		$model->select("COALESCE(MAX($field), $default) AS max");
		return intval($model->value('max'));
	}

	public static function find($query = '') {
		$model = get_called_class();
		$model = new $model;
		$model->data = false;
		$model->query['command'] = 'SELECT';
		if (!empty($query))
			$model->execute($query);
		return $model;
	}

	public static function find_by($fields, $arguments, $table = false) {
		$model = get_called_class();
		$model = new $model;
		if ($table !== false)
			$model->table = $table;
		$model->data = false;
		$model->query['command'] = 'SELECT';
		if (!array_key_exists('where', $model->query))
			$model->query['where'] = array();
		$i = 0;
		foreach ($fields as $field) {
			if ($arguments[$i] === null) {
				$model->query['where']["\"$field\" IS NULL"] = [];
			} else {
				$model->query['where']["\"$field\" = ?"] = $arguments[$i];
			}
			$i++;
		}
		return $model;
	}

	public static function find_or_initialize_by($fields, $arguments, $table = false) {
		$model = get_called_class();
		$model = new $model;
		if ($table !== false)
			$model->table = $table;
		$model->data = false;
		$model->query['command'] = 'SELECT';
		if (!array_key_exists('where', $model->query))
			$model->query['where'] = array();
		$i = 0;
		foreach ($fields as $field) {
			if ($arguments[$i] === null) {
				$model->query['where']["\"$field\" IS NULL"] = [];
			} else {
				$model->query['where']["\"$field\" = ?"] = $arguments[$i];
			}
			$i++;
		}
		if (!$model->next()) {
			$model->query['command'] = 'INSERT';
			$model->params = array();
			$model->result = false;
			$model->data = array();
			$i = 0;
			foreach ($fields as $field) {
				$model->set_value($field, $arguments[$i]);
				$i++;
			}
		}
		return $model;
	}

	public static function update_by($fields, $arguments, $table = false) {
		$model = get_called_class();
		$model = new $model;
		if ($table !== false)
			$model->table = $table;
		$model->query['command'] = 'UPDATE';
		if (!array_key_exists('where', $model->query))
			$model->query['where'] = array();
		$i = 0;
		foreach ($fields as $field) {
			$model->data[$field] = $arguments[$i];
			$model->query['where']["\"$field\" = ?"] = $arguments[$i];
			$i++;
		}
		return $model;
	}

	public function select($fields = '*', $set_command = true) {
		if ($set_command)
			$this->query['command'] = 'SELECT';
		$this->query['select'] = $fields;
		return $this;
	}

	public function append_select($fields = '*', $set_command = true) {
		if ($set_command)
			$this->query['command'] = 'SELECT';
		$select = array();
		if (array_key_exists('select', $this->query))
			$select[] = $this->query['select'];
		$select[] = $fields;
		$this->query['select'] = implode(', ', $select);
		return $this;
	}

	public function join($join) {
		$this->query['join'] = array_merge(array_value($this->query, 'join', array()), (array)$join);
		return $this;
	}

	public function where($conditions, $value = array()) {
		if (!array_key_exists('where', $this->query))
			$this->query['where'] = array();
		if (is_array($conditions))
			$this->query['where'] += $conditions;
		else
			$this->query['where'][$conditions] = $value;
		return $this;
	}

	public function group_by($fields) {
		$this->query['group'] = $fields;
		return $this;
	}

	public function order_by($fields) {
		$this->query['order'] = $fields;
		return $this;
	}

	public function limit($limit) {
		$this->query['limit'] = $limit;
		return $this;
	}

	public function offset($offset) {
		$this->query['offset'] = $offset;
		return $this;
	}

	private function error($error) {
		quit(message($error));
	}

	private function build_where_condition(&$params, $last_param_index) {
		$query = '';
		$where = array_value($this->query, 'where', array());
		if (count($where)) {
			$replace_callback = create_function('$matches', 'static $i = '.$last_param_index.'; $i++; return \'$\'.$i;');
			$query .= ' WHERE ';
			$i = 0;
			foreach ($where as $condition => $value) {
				if ($i) $query .= ' AND ';
				$query .= '(';
				$query .= preg_replace_callback('/\?/', $replace_callback, $condition, -1, $count);
				if (is_array($value)) {
					if (count($value) != $count) $this->error('incorrect_param_count');
					$params = array_merge($params, $value);
				} else {
					if ($count != 1) $this->error('incorrect_param_count');
					$params[] = $value;
				}
				$query .= ')';
				$i++;
			}
		}
		return $query;
	}

	private function build_query() {
		$query = '';
		$command = array_value($this->query, 'command', 'SELECT');
		$params = array();
		if ($command == 'SELECT') {
			$select = array_value($this->query, 'select', '*');
			if (is_array($select))
				$select = implode(', ', array_double_quote_values($select));
			$query = "SELECT $select FROM $this->table";
			$joins = array_value($this->query, 'join', array());
			foreach ($joins as $join)
				$query .= ' '.$join;
			$query .= $this->build_where_condition($params, 0);
			if (array_key_exists('group', $this->query))
				$query .= ' GROUP BY '.$this->query['group'];
			if (array_key_exists('order', $this->query))
				$query .= ' ORDER BY '.$this->query['order'];
			if (array_key_exists('limit', $this->query))
				$query .= ' LIMIT '.$this->query['limit'];
			if (array_key_exists('offset', $this->query))
				$query .= ' OFFSET '.$this->query['offset'];
			$query .= ';';
		} else if ($command == 'INSERT') {
			$update = array_value($this->query, 'update', array());
			if (!count($update)) $this->error('incorrect_param_count');
			$update_special = array_value($this->query, 'update_special', array());
			$update_imploded = implode(', ', array_double_quote_values(array_merge($update, array_keys($update_special))));
			$query = "INSERT INTO \"$this->table\" ($update_imploded) VALUES (";
			$i = 0;
			foreach ($update as $field) {
				if ($i) $query .= ', ';
				$i++;
				$query .= '$'.$i;
				$params[] = $this->data[$field];
			}
			foreach ($update_special as $name => $value)
				$query .= ", $value";
			$query .= ')';
			$returning = array_value($this->query, 'returning', '');
			if (!empty($returning))
				$query .= " RETURNING $returning";
			$query .= ';';
		} else if ($command == 'UPDATE') {
			$update = array_value($this->query, 'update', array());
			$update_special = array_value($this->query, 'update_special', array());
			if (!count($update) && !count($update_special)) return '';
			$query = "UPDATE \"$this->table\" SET ";
			$i = 0;
			foreach ($update as $field) {
				if ($i) $query .= ', ';
				$i++;
				$query .= '"'.$field.'" = $'.$i;
				$params[] = $this->data[$field];
			}
			$j = $i;
			foreach ($update_special as $name => $value) {
				if ($j) $query .= ', ';
				$j++;
				$query .= "\"$name\" = $value";
			}
			$query .= $this->build_where_condition($params, $i);
			$returning = array_value($this->query, 'returning', '');
			if (!empty($returning))
				$query .= " RETURNING $returning";
			$query .= ';';
		} else if ($command == 'DELETE') {
			$query = "DELETE FROM \"$this->table\"".$this->build_where_condition($params, 0).';';
		}
		$this->params = $params;
		return $query;
	}

	public function query_dump() {
		var_dump($this->build_query())."\n";
		var_dump($this->params)."\n";
	}

	public function validate_and_save($returning = '', $set_where = false) {
		if (!$this->run_validations())
			return false;

		return $this->save($returning, $set_where);
	}

	public function save($returning = '', $set_where = false) {
		if (array_value($this->query, 'command', 'SELECT') == 'SELECT') {
			if (count(array_value($this->query, 'where', array())))
				$this->query['command'] = 'UPDATE';
			else
				$this->query['command'] = 'INSERT';
		}
		if (blank($returning))
			$returning = array();
		else
			$returning = preg_split('/[\s"]*,[\s"]*/', trim($returning, " \t\n\r\0\x0B\""));
		$returning_special = array();
		if (in_array('*', $returning)) {
			$this->query['returning'] = '*';
		} else {
			$update_special = array_keys(array_value($this->query, 'update_special', array()));
			foreach ($update_special as $name)
				if (!in_array($name, $returning))
					$returning_special[] = $name;
			$this->query['returning'] = implode(', ', array_walk_copy(array_merge($returning, $returning_special), function(&$value, $key) {
				if (!preg_match('/[\s\(\)]/', $value))
					$value = "\"$value\"";
			}));
		}
		$result = $this->execute();
		$this->query['returning'] = '';
		if ($result && (count($returning) || count($returning_special))) {
			$returned = pg_fetch_assoc($this->result);
			if (is_array($returned)) {
				$this->data = array_merge($this->data ?: [], $returned);
				if ($set_where)
					foreach ($returned as $field => $value)
						if (in_array($field, $returning))
							$this->where("\"$field\" = ?", $value);
			}
		}
		if (count(array_value($this->query, 'where', array())))
			$this->query['command'] = 'SELECT';
		return $result;
	}

	public function delete() {
		$command = array_value($this->query, 'command', 'SELECT');
		$this->query['command'] = 'DELETE';
		$result = $this->execute();
		$this->query['command'] = $command;
		return $result;
	}

	public static function execute_query($query) {
		if (DEBUG) {
			$result = pg_query($query) or quit(message('query_failed').pg_last_error());
		} else {
			$result = @pg_query($query) or quit(message('query_failed').pg_last_error());
		}
		return $result;
	}

	public static function execute_query_params($query, $params = array()) {
		if (DEBUG) {
			$result = pg_query_params($query, $params) or quit(message('query_failed').pg_last_error());
		} else {
			$result = @pg_query_params($query, $params) or quit(message('query_failed').pg_last_error());
		}
		return $result;
	}

	public function execute($query = '') {
		if (empty($query)) {
			$query = $this->build_query();
			if (!empty($query))
				$this->result = self::execute_query_params($query, $this->params);
			else
				return true;
		} else {
			$this->result = self::execute_query($query);
		}
		$this->query['last_command'] = strtoupper(array_value(preg_split('/\s/', $query, 2), 0, 'SELECT'));
		return $this->result !== false;
	}

	public function seek($i) {
		pg_result_seek($this->result, $i);
	}

	public function next() {
		if (array_value($this->query, 'command', 'SELECT') != 'SELECT') return false;
		if ($this->result === false) $this->execute();
		return ($this->data = pg_fetch_assoc($this->result)) ? true : false;
	}

	public function exists() {
		return $this->data !== false || (($this->result !== false || $this->execute()) && pg_num_rows($this->result));
	}

	public function value($name, $default_value = null) {
		if ($this->data === false && !$this->next()) return $default_value;
		return $this->data[$name];
	}

	public function set_value($name, $value) {
		if ($this->data === false && ($this->query['command'] != 'SELECT' || !$this->next()))
			$this->data = array();
		if ($this->query['command'] != 'INSERT')
			$this->query['command'] = 'UPDATE';
		if (!array_key_exists('update', $this->query))
			$this->query['update'] = array();

		if ($value !== null) {
			$value = (string)$value;

			if (array_key_exists('update_special', $this->query) &&
				array_key_exists($name, $this->query['update_special'])) {
				unset($this->query['update_special'][$name]);
			}
		} else {
			$this->set_special_value($name, 'NULL');
		}

		if (array_key_exists($name, $this->data) && $this->data[$name] === $value)
			return;

		if ($value !== null && !in_array($name, $this->query['update']))
			$this->query['update'][] = $name;

		$this->data[$name] = $value;
	}

	public function set_special_value($name, $value) {
		if (array_key_exists('update', $this->query) &&
			($key = array_search($name, $this->query['update'])) !== false)
			unset($this->query['update'][$key]);
		if (!array_key_exists('update_special', $this->query))
			$this->query['update_special'] = array();
		$this->query['update_special'][$name] = $value;
	}

	public function current_row() {
		if (!$this->exists()) return array();
		return $this->data;
	}

	public function num_rows() {
		if ($this->result === false && !$this->execute())
			return 0;
		return pg_num_rows($this->result);
	}

	public function affected_rows() {
		if ($this->result === false && !$this->execute())
			return 0;
		return pg_affected_rows($this->result);
	}

	public function count_rows($field) {
		$this->select("COUNT($field) AS count");
		return intval($this->value('count'));
	}

	public static function __callStatic($name, $arguments) {
		$model = get_called_class();
		if (preg_match('/^find_by_(?<fields>\w+)$/', $name, $matches))
			return $model::find_by(explode('_and_', $matches['fields']), $arguments);
		if (preg_match('/^find_or_initialize_by_(?<fields>\w+)$/', $name, $matches))
			return $model::find_or_initialize_by(explode('_and_', $matches['fields']), $arguments);
		if (preg_match('/^update_by_(?<fields>\w+)$/', $name, $matches))
			return $model::update_by(explode('_and_', $matches['fields']), $arguments);

		$trace = debug_backtrace();
		trigger_error(
			'Undefined method via __callStatic(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
		return null;
	}

	public function __set($name, $value) {
		if (method_exists($this, "set_$name")) {
			$method = new ReflectionMethod($this, "set_$name");
			if ($method->isProtected()) {
				$name = "set_$name";
				$this->$name($value);
				return;
			}
		}
		$this->set_value($name, $value);
	}

	public function __get($name) {
		if ($this->data === false) $this->next();
		if (method_exists($this, $name)) {
			$method = new ReflectionMethod($this, $name);
			if ($method->isProtected())
				return $this->$name();
		}
		if (is_array($this->data) && array_key_exists($name, $this->data))
			return $this->data[$name];

		if ($this->query['last_command'] == 'SELECT') {
			$trace = debug_backtrace();
			trigger_error(
				'Undefined property via __get(): ' . $name .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
		}
		return null;
	}

	public function __isset($name) {
		if (method_exists($this, $name)) {
			$method = new ReflectionMethod($this, $name);
			if ($method->isProtected())
				return true;
		}
		return isset($this->data[$name]);
	}

	public function __unset($name) {
		unset($this->data[$name]);
		if (array_key_exists('update', $this->query))
			array_remove_value($this->query['update'], $name);
	}

	public function validates($attribute, $options, $readable_attribute = null) {
		$this->validators[] = new ValidateService($attribute, $options, $readable_attribute);
	}

	public function run_validations() {
		$pass = true;
		foreach ($this->validators as $validator) {
			if (!$validator->validate($this))
				$pass = false;
		}
		return $pass;
	}

	public static function identifier_column() {
		return 'id';
	}

	public function has_property($attribute) {
		if ($this->has_attribute($attribute))
			return true;
		return method_exists($this, $attribute);
	}

	public function has_attribute($attribute) {
		return is_array($this->data) && array_key_exists($attribute, $this->data);
	}

	public function update_attributes($attributes, $params) {
		foreach ($attributes as $attribute) {
			if (array_key_exists($attribute, $params)) {
				$this->set_value($attribute, $params[$attribute]);
			}
		}
	}

	protected function identifier() {
		$column = 'id';
		$class = get_called_class();
		if (method_exists($class, 'identifier_column'))
			$column = $class::identifier_column();
		return $this->$column;
	}
}

?>
