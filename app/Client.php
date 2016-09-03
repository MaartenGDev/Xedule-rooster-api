<?php
namespace App;

use GuzzleHttp\ClientInterface;

class Client
{
    private $client;
    private $parser;

    private $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36';
    private $baseUrl = 'https://roosters.xedule.nl/Attendee/ChangeWeek/';

    private $class = '95311OLVM4%20(1)';
    private $ordeId = 130;
    private $week = 52257;
    private $attId = 1;

    private $storage = 'rooster.html';

    private $result;

    public function __construct(ClientInterface $client, XeduleParser $parser)
    {
        $this->client = $client;
        $this->parser = $parser;
    }

    private function cache(){
        file_put_contents($this->storage,$this->result);
        return $this;
    }

    private function getApi(){
        return $this->baseUrl . $this->week .'?Code=' . $this->class . '&OreId=' . $this->ordeId . '&AttId=' . $this->attId;
    }


    private function createForm($data)
    {
        $this->result = $this->client->request(
            'POST',
            $this->getApi(),
            [
                'form_params' => $data,
                'headers' => [
                    'User-Agent' => $this->userAgent
                ]
            ]
        )->getBody();

        return $this;
    }

    private function post(){
        return $this->parser->parse($this->result)->allWeek();
    }

    public function getWeek($week,$year = null)
    {
        if($year === null) $year = date('Y');

        return $this->createForm([
            'currentWeek' => "{$year}/{$week}"
        ])->cache()->post();
    }
}
