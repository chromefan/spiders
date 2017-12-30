<?php

namespace App\Console\Commands;

use App\Libs\Http;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ixudra\Curl\Facades\Curl;

class PicGet extends Command
{
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

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->pic();
    }
    private function pic(){
        $base_url = 'http://www.58mm.top';
        $this->base_url = $base_url;
        $cates = DB::table('category')->get();
        $patterns = $this->getPattern();
        $client = new Client();
        foreach ($cates as $cate){
            $url = $base_url.'/'.$cate->cate_key;
            var_dump($url);
            $html = $client->request('get',$url)->getBody()->getContents();
            preg_match_all($patterns['list'],$html,$res);
            if(!empty($res[1])){
                $urls = $res[1];
                $srcs = $res[2];
                $titles = $res[3];
                foreach ($urls as $k=>$v){
                    $image_path = $this->saveImage($srcs[$k]);

                    $album['url'] = $urls[$k];
                    $album['src'] = $srcs[$k];
                    $album['title'] = $titles[$k];
                    $album['cate_id'] = $cate->id;
                    $album['path'] = $image_path;
                    $album_id = DB::table('album')->insertGetId($album);
                    if($album_id>0){
                        echo "\t $album_id";
                        $this->getPhotos($urls[$k],$album_id);
                    }
                }
            }
        }
    }
    private function getPhotos($url,$album_id){
        $client = new Client();
        $url = $this->base_url.$url;
        $patterns = $this->getPattern();
        $status = $client->request('get',$url)->getStatusCode();
        preg_match('|\/(\d+?)\.html|',$url,$res);
        $url_code = $res[1];

        if($status==200){
            $html  = $client->request('get',$url)->getBody()->getContents();
            preg_match($patterns['total_page'],$html,$res);
            $total_page = (int)$res[1];
            for($page=1;$page<=$total_page;$page++){

                if($page==1){
                    $page_url = $url;
                }else{
                    $page_url = $this->base_url.'/view/'.$url_code.'_'.$page.'.html';
                }
                $html  = $client->request('get',$page_url)->getBody()->getContents();
                preg_match($patterns['pic'],$html,$res);
                if(!empty($res[1])){
                    $image_path = $this->saveImage($res[1]);
                    $photo['url'] = $url;
                    $photo['src'] = $res[1];
                    $photo['title'] = $res[2];
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
        $ext = '.jpg';
        $filename = uniqid().time().$ext;
        $save_path = storage_path('images');
        $file_path = Http::mkMd5Dir($src,$save_path);
        $file = $file_path.$filename;
        $response = $client->get($src, ['save_to' => $file]);
        $image_path = Http::getMd5Dir($src);
        if($response->getStatusCode()==200){
            return $image_path.$filename;
        }
    }
    private function getPattern(){
        return [
            'list'=>"|href=\'(.+?)\'><img src=\'(.+?)\' alt=\'(.+?)\'/></a></p>|i",
            'pic'=>"|src='(.+?)' alt='(.+?)'\s+/>|i",
            'total_page'=>'|<a>共(\d+)页: </a>|'
        ];
    }

}
