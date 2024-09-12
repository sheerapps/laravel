<?php

namespace App\Console\Commands;

use DateTime;
use DateTimeZone;

use Illuminate\Console\Command;

class SaveDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:daily_update';

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
        $apiController = new \App\Http\Controllers\ApiController;
        
        // $today = date('Y-m-d'); //$today
        // $today_live = new DateTime($today);
        // $today_live->modify('-1 days');
        // $date = $today_live->format('Y-m-d');

        // $date = date('Y-m-d', strtotime('now', strtotime('Asia/Kuala_Lumpur')));
        // if(date("Gi") <= 1829){
        //     $today = new DateTime($date, new DateTimeZone('Asia/Kuala_Lumpur'));
        //     $today->modify('-18 hours');
        //     $date = $today->format('Y-m-d');
        // }
        // $apiController->saveDataV1_2_0("date");

        // $this->info('Data saved successfully!');
        // $this->info('Data saved successfully! fetch '.$date.' now = '.$date = date('Y-m-d H:i:s', strtotime('now', strtotime('Asia/Kuala_Lumpur'))));
    }
}
