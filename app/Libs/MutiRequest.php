<?php

namespace App\Libs;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;


class MutiRequest{

    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 7;  // 同时并发抓取
    private $users = [];


    public function __construct($totalPageCount,$users)
    {
        $this->totalPageCount = $totalPageCount;
        $this->users = $users;
    }

    public function run()
    {
        $this->totalPageCount = count($this->users);

        $client = new Client();

        $requests = function ($total) use ($client) {
            foreach ($this->users as $key => $user) {

                $uri = 'https://api.github.com/users/' . $user;
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){

                $res = json_decode($response->getBody()->getContents());

                echo "请求第 $index 个请求，用户 " . $this->users[$index] . " 的 Github ID 为：" .$res->id;

                $this->countedAndCheckEnded();
            },
            'rejected' => function ($reason, $index){
                $this->countedAndCheckEnded();
            },
        ]);

        // 开始发送请求
        $promise = $pool->promise();
        $promise->wait();
    }

    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            $this->counter++;
            return;
        }
    }

}