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
        $sickleave = $api->get_wfo_sickleave_count($year, $month);
        $result['sickleave'] = $sickleave ? $sickleave : 0;
        $sickleave_year = $api->get_wfo_sickleave_count($year);
        $result['sickleave_year'] = $sickleave_year ? $sickleave_year : 0;
        $overtime = $api->get_wfo_overtime_hours_sum($year, $month);
        $result['overtime'] = $overtime;
        $overtime_year = $api->get_wfo_overtime_hours_sum($year);
        $result['overtime_year'] = $overtime_year;
        $overtime = $api->get_wfo_overtime_hours_sum_office_only($year, $month);
        $result['overtime_office_only'] = $overtime;
        $overtime_year = $api->get_wfo_overtime_hours_sum_office_only($year);
        $result['overtime_year_office_only'] = $overtime_year;

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

$router->post('/sickleave/add', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    }
    try {
        $day = date('Y-m-d', strtotime($j['day']));
        $api = new API();
        $result = $api->add_wfo_sickleave($day);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add Holiday!", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/bank-holiday/add', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    }
    try {
        $day = date('Y-m-d', strtotime($j['day']));
        $api = new API();
        $result = $api->add_wfo_bank_holidays($day);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add Bank Holiday!", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/working-days/year/(\d+)/month/(\d+)/working-days/(\d+)', function ($year, $month, $working_days) {
    try {
        $api = new API();
        $result = $api->add_wfo_working_days($year, $month, $working_days);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add new value! Data... year: " . strval($year) . " month: " . strval($month) . " working_days: " . strval($working_days), 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/overtime/add', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    }
    try {
        $date = date('Y-m-d', strtotime($j['date']));
        $hours = $j['hours'];
        $api = new API();
        $result = $api->add_wfo_overtime($date, $hours);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'added']);
        } else {
            throw new \Exception("Unable to add overtime!", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/overtime/delete', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
        die();
    }
    try {
        $date = date('Y-m-d', strtotime($j['date']));
        $api = new API();
        $result = $api->delete_wfo_overtime($date);
        if ($result) {
            echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => 'deleted']);
        } else {
            throw new \Exception("Unable to delete overtime!", 1);
        }
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/overtime/year/(\d+)', function ($year) {
    try {
        $api = new API();
        $days = $api->get_wfo_overtime($year);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $days]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/overtime/year/(\d+)/month/(\d+)', function ($year, $month) {
    try {
        $api = new API();
        $days = $api->get_wfo_overtime($year, $month);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $days]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/generate-commands', function () {
    try {
        $api = new API();
        $commands = $api->generate_wfo_custom_command();
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $commands]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});


$router->get('/get-tokens', function () {
    try {
        $api = new API();
        $commands = $api->get_access_tokens();
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $commands]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});


$router->post('/generate-token', function () {
    try {
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['token_name'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with token_name']]);
            die();
        }
        if (empty($j['token_name'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Token name cannot be empty']]);
            die();
        }
        $api = new API();
        $token = $api->generate_access_token($j['token_name']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['token' => $token]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/revoke-token', function () {
    try {
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['token_id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with token_id']]);
            die();
        }
        $api = new API();
        $result = $api->revoke_access_token($j['token_id']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});


$router->post('/get-info', function () {
    try {
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
            die();
        }
        $api = new API();
        $result = $api->get_info($j['token'], $j['in_x_days']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});


$router->get('/get-settings', function () {
    try {
        $api = new API();
        $settings = $api->get_settings();
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $settings]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/save-settings', function () {
    try {
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
            die();
        }
        $api = new API();
        $settings = $api->save_settings($j['days_to_show'], $j['language']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $settings ? 'saved' : 'not saved']]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/map', function () {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['map']) || !isset($j['type']) || !isset($j['name']) || !isset($j['imageBoundsX']) || !isset($j['imageBoundsY'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with map data']]);
            die();
        }
        $api = new API();
        if (!in_array($j['type'], $api->map_allowed_types())) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Invalid map type']]);
            die();
        }
        $result = $api->save_map($j['map'], $j['name'],  $j['imageBoundsX'], $j['imageBoundsY'], $j['type']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/map/delete', function () {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['map_id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with map data']]);
            die();
        }
        $api = new API();
        $result = $api->delete_map($j['map_id']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/maps', function () {
    try {
        log_in_check(true);
        $api = new API();
        $maps = $api->get_maps();
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $maps]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/maps/info', function () {
    try {
        log_in_check(true);
        $api = new API();
        $maps = $api->get_maps(true);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $maps]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/map/(\d+)/seats', function ($map_id) {
    try {
        log_in_check(true);
        $api = new API();
        $seats = $api->get_map_seats($map_id);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $seats]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/map/(\d+)', function ($map_id) {
    try {
        log_in_check(true);
        $api = new API();
        $map = $api->get_map($map_id);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => $map]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/seat', function () {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
            die();
        }
        $api = new API();
        $result = $api->create_seat($j['map_id'], $j['name'], $j['description'], $j['bookable'], $j['x'], $j['y']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/seats/bulk/(\d+)', function ($map_id) {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON']]);
            die();
        }
        $api = new API();
        $result = $api->bulk_create_seats($map_id, $j);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/seat/book-by-name', function () {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['seat_name']) || !isset($j['reservation_date']) || !isset($j['map_id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with seat_name and reservation_date']]);
            die();
        }
        $day = date('Y-m-d', strtotime($j['reservation_date']));
        $api = new API();
        $result = $api->book_seat_by_name($j['seat_name'], $day, $j['map_id']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/seat/book', function () {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['seat_id']) || !isset($j['reservation_date'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with seat_id and reservation_date']]);
            die();
        }
        $api = new API();
        $result = $api->book_seat($j['seat_id'], $j['reservation_date']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->post('/seat/unbook', function () {
    try {
        log_in_check(true);
        $j = json_decode(file_get_contents("php://input"), true);
        if (is_null($j) || $j === false || !isset($j['seat_id']) || !isset($j['reservation_date'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => ["code" => 400, 'message' => 'Accepts only JSON with seat_id and reservation_date']]);
            die();
        }
        $api = new API();
        $result = $api->unbook_seat($j['seat_id'], $j['reservation_date']);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/seat/booked', function () {
    try {
        log_in_check(true);
        $day = date('Y-m-d', strtotime(checkGetParam('date', NULL)));

        $api = new API();
        $result = $api->get_booked_seats($day);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/spot/booked', function () {
    try {
        log_in_check(true);
        $day = date('Y-m-d', strtotime(checkGetParam('date', NULL)));

        $api = new API();
        $result = $api->get_booked_parking_spot($day);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});

$router->get('/seats/recent/(\w+)', function ($map_type) {
    try {
        log_in_check(true);
        $api = new API();
        $result = $api->get_recent_seats($map_type);
        echo json_encode(['status' => ['code' => 200, 'message' => 'ok'], "data" => ['result' => $result]]);
    } catch (\Throwable $th) {
        handleErr($th);
    }
});



function checkGetParam($param, $default)
{
    if (isset($_GET[$param])) {
        return $_GET[$param];
    }
    return $default;
}

function handleErr($message)
{
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

function log_in_check($just_check = false)
{
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
