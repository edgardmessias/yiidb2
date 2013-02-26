<?php

/**
 * CIbmDB2Connection class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiidb2
 */

/**
 * CInformixConnection represents a connection to a IBM DB2 database.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiidb2
 */
class CIbmDB2Connection extends CDbConnection {

    protected function initConnection($pdo) {
        parent::initConnection($pdo);
        $this->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    }

    public $driverMap = array(
        'ibm' => 'CIbmDB2Schema', // IBM DB2 driver
    );

}

$dir = dirname(__FILE__);
$alias = md5($dir);
Yii::setPathOfAlias($alias, $dir);
Yii::import($alias . '.*');
