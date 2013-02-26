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
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return CDbTableSchema driver dependent table metadata, null if the table does not exist.
     */
    protected function loadTable($name) {
        
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @return array all table names in the database.
     */
    protected function findTableNames($schema = '') {
        $sql = <<<EOD
SELECT syscat.tables.tabname
FROM syscat.tables
WHERE syscat.type IN ('T', 'V')
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

}
