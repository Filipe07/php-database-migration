PhpMySQLMigration - PHP-MySQL database migration tool
=================================================
Project created to run migrations on multiple environments

This is a full standalone PHP tool based on [Symfony Console](http://symfony.com/doc/current/components/console).
It's a fork from https://github.com/alwex/php-database-migration

Usage
-----
```
$ ./bin/migrate
Console Tool

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help            Displays help for a command
  list            Lists commands
 migrate
  migrate:init    Create the changelog file and file directories
  migrate:addenv  Add an environment to work with php db migrate
  migrate:reset   Reset database given a clean file
  migrate:create  Create a SQL migration
  migrate:up      Execute all waiting migration up to [to] option if precised
  migrate:down    Rollback all waiting migration down to [to] option if precised
  migrate:status  Display the current status of the specified environment
  migrate:seed    Seed database with given file.
```

Installing it in your project
-----------------------------
Just run composer command (don't forget to specify your bin directory)

```
composer require filipe07/php-database-migration
```


Initialization
--------------
Choose folder for migrations and configurations and creates a new database table for tracking the current database changes.
Warning, all migrate commands must be executed on your root folder like `bin/migrate migrate:command...`

```
$ ./bin/migrate migrate:init
```


Adding an environment
---------------------
The first thing to do before playing with MySQL migrations is to add an environment, let's add the dev one.

```
$ ./bin/migrate migrate:addenv
```

You will be prompted to answer a series of questions about your environment, and then a config file will be saved
in `.[environments]/[env].yml`.


Create a migration
------------------
It is time to create our first migration file.

```
$ ./bin/migrate migrate:create
```

Migrations file are like this:
    -- // add table users
    -- Migration SQL that makes the change goes here.
    create table users (id integer, name text);
    -- @UNDO
    -- SQL to undo the change goes here.
    drop table users;


List status of migrations
------------------
View all available migrations and their status.

```
$ ./bin/migrate migrate:status [env]
+----------------+---------+------------------+--------------------+
| id             | version | applied at       | description        |
+----------------+---------+------------------+--------------------+
| 14679010838251 |         |                  | create table users |
+----------------+---------+------------------+--------------------+
```


Up and down
-----------
You can now up all the pending migrations. If you decide to down a migration, the last one will be downed alone to
prevent mistakes. You will be asked to confirm the downgrade of your database before running the real SQL script.

```
$ ./bin/migrate migrate:up [env]
```

For development purposes, it is also possible to up a single migration without taking care of the other ones:

```
$ ./bin/migrate migrate:up [env] --only=[migrationid]
```

or migrate to specific migration (it will run all migrations, including the specified migration)

```
$ ./bin/migrate migrate:up [env] --to=[migrationid]
```

Same thing for down:

```
$ ./bin/migrate migrate:down [env] --only=[migrationid]
```
or

```
$ ./bin/migrate migrate:down [env] --to=[migrationid]
```


Seed file to database
------------------
If you need to seed database with given file

```
$ ./bin/migrate migrate:seed [env] {file_location}
```