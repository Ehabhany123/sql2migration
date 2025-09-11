# SQL to Migration for CodeIgniter 4 - Documentation

This package provides a Spark command to convert SQL dump files (e.g., from phpMyAdmin) into CodeIgniter 4 migration files. It supports table creation (`CREATE TABLE`), foreign keys (`FOREIGN KEY`), triggers, and database prefixes (`database.default.DBPrefix`).

## Features
- **Table Creation**: Generates migration files from `CREATE TABLE` statements.
- **Foreign Keys**: Converts `ALTER TABLE` foreign key definitions into separate migration files, supporting `SET NULL`, `NO ACTION`, `CASCADE`, `RESTRICT`, and `SET DEFAULT`.
- **Triggers**: Creates migration files for `CREATE TRIGGER` statements.
- **Prefix Support**: Automatically strips prefixes (e.g., `ci4ms_`) from table names based on `database.default.DBPrefix` in the `.env` file.
- **Debugging**: Provides detailed CLI logs for troubleshooting.

## Installation

### 1. Install via Composer
Add the package to your CodeIgniter 4 project:
```bash
composer require bertugfahriozer/sql-to-migration
```

### 3. Verify Command Availability
Check if the Spark command is registered:
```bash
php spark list
```
You should see `sql2migration` in the list.

## Usage
To convert an SQL file into CodeIgniter 4 migration files:
```bash
php spark sql2migration /path/to/your/database.sql
```

- **Input**: Path to the SQL file (e.g., `/path/to/database.sql`).
- **Output**: Migration files are generated in `app/Database/Migrations/`:
  - Table migrations (e.g., `20250907223600_CreateUsersTable.php`)
  - Foreign key migration (e.g., `20250907223602_AddForeignKeys.php`)
  - Trigger migrations (e.g., `20250907223603_CreateUpdateTimestampTrigger.php`)

Apply the migrations:
```bash
php spark migrate
```

## Supported SQL Structures
- **Table Definitions**:
  - Supports `CREATE TABLE` statements with column types (`INT`, `VARCHAR`, etc.), `UNSIGNED`, `NOT NULL`, `AUTO_INCREMENT`, and `PRIMARY KEY`.
  - Example:
    ```sql
    CREATE TABLE `ci4ms_users` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;
    ```

- **Foreign Keys**:
  - Supports `ALTER TABLE ... ADD CONSTRAINT ... FOREIGN KEY` statements.
  - Handles `ON DELETE` and `ON UPDATE` with: `CASCADE`, `SET NULL`, `NO ACTION`, `RESTRICT`, `SET DEFAULT`.
  - Example:
    ```sql
    ALTER TABLE `ci4ms_orders`
      ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `ci4ms_users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;
    ```

- **Triggers**:
  - Supports `CREATE TRIGGER` statements (`BEFORE`/`AFTER`, `INSERT`/`UPDATE`/`DELETE`).
  - Example:
    ```sql
    CREATE TRIGGER `ci4ms_update_timestamp` BEFORE UPDATE ON `ci4ms_users` FOR EACH ROW SET NEW.updated_at = NOW();
    ```

- **Prefix Handling**:
  - Strips prefixes (e.g., `ci4ms_`) from table names based on `database.default.DBPrefix` in `.env`.
  - Example `.env`:
    ```env
    database.default.DBPrefix = ci4ms_
    ```

## Example SQL File
The following SQL file demonstrates the package’s capabilities:
```sql
CREATE TABLE `ci4ms_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `ci4ms_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

ALTER TABLE `ci4ms_orders`
  ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `ci4ms_users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

CREATE TRIGGER `ci4ms_update_timestamp` BEFORE UPDATE ON `ci4ms_users` FOR EACH ROW SET NEW.updated_at = NOW();
```

### Generated Migration Files
Running `php spark sql2migration database.sql` produces the following files:

- **Table Migration** (`20250907223600_CreateUsersTable.php`):
  ```php
  <?php
  namespace App\Database\Migrations;
  use CodeIgniter\Database\Migration;

  class CreateUsersTable extends Migration
  {
      public function up()
      {
          $this->forge->addField([
              'id' => [
                  'type' => 'INT',
                  'constraint' => '11',
                  'unsigned' => true,
                  'auto_increment' => true,
              ],
              'username' => [
                  'type' => 'VARCHAR',
                  'constraint' => '255',
              ],
          ]);
          $this->forge->addKey('id', true);
          $this->forge->createTable('users');
      }

      public function down()
      {
          $this->forge->dropTable('users');
      }
  }
  ```

- **Foreign Key Migration** (`20250907223602_AddForeignKeys.php`):
  ```php
  <?php
  namespace App\Database\Migrations;
  use CodeIgniter\Database\Migration;

  class AddForeignKeys extends Migration
  {
      public function up()
      {
          $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'NO ACTION');
      }

      public function down()
      {
          $this->forge->dropForeignKey('orders', 'fk_orders_users');
      }
  }
  ```

- **Trigger Migration** (`20250907223603_CreateUpdateTimestampTrigger.php`):
  ```php
  <?php
  namespace App\Database\Migrations;
  use CodeIgniter\Database\Migration;

  class CreateUpdateTimestampTrigger extends Migration
  {
      public function up()
      {
          $this->db->query(
              'CREATE TRIGGER `ci4ms_update_timestamp` BEFORE UPDATE ON `users` FOR EACH ROW SET NEW.updated_at = NOW();'
          );
      }

      public function down()
      {
          $this->db->query('DROP TRIGGER IF EXISTS `ci4ms_update_timestamp`;');
      }
  }
  ```

## Troubleshooting
- **"Command Not Found" Error**:
  - Ensure the namespace is correctly added in `app/Config/Autoload.php`.
  - Run `composer dump-autoload`.
- **Foreign Keys Not Parsed**:
  - Verify the `ALTER TABLE` format in your SQL file. The package supports:
    ```sql
    ALTER TABLE `table_name` ADD CONSTRAINT `constraint_name` FOREIGN KEY (`field`) REFERENCES `ref_table` (`ref_field`) ON DELETE SET NULL ON UPDATE NO ACTION;
    ALTER TABLE `table_name` ADD FOREIGN KEY (`field`) REFERENCES `ref_table` (`ref_field`);
    ```
  - Check CLI output for debugging:
    ```
    php spark sql2migration /path/to/database.sql
    ```
    Look for `Debug: Found ALTER TABLE statements` and `Debug: Found foreign keys` in the output.
- **Table Prefix Issues**:
  - Ensure `database.default.DBPrefix` is correctly set in `.env`. Example:
    ```env
    database.default.DBPrefix = ci4ms_
    ```
- **Migration Errors**:
  - If migrations fail, roll back with `php spark migrate:rollback` and verify the SQL file.

## Support and Contribution
- Report issues on GitHub: [github.com/bertugfahriozer/sql2migration](https://github.com/bertugfahriozer/sql2migration)
- Contribute via Pull Requests.
- The package is licensed under the MIT License.

## License
MIT License. See the `LICENSE` file for details.
