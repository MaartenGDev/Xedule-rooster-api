<?php
namespace App;
require_once 'vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;

$guzzle = new GuzzleClient();
$parser = new XeduleParser();
$cache = new Cache();
$client = new Client($guzzle,$parser,$cache);


$client->setGroup('95311OLVM4 (1)');

$week = $client->getWeek(36);

header("Access-Control-Allow-Origin: *");
echo json_encode($week);

