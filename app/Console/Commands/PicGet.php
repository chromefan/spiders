<?php

namespace App\Console\Commands;

use App\Libs\Http;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class PicGet extends Command
{
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 7;  // 同时并发抓取
    private $users = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pic:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $base_url;
    protected $timeout;

    public function __construct()
    {
        parent::__construct();
        $this->base_url = 'https://www.meitulu.com';
        $this->timeout = 100;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info(date('Y-m-d H:i:s')."\n");
        $this->pic(1000);
        $this->info(date('Y-m-d H:i:s')."\n");
    }
    private function mutiRun(){

        $cates = DB::table('category')->get();
        $totalPage = 10;
        foreach ($cates as $cate){
            for ($i = 1 ; $i<=$totalPage; $i++){
                $uri = $cate->cate_key.'/list_'.$i.'.html';
                $this->pic($uri);
            }
        }

    }
    private function test(){
        $client = new Client();
        $url = 'https://www.meitulu.com/item/14050_26.html';
        try {
            $status = $client->request('get',$url,['timeout' => $this->timeout])->getStatusCode();
        } catch (RequestException $e) {
            var_dump($e->getCode());
        }
        exit;
    }
    private function pic($totalPage){

        $base_url = $this->base_url;
        $cates = DB::table('category')->get();
        $patterns = $this->getPattern();
        $client = new Client();
        foreach ($cates as $cate){
            for ($i = 1 ; $i<=$totalPage; $i++) {
                if($i == 1){
                    $url = $base_url.'/t/'.$cate->cate_key.'/';
                }else{
                    $url = $base_url.'/t/'.$cate->cate_key.'/'.$i.'.html';
                }
                try {
                    $client->request('get',$url,['timeout' => $this->timeout])->getStatusCode();
                } catch (RequestException $e) {
                    if($e->getCode() <> 200){
                        break;
                    }
                }

                $html = $client->request('get', $url, ['timeout' => $this->timeout])->getBody()->getContents();
                preg_match_all($patterns['list'], $html, $res);
                if (!empty($res[1])) {
                    $urls = $res[1];
                    $srcs = $res[2];
                    $titles = $res[3];
                    foreach ($urls as $k => $v) {
                        if (DB::table('album')->where('url', $urls[$k])->count() > 0) {
                            continue;
                        }
                        $image_path = $this->saveImage($srcs[$k]);
                        $album['url'] = $urls[$k];
                        $album['src'] = $srcs[$k];
                        $album['title'] = $titles[$k];
                        $album['cate_id'] = $cate->id;
                        $album['path'] = $image_path;
                        $album_id = DB::table('album')->insertGetId($album);
                        if ($album_id > 0) {
                            echo "\t $album_id";
                            $this->getPhotos($urls[$k], $album_id);
                        }
                    }
                }else{
                    break;
                }
            }
        }
    }
    private function getPhotos($url,$album_id){
        $client = new Client();

        $patterns = $this->getPattern();
        $status = 0;
        try {
            $status = $client->request('get',$url,['timeout' => $this->timeout])->getStatusCode();
        } catch (RequestException $e) {
            if($e->getCode() <> 200){
                return ;
            }
        }
        preg_match('|\/(\d+?)\.html|',$url,$res);
        $url_code = $res[1];
        if($status==200){
            $total_page = 100;
            for($page=1;$page<=$total_page;$page++){
                if($page==1){
                    $page_url = $url;
                }else{
                    $page_url = $this->base_url.'/item/'.$url_code.'_'.$page.'.html';
                }
                try {
                    $client->request('get',$page_url,['timeout' => $this->timeout]);
                } catch (RequestException $e) {
                    if($e->getCode() <> 200){
                        break ;
                    }
                }
                $html  = $client->request('get',$page_url,['timeout' => $this->timeout])->getBody()->getContents();
                preg_match_all($patterns['pic'],$html,$res);
                if(!isset($res[1])){
                    break;
                }
                foreach ($res[1] as $k =>$img_src){
                    var_dump($img_src);
                    $image_path = $this->saveImage($img_src);
                    $photo['url'] = $url;
                    $photo['src'] = $img_src;
                    $photo['title'] = $res[2][$k];
                    $photo['path'] = $image_path;
                    $photo['album_id'] = $album_id;
                    $photo_id = DB::table('photos')->insertGetId($photo);
                    echo "\t $photo_id";
                }
            }
        }
    }


    private function saveImage($src){

        $client = new Client(['verify' => false]);  //忽略SSL错误
        $headers = [
            'Content-Type' => 'image/jpg',
            'Referer' => 'https://www.meitulu.com/'
        ];
        $ext = '.jpg';
        $filename = uniqid().time().$ext;
        $save_path = '/var/www/web/pic/public/images';
        $file_path = Http::mkMd5Dir($src,$save_path);
        $file = $file_path.$filename;
        $response = $client->get($src, ['save_to' => $file,'headers'=>$headers]);
        $image_path = Http::getMd5Dir($src);
        if(!file_exists($file)){
            var_dump($src,$file);
            exit;
        }
        if($response->getStatusCode()==200){
            return $image_path.$filename;
        }
    }
    private function getPattern(){
        return [
            'list'=>"|<a href=\"(.+?)\" target=\"_blank\"><img src=\"(.+?)\" alt=\"(.+?)\"|i",
            'pic'=>"|<img src=\"(.+?)\" alt=\"(.+?)\" class=\"content_img\"|i",
            'total_page'=>'|<a>共(\d+)页: </a>|'
        ];
    }

    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            $this->counter++;
            return;
        }
    }

}
