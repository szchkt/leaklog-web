<?php

class SchemaMigration extends Model {

	function __construct() {
		parent::__construct();
		$this->set_table('schema_migrations');
	}

}

?>
