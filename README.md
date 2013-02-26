YiiDB2
======

Support the DB2 database in Yii Framework

## Requirements
* PHP module pdo_ibm;
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
      'connectionString' => 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=testdb;HOSTNAME=11.22.33.444;PORT=56789;PROTOCOL=TCPIP;',
      'username' => 'username',
      'password' => 'password',
      'class' => 'ext.yiidb2.CIbmDB2Connection',
    ),
    ...
  ),
...
```

