<?php

class DBTestCase extends PHPUnit_Framework_TestCase
{

	function setUp() {
		pg_query('BEGIN');
	}

	function tearDown() {
		pg_query('ROLLBACK');
	}

}

?>
