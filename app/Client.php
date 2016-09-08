<?php
namespace App;

use GuzzleHttp\ClientInterface;
use MaartenGDev\CacheInterface;

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

    public function setGroup($group)
    {
        $this->group = $group;
    }

    private function getApi()
    {
        return $this->baseUrl . $this->code . '?Code=' . $this->group . '&OreId=' . $this->ordeId . '&AttId=' . $this->attId;
    }


    private function createForm($data)
    {
        $cache = $this->cache->has('rooster', function ($cache) {
            return $this->result = $cache->get('rooster');
        });


        if ($cache) {
            return $this;
        }

        $data = $this->client->request(
            'POST',
            $this->getApi(),
            [
                'form_params' => $data,
                'headers' => [
                    'User-Agent' => $this->userAgent
                ]
            ]
        )->getBody();

        $this->cache->store('rooster',$data);

        $this->result = $data;
        return $this;
    }

    private function post()
    {
        return $this->parser->parse($this->result)->allWeek();
    }

    public function getWeek($week, $year = null)
    {
        if ($year === null) $year = date('Y');

        return $this->createForm([
            'currentWeek' => "{$year}/{$week}"
        ])->post();
    }
}
