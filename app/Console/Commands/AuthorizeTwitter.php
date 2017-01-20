<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuthorizeTwitter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the oAuth access tokens';

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
        // $consumer_key = env('CONSUMER_KEY');
        // $consumer_secret = env('CONSUMER_SECRET');
    }
}
