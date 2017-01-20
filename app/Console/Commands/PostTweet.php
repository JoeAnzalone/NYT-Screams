<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PostTweet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tweet:post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post a tweet in response to someone';

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
        $twitter = new \App\TwitterHelper;
        $result = $twitter->chooseLatestTweetAndReply(env('TWITTER_TO_FOLLOW'));
    }
}
