<?php
// Reference ONLY. The setup wizard normally writes the real file to private storage.
// Recovery: restore your backed-up config.php, or supply the exact existing dedicated database.
// Copy outside htdocs and restrict Windows ACLs before entering any real password.
return ['driver' => 'sqlite'];
// MySQL/MariaDB alternative (use only the credentials for the existing, dedicated database):
// return ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'database' => 'motorlog', 'user' => 'motorlog_app', 'password' => '<enter locally>'];
