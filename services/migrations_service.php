<?

class MigrationsService
{

	function __construct() {
		$this->initialize();
	}

	function initialize() {
		$this->available_migrations = array_reverse(array_walk_copy(array_filter($this->scan_migrations_dir(), function($file) {
			return ends_with($file, '.sql');
		}), function(&$file, $key) {
			$file = substr($file, 0, -4);
		}));
		$schema_migrations = array();
		$migration = SchemaMigration::find();
		while ($migration->next())
			$schema_migrations[] = $migration->version;
		$this->schema_migrations = $schema_migrations;
	}

	function execute() {
		$migrations = array();
		foreach ($this->available_migrations as $migration)
			if (!in_array($migration, $this->schema_migrations))
				$migrations[] = $migration;
		sort($migrations);

		foreach ($migrations as $migration) {
			$query = $this->get_migration_contents($migration);

			if ($query === false)
				throw new Exception("Could not read migration $migration.");

			if ($this->run_query($query) === false)
				throw new Exception("Failed to execute migration $migration. ".pg_last_error());

			$schema_migration = new SchemaMigration;
			$schema_migration->version = $migration;
			$schema_migration->save();
		}
	}

	function get_available_migrations() {
		return $this->available_migrations;
	}

	function get_schema_migrations() {
		return $this->schema_migrations;
	}

	function scan_migrations_dir() {
		return scandir('db/migrate');
	}

	function get_migration_contents($migration) {
		return file_get_contents("db/migrate/$migration.sql");
	}

	function run_query($query) {
		return pg_query("BEGIN; $query COMMIT;");
	}

}

?>
