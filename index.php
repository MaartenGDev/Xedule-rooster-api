<?php
namespace App;
require_once 'vendor/autoload.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use GuzzleHttp\Client as GuzzleClient;
use MaartenGDev\Cache;
use MaartenGDev\LocalDriver;

$guzzle = new GuzzleClient();
$parser = new XeduleParser();

$dir = $_SERVER['DOCUMENT_ROOT'] . '/cache/';
$storage = new LocalDriver($dir);

$cache = new Cache($storage, 5);
$client = new Client($guzzle, $parser, $cache);

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = rawurldecode($_SERVER['REQUEST_URI']);

$week = 36;
$group = '95311OLVM4 (1)';

$dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET','/group/{group}/week/{id}',function($group,$id){
        return [$group,$id];
    });
    $r->addRoute('GET', '/week/{id}', function ($id) {
        return (int) $id;
    });
});

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

if ($routeInfo[0] === Dispatcher::FOUND) {
    $data = call_user_func_array($routeInfo[1],$routeInfo[2]);

    if(is_array($data)){
        list($group,$week) = $data;
    }else{
        $week = $data;
    }
}

$client->setGroup($group);

$week = $client->getWeek($week);

header("Access-Control-Allow-Origin: *");
echo json_encode($week);

