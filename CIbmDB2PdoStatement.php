<?php

/**
 * CIbmDB2PdoStatement class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiidb2
 */

/**
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiidb2
 */
class CIbmDB2PdoStatement extends PDOStatement {

    private $_stmt = null;
    private $_bindParam = array();
    private $_columnBindNumber = array();
    private $_defaultFetchMode = PDO::FETCH_BOTH;
    private $_attribute = array();
    static private $_attributeMap = array(
        PDO::CASE_NATURAL => DB2_CASE_NATURAL,
        PDO::CASE_UPPER => DB2_CASE_UPPER,
        PDO::CASE_LOWER => DB2_CASE_LOWER,
    );

    /**
     * DB2_BINARY, DB2_CHAR, DB2_DOUBLE, or DB2_LONG
     * @var array
     */
    static private $_typeMap = array(
        PDO::PARAM_INT => DB2_LONG,
        PDO::PARAM_STR => DB2_CHAR,
    );

    public function __construct($connection, $statement) {

        $matches = array();
        preg_match_all("/:(\w+)/", $statement, $matches);

        foreach ($matches[1] as $pos => $col) {
            $this->_columnBindNumber[$col] = $pos + 1;
        }

        $statement = preg_replace("/:(\w+)/", "?", $statement);

        $this->_stmt = @db2_prepare($connection, $statement);
        if (!$this->_stmt) {
            throw new CIbmDB2PdoException(db2_stmt_errormsg());
        }
    }

    public function bindValue($param, $value, $type = null) {
        return $this->bindParam($param, $value, $type);
    }

    public function bindParam($column, &$variable, $type = null, $length = null, $driver_options = null) {
        if (is_string($column) && isset($this->_columnBindNumber[substr($column, 1)])) {
            $numColumn = $this->_columnBindNumber[substr($column, 1)];
        } else {
            $numColumn = $column;
        }

        $this->_bindParam[$numColumn] = &$variable;

        if ($type && isset(self::$_typeMap[$type])) {
            $type = self::$_typeMap[$type];
        } else {
            $type = DB2_CHAR;
        }

        if (!db2_bind_param($this->_stmt, $numColumn, "variable", DB2_PARAM_IN, $type)) {
            throw new CIbmDB2PdoException(db2_stmt_errormsg());
        }
        return true;
    }

    public function closeCursor() {
        if (!$this->_stmt) {
            return false;
        }

        $this->_bindParam = array();
        db2_free_result($this->_stmt);
        $ret = db2_free_stmt($this->_stmt);
        $this->_stmt = false;
        return $ret;
    }

    public function columnCount() {
        if (!$this->_stmt) {
            return false;
        }
        return db2_num_fields($this->_stmt);
    }

    public function errorCode() {
        return db2_stmt_error();
    }

    public function errorInfo() {
        return array(
            0 => db2_stmt_errormsg(),
            1 => db2_stmt_error(),
        );
    }

    public function execute($params = null) {
        if (!$this->_stmt) {
            return false;
        }

        /* $retval = true;
          if ($params !== null) {
          $retval = @db2_execute($this->_stmt, $params);
          } else {
          $retval = @db2_execute($this->_stmt);
          } */
        if ($params === null) {
            ksort($this->_bindParam);
            $params = array_values($this->_bindParam);
        }
        $retval = @db2_execute($this->_stmt, $params);

        if ($retval === false) {
            throw new CIbmDB2PdoException(db2_stmt_errormsg());
        }
        return $retval;
    }

    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null) {
        $this->_defaultFetchMode = $fetchMode;
    }

    public function getIterator() {
        $data = $this->fetchAll();
        return new ArrayIterator($data);
    }

    public function fetch($fetchMode = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        $fetchMode = $fetchMode ? : $this->_defaultFetchMode;
        switch ($fetchMode) {
            case PDO::FETCH_BOTH:
                return db2_fetch_both($this->_stmt);
            case PDO::FETCH_ASSOC:
                return db2_fetch_assoc($this->_stmt);
            case PDO::FETCH_NUM:
                return db2_fetch_array($this->_stmt);
            case PDO::FETCH_OBJ:
                return db2_fetch_object($this->_stmt);
            case PDO::FETCH_COLUMN:
                return $this->fetchColumn();
            default:
                throw new CIbmDB2PdoException("Given Fetch-Style " . $fetchMode . " is not supported.");
        }
    }

    public function fetchAll($fetchMode = null, $fetch_argument = null, $ctor_args = array()) {
        $rows = array();
        while ($row = $this->fetch($fetchMode)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchColumn($columnIndex = 0) {
        $row = $this->fetch(PDO::FETCH_NUM);
        if ($row && isset($row[$columnIndex])) {
            return $row[$columnIndex];
        }
        return false;
    }

    public function rowCount() {
        return (@db2_num_rows($this->_stmt))? : 0;
    }

    public function setAttribute($attribute, $value) {
        $option = array();
        switch ($attribute) {
            case PDO::ATTR_CASE:
                $option['db2_attr_case'] = self::$_attributeMap[$value];
        }
        if (db2_set_option($this->_stmt, $option, 1)) {
            $this->_attribute[$attribute] = $value;
        }
    }

    public function getAttribute($attribute) {
        return $this->_attribute[$attribute];
    }

}
