<?php

namespace CLIENT;

require 'vendor/autoload.php';
require_once 'db.php';

use MyDB\DB as DB;

class API
{

    private $db;
    private $auth;

    public function __construct()
    {
        $this->db = new DB();
        $this->auth = new \Delight\Auth\Auth($this->db->dbh);
    }

    public function log_in($email, $password)
    {
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

    public function isLoggedIn()
    {
        return $this->auth->isLoggedIn();
    }

    public function isRegisterEnabled()
    {
        return $_ENV["register_enabled"] === "true" ? true : false;
    }

    public function logOut()
    {
        return $this->auth->logOut();
    }

    private function get_user_id()
    {
        return $this->auth->getUserId();
    }

    public function register($email, $password, $username)
    {
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

    public function get_wfo_days($year, $month = NULL)
    {
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

    public function get_wfo_days_count($year, $month = NULL)
    {
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

    public function get_wfo_days_feed($start, $end)
    {
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

        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") as defined_date, overtime_hours FROM wfo_overtime WHERE user_id = :user_id AND defined_date >= :start AND defined_date <= :end';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':start', $start, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, \PDO::PARAM_STR);
        $stmt->execute();
        $overtime_found = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $overtime_days = array_column($overtime_found, 'defined_date');


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
                $res[] = $this->generate_overtime_event($dt);
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
                    $res[] = $this->generate_overtime_event($dt);
                }
            }
            $overtime_key = array_search($dt->format("Y-m-d"), $overtime_days);
            if ($overtime_key !== false) {
                $res[] = [
                    "title" => "💪 " . $overtime_found[$overtime_key]['overtime_hours'] . "h Overtime",
                    "start" => $dt->format("Y-m-d"),
                    "end" => $dt->format("Y-m-d"),
                    "color" => "#a832a8",
                    "cursor" => "pointer"
                ];
            }
        }
        return $res;
    }

    private function generate_holiday_event($dt)
    {
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

    private function generate_overtime_event($dt)
    {
        return [
            "title" => "💪 Add Overtime",
            "start" => $dt->format("Y-m-d"),
            "end" => $dt->format("Y-m-d"),
            "color" => $_ENV['add_holiday_color'],
            "textColor" => $_ENV['add_holiday_text_color'],
            "cursor" => "pointer",
            "id" => 10
        ];
    }

    private function generate_sickleave_event($dt)
    {
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

    private function generate_bank_holiday_event($dt)
    {
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

    public function add_wfo_day($year, $month, $day)
    {
        $query = "REPLACE INTO wfo_days (defined_date, user_id) VALUES (:parsed, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $parsed = join("-", [$year, $month, $day]);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':parsed', $parsed, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_day($day)
    {
        $query = "DELETE from wfo_days WHERE user_id = :user_id AND defined_date = :day";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function switch_wfo_day($day)
    {
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

    public function get_wfo_month_target($year, $month)
    {
        $query = "select target from wfo_month_target WHERE month_of_target = :month_of_target AND year_of_target = :year_of_target AND user_id = :user_id limit 1";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':month_of_target', $month, \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);

        $stmt->execute();
        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function add_wfo_month_target($year, $month, $target)
    {
        $query = "REPLACE INTO wfo_month_target (month_of_target, year_of_target, `target`, user_id) VALUES (:month_of_target, :year_of_target, :target, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':month_of_target', $month, \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':target', $target, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    public function get_wfo_year_target($year)
    {
        $query = "select target from wfo_year_target WHERE year_of_target = :year_of_target AND user_id = :user_id limit 1";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);

        $stmt->execute();
        $target_found = $stmt->fetchColumn();
        return $target_found;
    }

    public function add_wfo_year_target($year, $target)
    {
        $query = "REPLACE INTO wfo_year_target (year_of_target, `target`, user_id) VALUES (:year_of_target, :target, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year_of_target', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':target', $target, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    public function get_wfo_working_days($year, $month = NULL)
    {
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


    public function add_wfo_working_days($year, $month, $working_days)
    {
        $query = "REPLACE INTO wfo_working_days (`year`, `month`, working_days, user_id) VALUES (:year, :month, :working_days, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, \PDO::PARAM_INT);
        $stmt->bindValue(':working_days', $working_days, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    public function get_wfo_holidays($year, $month = NULL)
    {
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

    public function get_wfo_holidays_count($year, $month = NULL)
    {
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

    public function add_wfo_holiday($day)
    {
        $this->delete_wfo_day($day);

        $query = "REPLACE INTO wfo_holidays (defined_date, user_id) VALUES (:day, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_holidays($day)
    {
        $query = "DELETE from wfo_holidays WHERE user_id = :user_id AND defined_date = :day";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        return $stmt->execute();
    }


    public function get_wfo_bank_holidays($year, $month = NULL)
    {
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

    public function add_wfo_bank_holidays($day)
    {
        $this->delete_wfo_day($day);

        $query = "REPLACE INTO wfo_bank_holidays (defined_date, user_id) VALUES (:day, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_bank_holidays($day)
    {
        $query = "DELETE from wfo_bank_holidays WHERE user_id = :user_id AND defined_date = :day";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function get_wfo_sickleave($year, $month = NULL)
    {
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

    public function get_wfo_sickleave_count($year, $month = NULL)
    {
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

    public function add_wfo_sickleave($day)
    {
        $this->delete_wfo_day($day);

        $query = "REPLACE INTO wfo_sickleave (defined_date, user_id) VALUES (:day, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_sickleave($day)
    {
        $query = "DELETE from wfo_sickleave WHERE user_id = :user_id AND defined_date = :day";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function add_wfo_overtime($date, $hours)
    {
        $query = "REPLACE INTO wfo_overtime (defined_date, overtime_hours, user_id) VALUES (:defined_date, :overtime_hours, :user_id)";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':defined_date', $date, \PDO::PARAM_STR);
        $stmt->bindValue(':overtime_hours', $hours, \PDO::PARAM_STR);

        $result = $stmt->execute();

        return $result;
    }

    public function delete_wfo_overtime($date)
    {
        $query = "DELETE from wfo_overtime WHERE user_id = :user_id AND defined_date = :date";

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, \PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function get_wfo_overtime($year, $month = NULL)
    {
        $query = 'SELECT DATE_FORMAT(defined_date , "%Y-%m-%d") as defined_date, overtime_hours FROM wfo_overtime WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

        $days_found = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $days_found;
    }

    public function get_wfo_overtime_hours_sum($year, $month = NULL)
    {
        $query = 'SELECT SUM(overtime_hours) FROM wfo_overtime WHERE user_id = :user_id AND YEAR(defined_date) = :year ';
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

        $sum = $stmt->fetchColumn();
        return $sum ? $sum : 0;
    }

    public function get_wfo_overtime_hours_sum_office_only($year, $month = NULL)
    {
        $query = 'SELECT SUM(t1.overtime_hours) FROM wfo_overtime as t1 INNER JOIN wfo_days as t2 ON t1.defined_date = t2.defined_date AND t1.user_id = t2.user_id WHERE t1.user_id = :user_id AND YEAR(t1.defined_date) = :year';
        if (!is_null($month)) {
            $query .= " and MONTH(t1.defined_date) = :month";
        }

        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        if (!is_null($month)) {
            $stmt->bindValue(':month', $month, \PDO::PARAM_INT);
        }

        $stmt->execute();

        $sum = $stmt->fetchColumn();
        return $sum ? $sum : 0;
    }

    public function generate_wfo_custom_command()
    {
        $prepared_commands = [];
        $query = 'SELECT command, days_in_advance FROM wfo_custom_command_generator WHERE user_id = :user_id ';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->execute();
        $commands = $stmt->fetchAll();

        foreach ($commands as $command) {
            $query = 'SELECT DATE_FORMAT(DATE_SUB(defined_date, INTERVAL :days_in_advance DAY), "%d.%m.%Y") as "date" FROM wfo_days WHERE user_id = :user_id and defined_date > DATE_ADD(CURRENT_DATE, INTERVAL :days_in_advance DAY)';
            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
            $stmt->bindValue(':days_in_advance', $command['days_in_advance'], \PDO::PARAM_INT);
            $stmt->execute();
            $days_for_placeholder = $stmt->fetchAll();

            foreach ($days_for_placeholder as $days) {
                $prepared_commands[] = str_replace("[placeholder]", $days['date'], $command['command']);
            }
        }

        return $prepared_commands;
    }

    public function generate_access_token($tokenName)
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        $token = $selector . ':' . $validator;
        $hashedValidator = hash('sha256', $validator);

        $query = "REPLACE INTO wfo_api_tokens (selector, hashed_validator, token_name, user_id) VALUES (:selector, :hashed_validator, :token_name, :user_id)";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':selector', $selector, \PDO::PARAM_STR);
        $stmt->bindValue(':hashed_validator', $hashedValidator, \PDO::PARAM_STR);
        $stmt->bindValue(':token_name', $tokenName, \PDO::PARAM_STR);
        $result = $stmt->execute();

        if ($result) {
            return $token;
        } else {
            throw new \Exception("Unable to generate token!", 1);
        }
    }

    public function get_access_tokens()
    {
        $query = "SELECT id, token_name, selector FROM wfo_api_tokens WHERE user_id = :user_id";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->execute();
        $tokens = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $tokens;
    }

    public function revoke_access_token($token_id)
    {
        $query = "DELETE FROM wfo_api_tokens WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->bindValue(':id', $token_id, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function get_info($token, $in_x_days)
    {
        $parts = explode(':', $token);
        if (count($parts) !== 2) {
            return ["status" => "home", "date" => null];
        }
        $selector = $parts[0];
        $validator = $parts[1];

        $query = 'SELECT t2.hashed_validator, t2.user_id FROM wfo_api_tokens as t2 WHERE t2.selector = :selector LIMIT 1';
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':selector', $selector, \PDO::PARAM_STR);
        $stmt->execute();
        $token_data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($token_data && hash_equals($token_data['hashed_validator'], hash('sha256', $validator))) {
            $query = 'SELECT t1.defined_date FROM wfo_days as t1 WHERE t1.user_id = :user_id AND t1.defined_date = DATE_ADD(CURRENT_DATE, INTERVAL :in_x_days DAY) LIMIT 1';
            $stmt = $this->db->dbh->prepare($query);
            $stmt->bindValue(':user_id', $token_data['user_id'], \PDO::PARAM_INT);
            $stmt->bindValue(':in_x_days', $in_x_days, \PDO::PARAM_INT);
            $stmt->execute();
            $day_found = $stmt->fetchColumn();

            if ($day_found) {
                return [
                    "status" => "office",
                    "date" => $day_found
                ];
            }
        }

        return [
            "status" => "home",
            "date" => null
        ];
    }

    public function get_settings()
    {
        $query = "SELECT days_to_show, language FROM settings WHERE user_id = :user_id";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $r = [];
        $r['days_to_show'] = $result['days_to_show'] ? explode(",", $result['days_to_show']) : [];
        $r['language'] = $result['language'] ?? 'en';
        return $r;
    }

    public function save_settings($days_to_show, $language)
    {
        if (is_array($days_to_show)) {
            $days_to_show = implode(",", $days_to_show);
        } else {
            $days_to_show = "";
        }
        $query = "REPLACE INTO settings (days_to_show, language, user_id) VALUES (:days_to_show, :language, :user_id)";
        $stmt = $this->db->dbh->prepare($query);
        $stmt->bindValue(':days_to_show', $days_to_show, \PDO::PARAM_STR);
        $stmt->bindValue(':language', $language, \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->get_user_id(), \PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if ($result) {
            return true;
        } else {
            throw new \Exception("Unable to save settings!", 1);
        }
    }
}
