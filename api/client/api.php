<?php

namespace CLIENT;

require 'vendor/autoload.php';
require_once 'db.php';

use MyDB\DB as DB;

class API {

    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    public function get_wfo_days($year, $month = NULL) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_days WHERE YEAR(defined_date) = :year';
        if (!is_null($month)) {
            $query .= " and MONTH(defined_date) = :month";
        }

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        if (!is_null($month)) {
            $stmt->bindValue(':month', $month, \PDO::PARAM_INT);
        }

        $stmt->execute();

        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $days_found;
    }

    public function get_wfo_days_feed($start, $end) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_days WHERE defined_date >= :start AND defined_date <= :end';

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':start', $start, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, \PDO::PARAM_STR);


        $stmt->execute();

        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $res = [];
        $begin = new \DateTime($start);
        $finish = new \DateTime($end);
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $finish);

        foreach ($period as $dt) {
            if (in_array($dt->format("Y-m-d"), $days_found)) {
                $res[] = [
                    "title" => $_ENV['office_day_label'],
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => $_ENV['office_day_color'],
                    "cursor" => "pointer"
                ];
            } else {
                $res[] = [
                    "title" => $_ENV['home_day_label'],
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => $_ENV['home_day_color'],
                    "cursor" => "pointer"
                ];
            }
        }
        return $res;
    }

    public function add_wfo_day($year, $month, $day) {
        $query = "REPLACE INTO wfo_days (defined_date) VALUES (:parsed)";

        $stmt = $this->db->dbh->prepare($query);
        $parsed = join("-", [$year, $month, $day]);
        $stmt->bindValue(':parsed', $parsed, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function switch_wfo_day($day) {
        $query = 'SELECT count(*) as count FROM wfo_days WHERE defined_date = :day';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        $stmt->execute();
        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN)[0];

        if ($days_found > 0) {
            $query = "DELETE from wfo_days WHERE defined_date = :day";

            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

            $result = $stmt->execute();
        } else {
            $query = "REPLACE INTO wfo_days (defined_date) VALUES (:day)";

            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

            $result = $stmt->execute();
        }

        return $result;
    }
}
