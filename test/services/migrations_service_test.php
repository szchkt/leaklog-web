<?php

class MigrationsServiceTest extends DBTestCase {

	public function setUp() {
		parent::setUp();

		Factory::create('migration', [
			'version' => '20140319-old_migration'
		]);

		$this->service = $this->getMockBuilder('MigrationsService')
			->setMethods([
				'scan_migrations_dir',
				'run_query',
				'get_migration_contents'
			])
			->disableOriginalConstructor()
			->getMock();
		$this->service->expects($this->once())
			->method('scan_migrations_dir')
			->will($this->returnValue([
				'20140319-old_migration.sql',
				'20140319-new_migration.sql'
			]));
		$this->service->initialize();
	}

	public function test_available_migrations() {
		$response = $this->service->get_available_migrations();
		sort($response);
		$this->assertEquals([
				'20140319-new_migration',
				'20140319-old_migration'
			], $response);
	}

	public function test_execute() {
		$this->service->expects($this->once())
			->method('get_migration_contents')
			->will($this->returnValue('the query'));

		$this->service->expects($this->once())
			->method('run_query')
			->with($this->equalTo('the query'))
			->will($this->returnValue(true));

		$this->service->execute();

		$this->assertEquals(true,
			SchemaMigration::find_by_version('20140319-new_migration')->exists());
	}

}

?>
