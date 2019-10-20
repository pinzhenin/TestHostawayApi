<?php

use GuzzleHttp\{
    Client,
    Promise
};

class ApiHostaway
{

    public $cache;
    public $logger;
    public $baseUri = 'https://api.hostaway.com';
    public $timeout = 3;
    public static $resources = [
        'countries' => '/countries',
        'timezones' => '/timezones'
    ];

    public function getClient()
    {
        $client = new Client(
            [
            'base_uri' => $this->baseUri,
            'timeout' => $this->timeout
            ]
        );
        return $client;
    }

    public function getResources(array $ids)
    {

        $client = $this->getClient();
        $cache = $this->cache;
        $logger = $this->logger;

        $resources = [];
        $promises = [];

        foreach ($ids as $id) {
            // Checking resource existence
            if (empty(self::$resources[$id])) {
                continue;
            }

            // Checking cache
            $cacheKey = "resource.{$id}";
            $resources[$id] = $cache->get($cacheKey);

            // Get resource from remote site
            if ($resources[$id] === NULL) {
                $uri = self::$resources[$id];
                $promises[$id] = $client->getAsync($uri);
                $logger->notice(
                    "Getting resource «{$id}» from remote site"
                );
            }
            // Get resource from cache
            else {
                $logger->notice(
                    "Getting resource «{$id}» from cache"
                );
            }
        }

        // Handle responses
        if (count($promises)) {
            $results = Promise\settle($promises)->wait();

            foreach ($results as $id => $result) {

                if ($result['state'] === 'fulfilled') {
                    $response = $result['value'];
                    $data = json_decode($response->getBody(), TRUE);

                    // No json format
                    if (is_null($data)) {
                        $data = [
                            'status' => 'error',
                            'message' => json_last_error()
                        ];
                        $logger->error(
                            "Bad json for resource «{$id}»"
                        );
                    }

                    // Save to cache
                    if ($data['status'] === 'success') {
                        $cacheKey = "resource.{$id}";
                        $cache->save($cacheKey, $data);
                    }
                }
                else {
                    $data = [
                        'status' => 'error',
                        'message' => 'No data received from server'
                    ];
                    $logger->error(
                        "No data received for resource «{$id}»"
                    );
                }

                $resources[$id] = $data;
            }
        }

        return $resources;
    }
}
