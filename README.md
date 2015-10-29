Leaklog Web
===========

# Installation

```
CREATE DATABASE szchkt;
CREATE DATABASE szchkt_test;
```

In each database execute:

```
CREATE TABLE schema_migrations
(
  version text NOT NULL,
  CONSTRAINT schema_migrations_version PRIMARY KEY (version)
)
WITH (
  OIDS=FALSE
);
```

# To run the tests

1. create the test database (instructions above)
2. create a `test/database_config.php` file (sample available in the same folder)
3. `php test/migrate.php`
4. `php test/load_fixtures.php`
5. `phpunit`
