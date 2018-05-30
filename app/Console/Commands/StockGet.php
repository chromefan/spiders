<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockGet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $base_url;
    protected $timeout;

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $this->go(1000);
        $this->info(date('Y-m-d H:i:s')."\n");
    }

    private function go(){
        $client = new Client();
        $max = 4000;
        $limit = 100;

        for ($p = 1; $p <= 36; $p++){
            $url = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?'
                .'cb=jQuery1124033732334340145465_1527668240767&type=CT&token=4f1862fc3b5e77c150a2b985b12db0fd&js=(%7Bdata%3A%5B(x)%5D%2CrecordsTotal%3A(tot)%2CrecordsFiltered%3A(tot)%7D)&cmd=C._A&sty=FCOIATC&'
                .'st=(ChangePercent)&sr=-1&p='.$p.'&ps='.$limit.'&_=1527668240768';
            try {
                $status = $client->request('get',$url,['timeout' => $this->timeout])->getStatusCode();
            } catch (RequestException $e) {
                var_dump($e->getCode());
                exit;
            }
            $html = file_get_contents($url);
            preg_match('/\[.+?\]/',$html,$res);
            $json = json_decode($res[0],true);
            $stock_data=$price=[];
            foreach ($json as $k=> $v){
                $stock = explode(',',$v);

                $find_num = DB::connection('stock')->table('stock')->where('code',$stock[1])->count();
                if($find_num>0){
                    $update_data['name'] = $stock[2];
                    $update_data['price'] = (float)$stock[3];
                    $update_data['updated_at'] = date('Y-m-d H:i:s');
                    DB::connection('stock')->table('stock')->where('code',$stock[1])->update($update_data);
                }else{
                    $stock_data[$k]['code'] = $stock[1];
                    $stock_data[$k]['name'] = $stock[2];
                    $stock_data[$k]['price'] = (float)$stock[3];
                    $stock_data[$k]['market_type'] = 'hsa';
                }
                $price[$k]['code']= $stock[1];
                $price[$k]['price']= (float)$stock[3];
                $price[$k]['price_change']= (float)$stock[5];
                $price[$k]['vol']= (float)$stock[6];
                $price[$k]['turnover']= (float)$stock[15];
                $price[$k]['day']= date('Ymd');
            }
            DB::connection('stock')->table('stock')->insert($stock_data);
            DB::connection('stock')->table('price')->insert($price);
            echo "$p\n";
        }

    }
}
