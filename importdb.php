<?php
/**
 * This is a separate script that copies the GEWIS Report Database to the Web
 * database.
 *
 * It is a simple PostgreSQL to MySQL copy script.
 */

echo "Commencing sync with gewisdb\n";

try {
// connections
    $config = include 'config/autoload/gewisdb.local.php';

    $pgconn = new PDO('pgsql:host=' . $config['host'] . ';dbname=' . $config['dbname']
        . ';user=' . $config['user'] . ';password=' . $config['password']);
    $pgconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $doctrineConf = include 'config/autoload/doctrine.local.php';
    $params = $doctrineConf['doctrine']['connection']['orm_default']['params'];

    $myconn = new PDO(
        'mysql:host=' . $params['host'] . ';dbname=' . $params['dbname'] . ';charset=' . $params['charset'],
        $params['user'],
        $params['password']
    );
    $myconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* which tables to sync */
    $tables = [
        'Address',
        'BoardMember',
        'Decision',
        'MailingList',
        'Meeting',
        'Member',
        'members_mailinglists',
        'Keyholder',
        'Organ',
        'OrganMember',
        'organs_subdecisions',
        'SubDecision'
    ];

    echo "Connection with gewisdb set up\n";
    echo "Disabling foreign key constraints\n";

// to not trip up InnoDB
    $myconn->query('SET foreign_key_checks = 0');
    $myconn->query('START TRANSACTION');

    foreach ($tables as $table) {
        $query = "SELECT * FROM $table";
        $stmt = $pgconn->query($query);
        echo "Table $table\n";

        $truncate = "TRUNCATE TABLE $table";
        $myconn->query($truncate);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fields = '(' . implode(', ', array_keys($row)) . ')';
            $values = '(' . implode(', ', array_map(function ($a) {
                    return ':' . $a;
                }, array_keys($row))) . ')';

            $data = $row;

            // see if we can fetch about 256 more rows (gigantic speed increase)
            for ($i = 0; $i < 256 && ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)); $i++) {
                $values .= ', (' . implode(', ', array_map(function ($a) use ($i) {
                        return ':' . $a . $i;
                    }, array_keys($row2))) . ')';
                foreach ($row2 as $key => $value) {
                    $data[$key . $i] = $value;
                }
            }

            $sql = "INSERT IGNORE INTO $table $fields VALUES $values";
            $stmtt = $myconn->prepare($sql);

            try {
                $stmtt->execute($data);
            } catch (Exception $e) {
                echo "ERROR: Failed synchronization of table " . $table . "\n";
                echo $e->getMessage();
                echo "\n";
                echo $e->getTraceAsString();
                echo "\n\n";
            }

            echo '.';
        }
        echo "\n\n";
    }

    echo "Enabling foreign key constraints\n";

    $myconn->query('COMMIT');
    $myconn->query('SET foreign_key_checks = 1');

    echo "Sync with gewisdb completed \n\n\n";
} catch (Exception $e) {
    echo "ERROR: Sync with gewisdb failed because of exception\n";
    echo $e->getMessage();
    echo "\n";
    echo $e->getTraceAsString();
    echo "\n\n\n";
}
