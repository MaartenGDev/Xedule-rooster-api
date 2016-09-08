<?php
namespace App;
require_once 'vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;
use MaartenGDev\Cache;
use MaartenGDev\LocalDriver;

$guzzle = new GuzzleClient();
$parser = new XeduleParser();

$dir = $_SERVER['DOCUMENT_ROOT'] . '/cache/';
$storage = new LocalDriver($dir);

$cache = new Cache($storage,5);
$client = new Client($guzzle,$parser,$cache);


$client->setGroup('95311OLVM4 (1)');

$week = $client->getWeek(36);

header("Access-Control-Allow-Origin: *");
echo json_encode($week);

