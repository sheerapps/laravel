<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;

class SaveLiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:live_update';

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
        $start = time();
        while (time() - $start < 60) {
            $apiController = new \App\Http\Controllers\ApiController;
            $apiController->saveLiveDB("date");
            sleep(7);
        }
    }
}
