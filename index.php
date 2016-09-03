<?php
namespace App;
require_once 'vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;

$guzzle = new GuzzleClient();
$parser = new XeduleParser();

$client = new Client($guzzle,$parser);

$week = $client->getWeek(36);

echo json_encode($week);

