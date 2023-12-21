<?php

namespace App\Console\Commands;

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
        $apiController = new ApiController();
        $apiController->saveData(date('Y-m-d'));
        
        $this->info('Data saved successfully!');
    }
}
