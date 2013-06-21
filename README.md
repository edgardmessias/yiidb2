YiiDB2
======

Support the DB2 database in Yii Framework

## Requirements
* PHP module pdo and ibm_db2;
* DB2 Client installed
 
## Installation
* Install yiidb2 extension
* Extract the release file under `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
  'components' => array(
  ...
    'db' => array(
      'connectionString' => 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=database;HOSTNAME=hostname;PORT=port;PROTOCOL=TCPIP;',
      'username' => 'username',
      'password' => 'password',
      'class' => 'ext.yiidb2.CIbmDB2Connection',
    ),
    ...
  ),
...
```

