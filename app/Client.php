<?php
namespace App;

use GuzzleHttp\ClientInterface;

class Client
{
    private $client;
    private $parser;

    private $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36';
    private $baseUrl = 'https://roosters.xedule.nl/Attendee/ChangeWeek/';

    private $group = '';
    private $ordeId = 130;
    private $code = 52257;
    private $attId = 1;

    private $storage = 'rooster.html';

    private $result;
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(ClientInterface $client, XeduleParser $parser, CacheInterface $cache)
    {
        date_default_timezone_set('Europe/Amsterdam');

        $this->client = $client;
        $this->parser = $parser;
        $this->cache = $cache;
    }

    public function setGroup($group){
        $this->group = $group;
    }

    private function cache(){
        file_put_contents($this->storage,$this->result);
        return $this;
    }

    private function getApi(){
        return $this->baseUrl . $this->code .'?Code=' . $this->group . '&OreId=' . $this->ordeId . '&AttId=' . $this->attId;
    }


    private function createForm($data)
    {
//        $this->result = file_get_contents('rooster.html');
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
