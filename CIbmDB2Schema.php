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

}
