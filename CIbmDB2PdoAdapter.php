<?php

/**
 * CIbmDB2PdoAdapter class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiidb2
 */

/**
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiidb2
 */
class CIbmDB2PdoAdapter extends PDO {

    private $_conn = null;

    public function __construct($dsn, $username, $passwd, $options) {

        $dsn = substr($dsn, (int) strpos($dsn, ':'));

        $isPersistant = (isset($options['persistent']) && $options['persistent'] == true);

        if ($isPersistant) {
            $this->_conn = db2_pconnect($dsn, $username, $passwd, $options);
        } else {
            $this->_conn = db2_connect($dsn, $username, $passwd, $options);
        }
        if (!$this->_conn) {
            throw new CIbmDB2PdoException(db2_conn_errormsg());
        }
    }

    public function prepare($sql) {
        $stmt = @db2_prepare($this->_conn, $sql);
        if (!$stmt) {
            throw new CIbmDB2PdoException(db2_stmt_errormsg());
        }
        return new CIbmDB2PdoStatement($stmt);
    }

    public function query() {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function quote($input, $type = PDO::PARAM_STR) {
        $input = db2_escape_string($input);
        if ($type == PDO::PARAM_INT) {
            return $input;
        } else {
            return "'" . $input . "'";
        }
    }

    public function exec($statement) {
        $stmt = $this->prepare($statement);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function lastInsertId($name = null) {
        return db2_last_insert_id($this->_conn);
    }

    public function beginTransaction() {
        db2_autocommit($this->_conn, DB2_AUTOCOMMIT_OFF);
    }

    public function commit() {
        if (!db2_commit($this->_conn)) {
            throw new CIbmDB2PdoException(db2_conn_errormsg($this->_conn));
        }
        db2_autocommit($this->_conn, DB2_AUTOCOMMIT_ON);
    }

    public function rollBack() {
        if (!db2_rollback($this->_conn)) {
            throw new CIbmDB2PdoException(db2_conn_errormsg($this->_conn));
        }
        db2_autocommit($this->_conn, DB2_AUTOCOMMIT_ON);
    }

    public function errorCode() {
        return db2_conn_error($this->_conn);
    }

    public function errorInfo() {
        return array(
            0 => db2_conn_errormsg($this->_conn),
            1 => $this->errorCode(),
        );
    }

}
