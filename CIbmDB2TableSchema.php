<?php
/**
 * CIbmDB2TableSchema class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiidb2
 */

/**
 * CIbmDB2TableSchema represents the metadata for a DB2 table.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiidb2
 */
class CIbmDB2TableSchema extends CDbTableSchema
{
	/**
	 * @var string name of the schema that this table belongs to.
	 */
	public $schemaName;
}
