<?php

namespace sql2migration\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Exception;

class SqlToMigration extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'sql2migration';
    protected $description = 'It generates CodeIgniter 4 migration files from an SQL file.';
    protected $migrationPath = APPPATH . 'Database/Migrations/';
    protected $timestamp;
    protected $dbPrefix;

    public function run(array $params)
    {
        // Get the SQL file path from the argument
        $sqlFilePath = $params[0] ?? CLI::prompt('Enter the path of the SQL file');
        if (!file_exists($sqlFilePath)) {
            CLI::error("Hata: SQL file not found: $sqlFilePath");
            return;
        }

        // .env dosyasından database.default.DBPrefix değerini al
        $this->dbPrefix = env('database.default.DBPrefix', '');
        if ($this->dbPrefix) {
            CLI::write("Database prefix: {$this->dbPrefix}", 'yellow');
        } else {
            CLI::write("The database prefix is not defined, the table names will be used as they are.", 'yellow');
        }

        $this->timestamp = date('Y-m-d-His');

        try {
            $this->convert($sqlFilePath);
            CLI::write('Migration files created.', 'green');
        } catch (Exception $e) {
            CLI::error('Hata: ' . $e->getMessage());
        }
    }

    protected function stripPrefix($tableName)
    {
        // If there is a prefix, remove it from the table name
        if ($this->dbPrefix && strpos($tableName, $this->dbPrefix) === 0) {
            return substr($tableName, strlen($this->dbPrefix));
        }
        return $tableName;
    }

    protected function convert($sqlFilePath)
    {
        // Read the SQL file
        $sqlContent = file_get_contents($sqlFilePath);
        if ($sqlContent === false) {
            throw new Exception('The SQL file could not be read.');
        }

        // Regular expression for CREATE TABLE statements
        $tablePattern = '/CREATE TABLE `?(\w+)`?\s*\((.*?)\)\s*(?:ENGINE|;)/is';
        preg_match_all($tablePattern, $sqlContent, $tableMatches, PREG_SET_ORDER);

        if (empty($tableMatches)) {
            CLI::write('Warning: No valid CREATE TABLE statement was found in the SQL file.', 'yellow');
        }

        // Generate the table migrations
        foreach ($tableMatches as $match) {
            $rawTableName = $match[1]; // Ham tablo adı (örneğin: ci4ms_users)
            $tableName = $this->stripPrefix($rawTableName); // Öneksiz ad (örneğin: users)
            $tableDefinition = $match[2]; // Tablo içeriği
            $this->generateMigrationFile($tableName, $tableDefinition);
        }

        // Parse the foreign keys added with ALTER TABLE.
        $foreignKeys = $this->parseAlterTableForeignKeys($sqlContent);
        if (!empty($foreignKeys)) {
            $this->generateForeignKeyMigrationFile($foreignKeys);
        } else {
            CLI::write('Warning: No foreign key (FOREIGN KEY) was found in the SQL file.', 'yellow');
        }

        // Separate parsing for CREATE TRIGGER statements
        $triggers = $this->parseTriggers($sqlContent);
        foreach ($triggers as $trigger) {
            $this->generateTriggerMigrationFile($trigger['name'], $trigger['sql'], $trigger['table']);
        }
    }

    protected function generateMigrationFile($tableName, $tableDefinition)
    {
        // Parse the columns and the primary key
        $fields = $this->parseFields($tableDefinition);
        $primaryKey = $this->parsePrimaryKey($tableDefinition);

        // Migration file content
        $className = 'Create' . ucfirst($tableName) . 'Table';
        $fileContent = "<?php\n\n";
        $fileContent .= "namespace App\\Database\\Migrations;\n\n";
        $fileContent .= "use CodeIgniter\\Database\\Migration;\n\n";
        $fileContent .= "class $className extends Migration\n";
        $fileContent .= "{\n";
        $fileContent .= "    public function up()\n";
        $fileContent .= "    {\n";
        $fileContent .= "        \$this->forge->addField([\n";

        // Add the columns
        foreach ($fields as $field) {
            $fieldDef = "            '{$field['name']}' => [\n";
            $fieldDef .= "                'type' => '{$field['type']}',\n";
            if (isset($field['constraint'])) {
                $fieldDef .= "                'constraint' => '{$field['constraint']}',\n";
            }
            if (isset($field['unsigned']) && $field['unsigned']) {
                $fieldDef .= "                'unsigned' => true,\n";
            }
            if (isset($field['auto_increment']) && $field['auto_increment']) {
                $fieldDef .= "                'auto_increment' => true,\n";
            }
            if (isset($field['null']) && $field['null']) {
                $fieldDef .= "                'null' => true,\n";
            }
            $fieldDef .= "            ],\n";
            $fileContent .= $fieldDef;
        }

        $fileContent .= "        ]);\n";

        // Add the primary key
        if ($primaryKey) {
            $fileContent .= "        \$this->forge->addKey('{$primaryKey}', true);\n";
        }

        $fileContent .= "        \$this->forge->createTable('$tableName');\n";
        $fileContent .= "    }\n\n";
        $fileContent .= "    public function down()\n";
        $fileContent .= "    {\n";
        $fileContent .= "        \$this->forge->dropTable('$tableName');\n";
        $fileContent .= "    }\n";
        $fileContent .= "}\n";

        // Save the file
        $fileName = $this->migrationPath . $this->timestamp . '_' . $className . '.php';
        file_put_contents($fileName, $fileContent);
        CLI::write("The generated file: $fileName", 'yellow');

        // Increase the timestamp for each table
        $this->timestamp++;
    }

    protected function generateForeignKeyMigrationFile($foreignKeys)
    {
        // Migration file content
        $className = 'AddForeignKeys';
        $fileContent = "<?php\n\n";
        $fileContent .= "namespace App\\Database\\Migrations;\n\n";
        $fileContent .= "use CodeIgniter\\Database\\Migration;\n\n";
        $fileContent .= "class $className extends Migration\n";
        $fileContent .= "{\n";
        $fileContent .= "    public function up()\n";
        $fileContent .= "    {\n";

        // Add the foreign keys
        foreach ($foreignKeys as $fk) {
            $fileContent .= "        \$this->forge->addForeignKey('{$fk['field']}', '{$this->stripPrefix($fk['referenced_table'])}', '{$fk['referenced_field']}', '{$fk['on_delete']}', '{$fk['on_update']}');\n";
        }

        $fileContent .= "    }\n\n";
        $fileContent .= "    public function down()\n";
        $fileContent .= "    {\n";
        foreach ($foreignKeys as $fk) {
            $fileContent .= "        \$this->forge->dropForeignKey('{$this->stripPrefix($fk['table'])}', '{$fk['constraint']}');\n";
        }
        $fileContent .= "    }\n";
        $fileContent .= "}\n";

        // Save the file
        $fileName = $this->migrationPath . $this->timestamp . '_' . $className . '.php';
        file_put_contents($fileName, $fileContent);
        CLI::write("The generated foreign key migration file: $fileName", 'yellow');

        // Increase the timestamp
        $this->timestamp++;
    }

    protected function generateTriggerMigrationFile($triggerName, $triggerSql, $tableName)
    {
        // Remove the prefix from the table name in the trigger SQL
        $cleanTableName = $this->stripPrefix($tableName);
        $triggerSql = str_replace("`$tableName`", "`$cleanTableName`", $triggerSql);

        // Migration file content
        $className = 'Create' . ucfirst($triggerName) . 'Trigger';
        $fileContent = "<?php\n\n";
        $fileContent .= "namespace App\\Database\\Migrations;\n\n";
        $fileContent .= "use CodeIgniter\\Database\\Migration;\n\n";
        $fileContent .= "class $className extends Migration\n";
        $fileContent .= "{\n";
        $fileContent .= "    public function up()\n";
        $fileContent .= "    {\n";
        $fileContent .= "        \$this->db->query(\n";
        $fileContent .= "            '" . addslashes($triggerSql) . "'\n";
        $fileContent .= "        );\n";
        $fileContent .= "    }\n\n";
        $fileContent .= "    public function down()\n";
        $fileContent .= "    {\n";
        $fileContent .= "        \$this->db->query('DROP TRIGGER IF EXISTS `$triggerName`;');\n";
        $fileContent .= "    }\n";
        $fileContent .= "}\n";

        // Save the file
        $fileName = $this->migrationPath . $this->timestamp . '_' . $className . '.php';
        file_put_contents($fileName, $fileContent);
        CLI::write("The generated trigger migration file: $fileName", 'yellow');

        // Increase the timestamp
        $this->timestamp++;
    }

    protected function parseFields($tableDefinition)
    {
        $fields = [];
        $lines = explode("\n", $tableDefinition);

        foreach ($lines as $line) {
            $line = trim($line, ", \t\n\r\0\x0B");
            // Skip the foreign key, primary key, or index lines
            if (preg_match('/(FOREIGN KEY|PRIMARY KEY|KEY|INDEX)/i', $line)) {
                continue;
            }
            if (preg_match('/`?(\w+)`?\s+(\w+)(\(\d+\))?\s*(UNSIGNED)?\s*(NOT NULL)?\s*(AUTO_INCREMENT)?/i', $line, $fieldMatch)) {
                $field = [
                    'name' => $fieldMatch[1],
                    'type' => strtoupper($fieldMatch[2]),
                ];
                if (!empty($fieldMatch[3])) {
                    $field['constraint'] = trim($fieldMatch[3], '()');
                }
                if (!empty($fieldMatch[4])) {
                    $field['unsigned'] = true;
                }
                if (empty($fieldMatch[5])) {
                    $field['null'] = true;
                }
                if (!empty($fieldMatch[6])) {
                    $field['auto_increment'] = true;
                }
                $fields[] = $field;
            }
        }

        return $fields;
    }

    protected function parsePrimaryKey($tableDefinition)
    {
        if (preg_match('/PRIMARY KEY \(`?(\w+)`?\)/i', $tableDefinition, $match)) {
            return $match[1];
        }
        return null;
    }

    protected function parseAlterTableForeignKeys($sqlContent)
    {
        $foreignKeys = [];
        $pattern = '/ALTER TABLE\s+`?(\w+)`?\s*ADD\s*(?:CONSTRAINT\s*`?(\w+)`?\s*)?FOREIGN KEY\s*\(`?(\w+)`?\)\s*REFERENCES\s*`?(\w+)`?\s*\(`?(\w+)`?\)\s*(?:(?:ON DELETE\s*(SET NULL|NO ACTION|CASCADE|RESTRICT|SET DEFAULT))?\s*(?:ON UPDATE\s*(SET NULL|NO ACTION|CASCADE|RESTRICT|SET DEFAULT))?)?/is';
        preg_match_all($pattern, $sqlContent, $matches, PREG_SET_ORDER);

        // Log the ALTER TABLE lines for debugging
        $alterLines = [];
        preg_match_all('/ALTER TABLE\s+`?\w+`?.*?;/is', $sqlContent, $alterMatches);
        foreach ($alterMatches[0] as $alterLine) {
            $alterLines[] = trim($alterLine);
        }
        if (empty($alterLines)) {
            CLI::write('No ALTER TABLE statement was found in the SQL file.', 'blue');
        } else {
            CLI::write('Debug: The ALTER TABLE statements found: ' . implode("\n", $alterLines), 'blue');
        }

        foreach ($matches as $index => $match) {
            $constraintName = !empty($match[2]) ? $match[2] : 'fk_' . $this->stripPrefix($match[1]) . '_' . $match[3] . '_' . $index;
            $foreignKeys[] = [
                'table' => $match[1],
                'constraint' => $constraintName,
                'field' => $match[3],
                'referenced_table' => $match[4],
                'referenced_field' => $match[5],
                'on_delete' => !empty($match[6]) ? $match[6] : 'CASCADE',
                'on_update' => !empty($match[7]) ? $match[7] : 'CASCADE',
            ];
        }

        // If foreign keys are found, log them
        if (!empty($foreignKeys)) {
            CLI::write('Debug: The foreign keys found: ' . json_encode($foreignKeys), 'blue');
        } else {
            CLI::write('Debug: Foreign key parsing failed. The regular expression did not match.', 'red');
        }

        return $foreignKeys;
    }

    protected function parseTriggers($sqlContent)
    {
        $triggers = [];
        $pattern = '/CREATE TRIGGER `?(\w+)`?\s+(BEFORE|AFTER)\s+(INSERT|UPDATE|DELETE)\s+ON `?(\w+)`?\s+FOR EACH ROW\s+(BEGIN\s+.*?\s+END|.*?);/is';
        preg_match_all($pattern, $sqlContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $triggerName = $match[1];
            $tableName = $match[4]; // Ham tablo adı
            $triggerSql = 'CREATE TRIGGER `' . $triggerName . '` ' . $match[2] . ' ' . $match[3] . ' ON `' . $tableName . '` FOR EACH ROW ' . $match[5];
            $triggerSql = preg_replace('/DELIMITER\s+.+?\s+/i', '', $triggerSql);
            $triggerSql = str_replace('//', ';', $triggerSql);
            $triggers[] = [
                'name' => $triggerName,
                'sql' => $triggerSql,
                'table' => $tableName,
            ];
        }

        return $triggers;
    }
}
