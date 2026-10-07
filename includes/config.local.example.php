<?php
// Copy this file to config.local.php and fill in your web host's MySQL details.
// On InfinityFree they're under Control Panel → MySQL Databases.
// Anything set here overrides the defaults in config.php.

return [
    'debug'   => false,

    'db_host' => 'sqlXXX.infinityfree.com',   // "MySQL Hostname"
    'db_user' => 'if0_XXXXXXXX',              // "MySQL Username"
    'db_pass' => 'your-vpanel-password',      // "MySQL Password" (your hosting account password)
    'db_name' => 'if0_XXXXXXXX_lockhub',      // the database you created, full name with prefix
];
