## Other languages
- [German Readme](https://github.com/Yeora/DbSyncer/blob/main/README_DE.md)

## Brief description

DbSyncer is a tool that is supposed to facilitate the synchronization between databases.
As middleware DbSyncer uses the package ifsnop/mysqldump-php.

DbSyncer offers the possibility to perform a 1-to-1 synchronization with database schema and database
as well as a synchronization which is configurable on table and column level.

With DbSyncer, for example, the production database can be transferred to a local development database, without the need
for further
manual adjustments in the data are necessary. For example:

- Sensitive customer data can be masked or otherwise manipulated in advance.
- Overwrite or replace configurations with fixed values so that they fit the target environment.
- Set limits at table level so that only a limited amount of data is selected.
- Set conditions on table level so that only certain data is selected.
- ...


## Requirements

- PHP >= 7.4.0
- MySQL >= 4.1.0
- [PDO](https://secure.php.net/pdo)

## Installation & Setup (Quickstart)

The installation of DbSyncer is done via Composer using:

```console
composer require yeora/db-syncer --dev
```

Now a configuration file must be created from which, among other things, the access data is taken.
For a simple standard configuration file the following command can be used:

```console
vendor/bin/dbSyncer init
```

DbSyncer then creates a DbSyncer.yaml in the project root directory.
configuration file which looks like the following:

```yaml
---
syncFroms:
  - credentials:
      hostname: YOUR_DB_HOSTNAME
      port: YOUR_DB_PORT
      username: YOUR_DB_USERNAME
      password: YOUR_DB_PASSWORD
      database: YOUR_DB_DATABASENAME
syncTos:
  - credentials:
      hostname: YOUR_DB_HOSTNAME
      port: YOUR_DB_PORT
      username: YOUR_DB_USERNAME
      password: YOUR_DB_PASSWORD
      database: YOUR_DB_DATABASENAME
```

In this simple case only a syncFrom and a syncTo are preconfigured.
A syncFrom contains all necessary information of a database FROM which synchronization is to be performed.
A syncTo contains all necessary information of a database TO which synchronization is to be performed.
Any number of syncFroms and syncTos can be configured.

The next step is to enter the access data. The key values password and username
can also be removed completely from the configuration file. The credentials are then interactively when starting the
synchronizer.

After the access data have been entered or the key values have been removed, the actual synchronization process can be
started. the actual synchronization process can be started.
To do this, the following command must be executed:

```console
vendor/bin/dbSyncer sync
```

If only one syncFrom and one syncTo are configured and the access data have been specified,
the synchronization takes place automatically without further user action.

If multiple syncFroms are configured, you will be asked to select one interactively.
If more than one syncTos are configured, you will be asked to select one or more interactively.

This completes the basic setup of a complete synchronization of a database A to a database B is completed.

## Complete configuration with comments

The following is a configuration with all configuration options is given.

```yaml
---
generalConfig: # General config - Can be overridden by the configuration under syncFroms.config
  compress: NONE          # Compressmethod for the SQL dump (NONE,GZIP,BZIP2,GZIPSTREAM)
  no-data: false          # false = data | true = no data
  add-drop-table: true    # Should a "ADD DROP TABLE" statement be executed? true = yes | false = no
  single-transaction: true
  lock-tables: true
  add-locks: true
  extended-insert: true
  disable-foreign-keys-check: true
  skip-triggers: false
  add-drop-trigger: true    # Should a "ADD DROP TRIGGER" statement be executed? true = yes | false = no
  databases: false          # Should a "CREATE DATABASE" statement be executed? true = yes | false = no
  add-drop-database: true   # Should a "DROP DATABASE" statement be executed? true = yes | false = no
  hex-blob: true            # Dump binary strings (BINARY, VARBINARY, BLOB) in hexadecimal format?  true = yes | false = no

syncFroms: # One or as many as desired syncFrom hosts
  - credentials:
      hostname: DATABASE_HOSTNAME
      port: YOUR_DATABASE_PORT
      username: YOUR_DATABASE_USER  # Optional field. But must be entered interactively if omitted.
      password: YOUR_DATABASE_PASSWORD # Optional field. But must be entered interactively if omitted.
      database: YOUR_DATABASE_NAME

    config: # SyncFrom specific config
      no-data: false # E. g. overwrites no-data config from generalConfig

    conditions: # Allows to create rules on table level which data will be selected
      city: Population > 500000 and Name like 'A%' # For the city table, only entries are selected that have a population above 500000 and where the name starts with A.

    limits: # Sets table limits.
      city: 10  # Only 10 entries are selected for the city table
      country: 20 # Only 10 entries are selected for the country table

    tables: # The Tables Key is used to define operations on the table level.
      user: # Table user
        columns:
          email: # Column email
            replace: # The Replace operation replaces the values that correspond to oldValue with the value that is in value.
              # oldValue can also be a regular expression. But then this must be written as /REGULAR EXPRESSION/.
              - oldValue: gmail
                value: gmx
          password: # Column password
            overwrite: # The overwrite operation overwrites all entries of the column with value
              - value: 'DEFAULT' # All passwords are set to DEFAULT
          firstname: # Column firstname
            prefix: # The prefix operation appends to the beginning of the original value the value in "value".
              - value: MYPREFIX
          surname: # The suffix operation appends to the original value the value in "value".
            suffix:
              - value: MYSUFFIX
syncTos: # One or as many as desired syncTo hosts
  - credentials:
      hostname: DATABASE_HOSTNAME
      port: YOUR_DATABASE_PORT
      username: YOUR_DATABASE_USER
      password: YOUR_DATABASE_PASSWORD
      database: YOUR_DATABASE_NAME
```