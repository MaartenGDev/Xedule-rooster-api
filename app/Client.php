<?php
namespace App;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use MaartenGDev\CacheInterface;

class Client
{
    private $client;
    private $parser;

    protected $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36';
    protected $baseUrl = 'https://roosters.xedule.nl/Attendee/ChangeWeek/';

    protected $group = '';
    protected $ordeId = 130;
    protected $code = 52257;
    protected $attId = 1;

    protected $result;
    protected $cache;

    public function __construct(ClientInterface $client, XeduleParser $parser, CacheInterface $cache)
    {
        date_default_timezone_set('Europe/Amsterdam');

        $this->client = $client;
        $this->parser = $parser;
        $this->cache = $cache;
    }

    /**
     * Set the group.
     *
     * @param string $group The group name.
     *
     * @return void
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * Build the website
     * url using the parameters.
     *
     * @return string
     */
    protected function getApi()
    {
        $query = http_build_query(
            [
                'Code' => $this->group,
                'OreId' => $this->ordeId,
                'AttId' => $this->attId
            ]
        );

        return $this->baseUrl . $this->code . '?' . $query;
    }


    /**
     * Checks if there is already a cache entry
     * and gets the data if isn't a cache entry.
     *
     * @param array $data The form data
     * @param int $week The week number
     * @return $this
     */
    protected function createForm(array $data, $week)
    {

        $key = 'rooster' . $week . $this->group;

        $cache = $this->cache->has(
            $key, function ($cache) use ($week, $key) {
            return $this->result = $cache->get($key);
        }
        );

        if ($cache) {
            return $this;
        }

        $data = $this->client->request(
            'POST',
            $this->getApi(),
            [
                'form_params' => $data,
                'headers' => [
                    'User-Agent' => $this->userAgent,
                    'Cookie' => 'ASP.NET_SessionId=secureForSure; _ga=GA1.234.1234.123; _gat=1',
                ]
            ]
        )->getBody();

        $this->cache->store($key, $data);

        $this->result = $data;
        return $this;
    }

    /**
     * Parse the html page returned using the parser.
     *
     * @return array
     */
    protected function post()
    {
        return $this->parser->parse($this->result)->allWeek();
    }

    /**
     * Get the week by sending a post request.
     *
     * @param integer $week The week number
     * @param string $year The year of the week.
     *
     * @return array
     */
    public function getWeek($week, $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        return $this->createForm(
            [
                'currentWeek' => "{$year}/{$week}"
            ], $week
        )
            ->post();
    }
}
