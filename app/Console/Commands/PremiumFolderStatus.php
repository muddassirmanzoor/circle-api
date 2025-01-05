<?php

namespace App\Console\Commands;

use App\Purchases;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PremiumFolderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'premium_folder:change_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Premium Folder Pending Balance Transfer';

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
    //    Log::channel('premium_folder')->debug('Premium Folder');
      
    //    Log::channel('premium_folder')->debug($transactions_ids);
    }
}
