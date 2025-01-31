<?php

namespace CLIENT;

require 'vendor/autoload.php';
require_once 'db.php';

use MyDB\DB as DB;

class API {

    private $db;
    private $auth;

    public function __construct() {
        $this->db = new DB();
        $this->auth = new \Delight\Auth\Auth($this->db->dbh);
    }

    public function log_in($email, $password) {
        try {
            $this->auth->login($email, $password);

            return true;
        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new \Exception('Wrong email address');
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new \Exception('Wrong password');
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            throw new \Exception('Email not verified');
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new \Exception('Too many requests');
        }
        return false;
    }

    public function isLoggedIn() {
        return $this->auth->isLoggedIn();
    }

    public function logOut() {
        return $this->auth->logOut();
    }

    private function get_user_id() {
        return $this->auth->getUserId();
    }

    public function register($email, $password, $username) {
        try {
            if (\preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $username) === 0 && $_ENV["register_enabled"] === "true") {
                $userId = $this->auth->registerWithUniqueUsername($email, $password, $username);

                return 'We have signed up a new user with the ID ' . $userId;
            } else {
                throw new \Exception("Unable to register!", 1);
            }
        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new \Exception("Invalid email address!", 1);
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new \Exception("Invalid password!", 1);
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            throw new \Exception("User already exists!", 1);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new \Exception("Too many requests!", 1);
        } catch (\Delight\Auth\DuplicateUsernameException $e) {
            throw new \Exception("User exists!", 1);
        }
    }

    public function get_wfo_days($year, $month = NULL) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_days WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
        if (!is_null($month)) {
            $query .= " and MONTH(defined_date) = :month";
        }

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        if (!is_null($month)) {
            $stmt->bindValue(':month', $month, \PDO::PARAM_INT);
        }

        $stmt->execute();

        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $days_found;
    }

    public function get_wfo_days_feed($start, $end) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_days WHERE user_id = :user_id AND defined_date >= :start AND defined_date <= :end';

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
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
        $query = "REPLACE INTO wfo_days (defined_date, user_id) VALUES (:parsed, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $parsed = join("-", [$year, $month, $day]);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':parsed', $parsed, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function switch_wfo_day($day) {
        $query = 'SELECT count(*) as count FROM wfo_days WHERE user_id = :user_id AND defined_date = :day';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->execute();
        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN)[0];

        if ($days_found > 0) {
            $query = "DELETE from wfo_days WHERE user_id = :user_id AND defined_date = :day";

            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
            $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

            $result = $stmt->execute();
        } else {
            $query = "REPLACE INTO wfo_days (defined_date, user_id) VALUES (:day, :user_id)";

            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
            $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

            $result = $stmt->execute();
        }

        return $result;
    }
}
