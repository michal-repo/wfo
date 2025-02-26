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
            $this->auth->login($email, $password, ((empty($_ENV["login_remember_duration"]) || intval($_ENV["login_remember_duration"]) === 0)  ? NULL : $_ENV["login_remember_duration"]));

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

    public function isRegisterEnabled() {
        return $_ENV["register_enabled"] === "true" ? true : false;
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

    public function get_wfo_days_count($year, $month = NULL) {
        $query = 'SELECT count(*) FROM wfo_days WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function get_wfo_days_feed($start, $end) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_days WHERE user_id = :user_id AND defined_date >= :start AND defined_date <= :end';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':start', $start, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, \PDO::PARAM_STR);
        $stmt->execute();
        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_holidays WHERE user_id = :user_id AND defined_date >= :start AND defined_date <= :end';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':start', $start, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, \PDO::PARAM_STR);
        $stmt->execute();
        $holidays_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_sickleave WHERE user_id = :user_id AND defined_date >= :start AND defined_date <= :end';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':start', $start, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, \PDO::PARAM_STR);
        $stmt->execute();
        $sickleave_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_bank_holidays WHERE user_id = :user_id AND defined_date >= :start AND defined_date <= :end';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':start', $start, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, \PDO::PARAM_STR);
        $stmt->execute();
        $bank_holidays_found = $stmt->fetchAll(\PDO::FETCH_COLUMN);


        $res = [];
        $begin = new \DateTime($start);
        $finish = new \DateTime($end);
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $finish);

        foreach ($period as $dt) {
            if (in_array($dt->format("Y-m-d"), $bank_holidays_found)) {
                $res[] = [
                    "title" => "🛋️ Bank holiday",
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => "#84003e",
                    "cursor" => "pointer"
                ];
            } elseif (in_array($dt->format("Y-m-d"), $holidays_found)) {
                $res[] = [
                    "title" => $_ENV['holiday_label'],
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => $_ENV['holiday_color'],
                    "cursor" => "pointer"
                ];
            } elseif (in_array($dt->format("Y-m-d"), $sickleave_found)) {
                $res[] = [
                    "title" => $_ENV['sickleave_label'],
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => $_ENV['sickleave_color'],
                    "cursor" => "pointer"
                ];
            } elseif (in_array($dt->format("Y-m-d"), $days_found)) {
                $res[] = [
                    "title" => $_ENV['office_day_label'],
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => $_ENV['office_day_color'],
                    "cursor" => "pointer",
                    "id" => 1
                ];
                $res[] = $this->generate_holiday_event($dt);
                $res[] = $this->generate_bank_holiday_event($dt);
                $res[] = $this->generate_sickleave_event($dt);
            } else {
                if (in_array($dt->format("N"), [1, 2, 3, 4, 5])) {
                    $res[] = [
                        "title" => $_ENV['home_day_label'],
                        "start" => $dt->format("Y-m-d"),
                        "end" => $dt->format("Y-m-d"),
                        "color" => $_ENV['home_day_color'],
                        "cursor" => "pointer",
                        "id" => 1
                    ];
                    $res[] = $this->generate_holiday_event($dt);
                    $res[] = $this->generate_bank_holiday_event($dt);
                    $res[] = $this->generate_sickleave_event($dt);
                }
            }
        }
        return $res;
    }

    private function generate_holiday_event($dt) {
        return [
            "title" => "🏖️ Add holiday",
            "start" => $dt->format("Y-m-d"),
            "end" => $dt->format("Y-m-d"),
            "color" => $_ENV['add_holiday_color'],
            "textColor" => $_ENV['add_holiday_text_color'],
            "cursor" => "pointer",
            "id" => 9
        ];
    }

    private function generate_sickleave_event($dt) {
        return [
            "title" => "🤒 Add Sick Leave",
            "start" => $dt->format("Y-m-d"),
            "end" => $dt->format("Y-m-d"),
            "color" => $_ENV['add_holiday_color'],
            "textColor" => $_ENV['add_holiday_text_color'],
            "cursor" => "pointer",
            "id" => 9
        ];
    }

    private function generate_bank_holiday_event($dt) {
        return [
            "title" => "🏢 Add Bank holiday",
            "start" => $dt->format("Y-m-d"),
            "end" => $dt->format("Y-m-d"),
            "color" => $_ENV['add_holiday_color'],
            "textColor" => $_ENV['add_holiday_text_color'],
            "cursor" => "pointer",
            "id" => 9
        ];
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

    public function delete_wfo_day($day) {
        $query = "DELETE from wfo_days WHERE user_id = :user_id AND defined_date = :day";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function switch_wfo_day($day) {
        $query = 'SELECT count(*) as count FROM wfo_days WHERE user_id = :user_id AND defined_date = :day';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->execute();
        $days_found = $stmt->fetchAll(\PDO::FETCH_COLUMN)[0];

        if ($days_found > 0) {
            $result = $this->delete_wfo_day($day);
        } else {
            $result = $this->delete_wfo_holidays($day);
            $result = $this->delete_wfo_sickleave($day);
            $result = $this->delete_wfo_bank_holidays($day);

            $query = "REPLACE INTO wfo_days (defined_date, user_id) VALUES (:day, :user_id)";

            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
            $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

            $result = $stmt->execute();
        }

        return $result;
    }

    public function get_wfo_month_target($year, $month) {
        $query = "select target from wfo_month_target WHERE month_of_target = :month_of_target AND year_of_target = :year_of_target AND user_id = :user_id limit 1";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':month_of_target', $month, \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);

        $stmt->execute();
        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function add_wfo_month_target($year, $month, $target) {
        $query = "REPLACE INTO wfo_month_target (month_of_target, year_of_target, `target`, user_id) VALUES (:month_of_target, :year_of_target, :target, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':month_of_target', $month, \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':target', $target, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    public function get_wfo_year_target($year) {
        $query = "select target from wfo_year_target WHERE year_of_target = :year_of_target AND user_id = :user_id limit 1";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);

        $stmt->execute();
        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function add_wfo_year_target($year, $target) {
        $query = "REPLACE INTO wfo_year_target (year_of_target, `target`, user_id) VALUES (:year_of_target, :target, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':target', $target, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    public function get_wfo_working_days($year, $month = NULL) {
        $query = "select working_days ";
        if (is_null($month)) {
            $query = "select SUM(working_days) ";
        }
        $query .= " from wfo_working_days WHERE `year` = :year AND user_id = :user_id ";
        if (!is_null($month)) {
            $query .= " and `month` = :month ";
        }
        $query .= " limit 1";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        if (!is_null($month)) {
            $stmt->bindValue(':month', $month, \PDO::PARAM_INT);
        }

        $stmt->execute();
        $target_found = $stmt->fetchColumn();
        return $target_found;
    }


    public function add_wfo_working_days($year, $month, $working_days) {
        $query = "REPLACE INTO wfo_working_days (`year`, `month`, working_days, user_id) VALUES (:year, :month, :working_days, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, \PDO::PARAM_INT);
        $stmt->bindValue(':working_days', $working_days, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    public function get_wfo_holidays($year, $month = NULL) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_holidays WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

    public function get_wfo_holidays_count($year, $month = NULL) {
        $query = 'SELECT count(*) FROM wfo_holidays WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function add_wfo_holiday($day) {
        $this->delete_wfo_day($day);

        $query = "REPLACE INTO wfo_holidays (defined_date, user_id) VALUES (:day, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_holidays($day) {
        $query = "DELETE from wfo_holidays WHERE user_id = :user_id AND defined_date = :day";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        return $stmt->execute();
    }


    public function get_wfo_bank_holidays($year, $month = NULL) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_bank_holidays WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

    public function add_wfo_bank_holidays($day) {
        $this->delete_wfo_day($day);

        $query = "REPLACE INTO wfo_bank_holidays (defined_date, user_id) VALUES (:day, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_bank_holidays($day) {
        $query = "DELETE from wfo_bank_holidays WHERE user_id = :user_id AND defined_date = :day";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function get_wfo_sickleave($year, $month = NULL) {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") FROM wfo_sickleave WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

    public function get_wfo_sickleave_count($year, $month = NULL) {
        $query = 'SELECT count(*) FROM wfo_sickleave WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function add_wfo_sickleave($day) {
        $this->delete_wfo_day($day);

        $query = "REPLACE INTO wfo_sickleave (defined_date, user_id) VALUES (:day, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_sickleave($day) {
        $query = "DELETE from wfo_sickleave WHERE user_id = :user_id AND defined_date = :day";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        return $stmt->execute();
    }
}
