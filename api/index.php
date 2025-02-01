<?php

namespace Router;

require_once 'vendor/autoload.php';
require_once 'client/api.php';

use CLIENT\API as API;
use \Bramus\Router\Router as BRouter;
use Dotenv\Dotenv as Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();


$router = new BRouter();

header('Content-Type: application/json');

$router->set404('/api(/.*)?', function () {
    header('HTTP/1.1 404 Not Found');

    echo json_encode(['status' => ['code' => 404, 'message' => 'ok'], "data" => 'nothing here']);
});

// Define routes
$router->get('/', function () {
    echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'nothing here']);
});


$router->get('/check', function () {
    log_in_check(true);
});

$router->get('/register', function () {
    $api = new API();
    if ($api->isRegisterEnabled()) {
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => "Available"]);
        die();
    }
    header('HTTP/1.1 503 Service Unavailable');
    echo json_encode(['status' => ['code' => 503, 'message' => 'Unavailable'], "data" => "Unavailable"]);
    die();
});

$router->post('/register', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    } elseif (!empty($j["email"]) && !empty($j["password"]) && !empty($j["username"])) {
        try {
            $api = new API();
            $result = $api->register($j["email"], $j["password"], $j["username"]);
        } catch (\Throwable $th) {
            handleErr($th);
        }

        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $result]);
        die();
    }
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => ["code" => 400, 'message' => 'Bad request']]);
    die();
});

$router->post('/log-in', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    } elseif (!empty($j["email"]) && !empty($j["password"])) {
        try {
            $api = new API();
            $result = $api->log_in($j["email"], $j["password"]);
            if ($result === true) {
                echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => "Logged in"]);
                die();
            }
        } catch (\Throwable $th) {
            handleErr($th);
        }
    }
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => ["code" => 400, 'message' => 'Bad request']]);
    die();
});

$router->post('/log-out', function () {
    $api = new API();
    $api->logOut();
    echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => "Logged out"]);
    die();
});

$router->get('/feed', function () {
    try {
        log_in_check(true);
        $start = date('Y-m-d', strtotime(checkGetParam('start', NULL)));
        $end = date('Y-m-d', strtotime(checkGetParam('end', NULL)));

        $api = new API();
        $days = $api->get_wfo_days_feed($start, $end);
        if (is_array($days)) {
            echo json_encode($days);
        } else {
            throw new \Exception("Error Processing Request", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/year/(\d+)', function ($year) {
    try {
        $api = new API();
        $days = $api->get_wfo_days($year);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $days]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/year/(\d+)/month/(\d+)', function ($year, $month) {
    try {
        $api = new API();
        $days = $api->get_wfo_days($year, $month);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $days]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/year/(\d+)/month/(\d+)/day/(\d+)', function ($year, $month, $day) {
    try {
        $api = new API();
        $result = $api->add_wfo_day($year, $month, $day);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add new value! Data... year: " . strval($year) . " month: " . strval($month) . " day: " . strval($day), 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/switch', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    }
    try {
        $day = date('Y-m-d', strtotime($j['day']));
        $api = new API();
        $result = $api->switch_wfo_day($day);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add switch data!", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/target/year/(\d+)/month/(\d+)', function ($year, $month) {
    try {
        $api = new API();
        $result = [];
        $year_target = $api->get_wfo_year_target($year);
        $result['year_target'] = $year_target ? $year_target : null;
        $month_target = $api->get_wfo_month_target($year, $month);
        $result['month_target'] = $month_target ? $month_target : $result['year_target'];
        $office_days = $api->get_wfo_days_count($year, $month);
        $result['office_days'] = $office_days;
        $office_days_year = $api->get_wfo_days_count($year);
        $result['office_days_year'] = $office_days_year;
        $working_days = $api->get_wfo_working_days($year, $month);
        $result['working_days'] = $working_days ? $working_days : 0;
        $working_days_year = $api->get_wfo_working_days($year);
        $result['working_days_year'] = $working_days_year ? intval($working_days_year) : 0;
        $holidays = $api->get_wfo_holidays_count($year, $month);
        $result['holidays'] = $holidays ? $holidays : 0;
        $holidays_year = $api->get_wfo_holidays_count($year);
        $result['holidays_year'] = $holidays_year ? $holidays_year : 0;
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $result]);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['status' => ['code' => 404, 'message' => 'ok'], "data" => 'no data']);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/target/year/(\d+)/month/(\d+)/target/(\d+)', function ($year, $month, $target) {
    try {
        $api = new API();
        $result = $api->add_wfo_month_target($year, $month, $target);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add new value! Data... year: " . strval($year) . " month: " . strval($month) . " target: " . strval($target), 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/target/year/(\d+)/target/(\d+)', function ($year, $target) {
    try {
        $api = new API();
        $result = $api->add_wfo_year_target($year, $target);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add new value! Data... year: " . strval($year) . " target: " . strval($target), 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/holiday/add', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    }
    try {
        $day = date('Y-m-d', strtotime($j['day']));
        $api = new API();
        $result = $api->add_wfo_holiday($day);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add Holiday!", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

function checkGetParam($param, $default) {
    if (isset($_GET[$param])) {
        return $_GET[$param];
    }
    return $default;
}

function handleErr($message) {
    header('HTTP/1.1 500 Internal Server Error');
    if ($_ENV['debug'] === "true") {
        var_dump($message);
        die();
    } else {
        file_put_contents('logs.txt', "### " . date('Y-m-d H:i:s') . PHP_EOL . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        echo json_encode(['status' => ['code' => 500, 'message' => 'Ops! Error! Contact Administrator.']]);
        die();
    }
}

function log_in_check($just_check = false) {
    $api = new API();
    if ($api->isLoggedIn()) {
        if (!$just_check) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => "ok"]);
            die();
        }
        return;
    }
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => ["code" => 401, 'message' => 'Unauthorized']]);
    die();
}

$router->run();
