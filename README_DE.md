## Andere Sprachen
- [English Readme](https://github.com/Yeora/DbSyncer/blob/main/README.md)

## Kurzbeschreibung

DbSyncer ist ein Tool, dass die Synchronisation zwischen Datenbanken erleichtern soll.
Als Middleware greift DbSyncer auf das Package ifsnop/mysqldump-php zurück.

DbSyncer bietet sowohl die Möglichkeit an, eine 1zu1 Sychronisation mit Datenbankschema und Datenbestand
vorzunehmen, als auch eine Sychronisation die auf Tabellen und Spaltenebene konfigurierbar ist.

Mit DbSyncer lässt sich z. B. die Produktivdatenbank in eine lokale Entwicklungsdatenbank überführen, ohne das weitere
händische Anpassungen in den Daten nötig sind. So können z. B.:

- Sensible Kundendaten im Vorfeld maskiert oder anders manipuliert werden.
- Konfigurationen mit fest hinterlegten Werten überschrieben oder ersetzt werden, sodass diese auf die Zielumgebung
  passen.
- Auf Tabellenebene Limits gesetzt werden, sodass nur eine begrenze Anzahl an Daten selektiert werden.
- Auf Tabellenebene Bedingungen setzen, sodass nur bestimmte Daten selektiert werden.
- ...

## Anforderungen

- PHP >= 7.4.0
- MySQL >= 4.1.0
- [PDO](https://secure.php.net/pdo)

## Installation & Einrichtung (Quickstart)

Die Installation von DbSyncer erfolgt via Composer mittels:

```console
composer require yeora/db-syncer --dev
```

Nun muss eine Konfigurationsdatei angelegt werden aus der u. a. die Zugangsdaten entnommen werden.
Für eine einfache Standardkonfigurationsdatei kann folgender Befehl verwendet werden:

```console
vendor/bin/dbSyncer init
```

Daraufhin erzeugt DbSyncer im Projekthauptverzeichnis eine DbSyncer.yaml
Konfigurationsdatei, die wie folgt aussieht:

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

In diesem einfachen Fall sind lediglich ein syncFrom und ein syncTo vorkonfiguriert.
Ein syncFrom enthält alle notwendigen Informationen einer Datenbank VON der synchronisiert werden soll.
Ein syncTo enthält alle notwendigen Informationen einer Datenbank ZU der synchronisiert werden soll.
Es lassen sich beliebig viele syncFroms und syncTos konfigurieren.

Als nächster Schritt müssen die Zugangsdaten eingetragen werden. Die Schlüsselwerte password und username
können auch gänzlich aus der Konfigurationsdatei entfernt werden. Die Zugangsdaten werden dann interaktiv
beim Starten des Synchronisierers abgefragt.

Nachdem die Zugangsdaten eingetragen wurden, oder die Schlüsselwerte entfernt wurden, kann
der eigentliche Synchronisierungsprozess gestartet werden.
Dazu muss folgender Befehl ausgeführt werden:

```console
vendor/bin/dbSyncer sync
```

Sofern nur ein syncFrom und ein syncTo konfiguriert sind und die Zugangsdaten mit angegeben worden sind,
findet die Synchronisierung automatisch ohne weitere Benutzeraktion statt.

Sollten mehrere syncFroms konfiguriert sein, so wird man interaktiv aufgefordert einen auszuwählen.
Sollten mehrere syncTos konfiguriert sein, so wird man interaktiv aufgefordert einen oder mehrere auszuwählen.

Damit ist die Grundeinrichtung einer vollständigen Synchronisation einer Datenbank A zu einer Datenbank B
abgeschlossen.

## Vollständige Konfiguration mit Kommentaren

Im Folgenden ist eine Konfiguration mit allen Konfigurationsmöglichkeiten
angegeben.

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
      username: YOUR_DATABASE_USER  # Optional field. But must be entered interactively if omitted.
      password: YOUR_DATABASE_PASSWORD  # Optional field. But must be entered interactively if omitted.
      database: YOUR_DATABASE_NAME
```