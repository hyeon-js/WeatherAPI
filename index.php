<?php
header('Content-Type: application/json; charset=utf-8');
// header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Seoul'); //한국 시간대로 설정
// ini_set('display_errors', '1'); //디버깅용

include 'ConvGridGps.php';

$x = $_GET['x'];
$y = $_GET['y'];

$ConvGridGps = new ConvGridGps();
$grid = $ConvGridGps->gpsToGRID($x, $y);

$key = '안알랴줌';
$url = 'http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getVilageFcst?serviceKey='.$key.'&pageNo=1&numOfRows=1000&dataType=JSON&base_date='.get_date().'&base_time=2300&nx='.$grid['x'].'&ny='.$grid['y'];

$response = get_web_text($url);
// echo $response;

$data = json_decode($response, true);
if ($data['response']['header']['resultCode'] != '00') {
    echo '[]';
    exit;
}

$data = $data['response']['body']['items']['item'];
$result = [];
for ($n = 0; $n < count($data); $n++) {
    $datum = $data[$n];
    $time = $datum['fcstTime'];
    if (!array_key_exists($time, $result)) $result[$time] = [];
    $result[$time][$datum['category']] = $datum['fcstValue'];
}

echo json_encode(convert_data($result), JSON_UNESCAPED_UNICODE);

function convert_data($data) {
    $skys = [null, '맑음', null, '구름많음', '흐림'];
    $rains = [null, '비', '비 또는 눈', '눈', '소나기'];
    $result = [];
    for ($n = 0; $n < 24; $n++) {
        $time = $n > 9 ? $n.'00' : '0'.$n.'00';
        $datum = $data[$time];
        $result[$n] = [];
        $result[$n]['time'] = $n.'시';
        $result[$n]['sky'] = $datum['PTY'] == 0 ? $skys[$datum['SKY']] : $rains[$datum['PTY']];
        $result[$n]['tmp'] = $datum['TMP'].'℃';
        $result[$n]['hum'] = $datum['REH'].'%';
        $result[$n]['wind'] = deg2dir($datum['VEC']).'풍, '.$datum['WSD'].'m/s';
        $result[$n]['rain'] = $datum['POP'].'%';
    }
    return $result;
}

function deg2dir($deg) {
    $deg = (int)$deg;
    $dirs = ['북', '북동', '동', '남동', '남', '남서', '서', '북서', '북'];
    $dir = ($deg + 22.5) / 45;
    return $dirs[(int)$dir];
}

function get_date() {
    $now = time();
    return date('Ymd', $now - 24 * 60 * 60);
}

function get_web_text($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
?>
