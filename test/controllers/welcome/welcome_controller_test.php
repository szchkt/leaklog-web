<?php

class OrganizacieControllerTest extends ControllerTestCase
{

	public function test_zobrazit_with_correct_id() {
		$this->get('/leaklog/welcome');
		$this->assertResponseContains('Welcome');
	}

}

?>
