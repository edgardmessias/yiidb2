<?php

/**
 * CIbmDB2Schema class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiidb2
 */

/**
 * CIbmDB2Schema is the class for retrieving metadata information from a IBM DB2 database.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiidb2
 */
class CIbmDB2Schema extends CDbSchema {

    /**
     * @var array the abstract column types mapped to physical column types.
     */
    public $columnTypes = array(
        'pk' => 'INTEGER not null GENERATED ALWAYS AS IDENTITY (START WITH 1 INCREMENT BY 1)',
        'string' => 'VARCHAR(255)',
        'text' => 'CLOB',
        'integer' => 'INTEGER',
        'float' => 'FLOAT',
        'decimal' => 'DECIMAL',
        'datetime' => 'TIMESTAMP',
        'timestamp' => 'TIMESTAMP',
        'time' => 'TIME',
        'date' => 'DATE',
        'binary' => 'BINARY',
        'boolean' => 'SMALLINT',
        'money' => 'DECIMAL(19,4)',
    );

    /**
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return CDbTableSchema driver dependent table metadata, null if the table does not exist.
     */
    protected function loadTable($name) {
        $table = new CIbmDB2TableSchema;
        $this->resolveTableNames($table, $name);
        if (!$this->findColumns($table)) {
            return null;
        }
        $this->findPrimaryKey($table);
        $this->findForeignKey($table);

        return $table;
    }

    /**
     * Quotes a table name for use in a query.
     * A simple table name does not schema prefix.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name) {
        return $name;
    }

    /**
     * Quotes a column name for use in a query.
     * A simple column name does not contain prefix.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name) {
        return $name;
    }

    /**
     * Generates various kinds of table names.
     * @param CIbmDB2TableSchema $table the table instance
     * @param string $name the unquoted table name
     */
    protected function resolveTableNames($table, $name) {
        $parts = explode('.', str_replace('"', '', $name));
        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name = $parts[1];
            $table->rawName = $this->quoteTableName($table->schemaName) . '.' . $this->quoteTableName($table->name);
        } else {
            $table->name = $parts[0];
            $table->rawName = $this->quoteTableName($table->name);
        }
    }

    /**
     * Collects the table column metadata.
     * @param CIbmDB2TableSchema $table the table metadata
     * @return boolean whether the table exists in the database
     */
    protected function findColumns($table) {

        $sql = <<<EOD
SELECT LOWER(colname) AS colname,
       colno,
       typename,
       CAST(default AS VARCHAR(254)) AS default,
       nulls,
       length,
       scale,
       identity
FROM syscat.columns
WHERE UPPER(tabname) = :table
ORDER BY colno
EOD;

        $command = $this->getDbConnection()->createCommand($sql);
        $command->bindValue(':table', strtoupper($table->name));

        if (($columns = $command->queryAll()) === array()) {
            return false;
        }

        foreach ($columns as $column) {
            $c = $this->createColumn($column);
            $table->columns[$c->name] = $c;
        }

        return (count($table->columns) > 0);
    }

    /**
     * Creates a table column.
     * @param array $column column metadata
     * @return CDbColumnSchema normalized column metadata
     */
    protected function createColumn($column) {
        $c = new CIbmDB2ColumnSchema;
        $c->name = $column['colname'];
        $c->rawName = $this->quoteColumnName($c->name);
        $c->allowNull = ($column['nulls'] == 'Y');
        $c->isPrimaryKey = false;
        $c->isForeignKey = false;
        $c->autoIncrement = ($column['identity'] == 'Y');

        if (preg_match('/(varchar|character|clob|graphic|binary|blob)/i', $column['typename'])) {
            $column['typename'] .= '(' . $column['length'] . ')';
        } elseif (preg_match('/(decimal|double|real)/i', $column['typename'])) {
            $column['typename'] .= '(' . $column['length'] . ',' . $column['scale'] . ')';
        }

        if (is_string($column['default'])) {
            $column['default'] = trim($column['default'], '\'');
        }
        $default = ($column['default'] == "NULL") ? null : $column['default'];

        $c->init($column['typename'], $default);
        return $c;
    }

    /**
     * Collects primary key information.
     * @param CIbmDB2TableSchema $table the table metadata
     */
    protected function findPrimaryKey($table) {
        $sql = <<<EOD
SELECT LOWER(colnames) AS colnames
FROM syscat.indexes
WHERE uniquerule = 'P'
  AND UPPER(tabname) = :table
EOD;
        $command = $this->getDbConnection()->createCommand($sql);
        $command->bindValue(':table', strtoupper($table->name));

        $indexes = $command->queryAll();
        foreach ($indexes as $index) {
            $columns = explode("+", ltrim($index['colnames'], '+'));
            foreach ($columns as $colname) {
                if (isset($table->columns[$colname])) {
                    $table->columns[$colname]->isPrimaryKey = true;
                    if ($table->primaryKey === null)
                        $table->primaryKey = $colname;
                    elseif (is_string($table->primaryKey))
                        $table->primaryKey = array($table->primaryKey, $colname);
                    else
                        $table->primaryKey[] = $colname;
                }
            }
        }

        /* @var $c CIbmDB2ColumnSchema */
        foreach ($table->columns as $c) {
            if ($c->autoIncrement && $c->isPrimaryKey) {
                $table->sequenceName = $c->rawName;
                break;
            }
        }
    }

    /**
     * Collects foreign key information.
     * @param CIbmDB2TableSchema $table the table metadata
     */
    protected function findForeignKey($table) {
        $sql = <<<EOD
SELECT 	LOWER(fk.colname) AS fkcolname,
	LOWER(pk.tabname) AS pktabname,
	LOWER(pk.colname) AS pkcolname
FROM syscat.references
INNER JOIN syscat.keycoluse AS fk ON fk.constname = syscat.references.constname
INNER JOIN syscat.keycoluse AS pk ON pk.constname = syscat.references.refkeyname AND pk.colseq = fk.colseq
WHERE UPPER(fk.tabname) = :table
EOD;
        $command = $this->getDbConnection()->createCommand($sql);
        $command->bindValue(':table', strtoupper($table->name));

        $indexes = $command->queryAll();
        foreach ($indexes as $index) {
            if (isset($table->columns[$index['fkcolname']])) {
                $table->columns[$index['fkcolname']]->isForeignKey = true;
            }
            $table->foreignKeys[$index['fkcolname']] = array($index['pktabname'], $index['pkcolname']);
        }
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @return array all table names in the database.
     */
    protected function findTableNames($schema = '') {
        $sql = <<<EOD
SELECT LOWER(tabname) AS tabname
FROM syscat.tables
WHERE type IN ('T', 'V')
  AND ownertype != 'S'

EOD;
        if ($schema !== '') {
            $sql .= <<<EOD
AND   syscat.tables.tabschema=:schema
EOD;
        }
        $sql .= <<<EOD
ORDER BY syscat.tables.tabname;
EOD;
        $command = $this->getDbConnection()->createCommand($sql);
        if ($schema !== '') {
            $command->bindParam(':schema', $schema);
        }
        return $command->queryColumn();
        ;
    }

    /**
     * Creates a command builder for the database.
     * This method overrides parent implementation in order to create a Informix specific command builder
     * @return CDbCommandBuilder command builder instance
     */
    protected function createCommandBuilder() {
        return new CIbmDB2CommandBuilder($this);
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     * @return string the SQL statement for truncating a DB table.
     * @since 1.1.6
     */
    public function truncateTable($table) {
        return "TRUNCATE TABLE " . $this->quoteTableName($table) . " IMMEDIATE ";
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The {@link getColumnType} method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     * @since 1.1.6
     */
    public function alterColumn($table, $column, $type) {
        $tableSchema = $this->getTable($table);
        $columnSchema = $tableSchema->getColumn(strtolower(rtrim($column)));

        $allowNullNewType = !preg_match("/not +null/i", $type);

        $type = preg_replace("/ +(not)? *null/i", "", $type);

        $sql = 'ALTER TABLE ' . $this->quoteTableName($table)
                . ' ALTER COLUMN ' . $this->quoteColumnName($column) . ' '
                . ' SET DATA TYPE ' . $this->getColumnType($type);

        if ($columnSchema->allowNull != $allowNullNewType) {
            if ($allowNullNewType) {
                $sql .= ' ALTER COLUMN ' . $this->quoteColumnName($column) . 'DROP NOT NULL';
            } else {
                $sql .= ' ALTER COLUMN ' . $this->quoteColumnName($column) . 'SET NOT NULL';
            }
        }
        return $sql;
    }

}
