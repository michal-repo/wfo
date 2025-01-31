<?php

namespace MyDB;

require_once 'vendor/autoload.php';


class DB {
    public $dbh;

    function __construct() {

        try {
            $this->dbh = new \PDO("mysql:dbname=" . $_ENV["db_name"] . ";host=" . $_ENV["db_host"], $_ENV["db_user"], $_ENV["db_pass"]);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            die();
        }
    }
}
