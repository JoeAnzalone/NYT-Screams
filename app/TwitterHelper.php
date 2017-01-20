<?php

namespace App;

use Cache;
use TwitterAPIExchange;

class TwitterHelper
{
    public function __construct()
    {
        $this->settings = [
            'oauth_access_token' => env('OAUTH_ACCESS_TOKEN'),
            'oauth_access_token_secret' => env('OAUTH_ACCESS_TOKEN_SECRET'),
            'consumer_key' => env('CONSUMER_KEY'),
            'consumer_secret' => env('CONSUMER_SECRET'),
        ];
    }

    public function request(string $url, string $get_field)
    {
        $request_method = 'GET';

        $twitter = new TwitterAPIExchange($this->settings);
        $response = $twitter->setGetfield($get_field)
            ->buildOauth($url, $request_method)
            ->performRequest();

        return json_decode($response, true);
    }

    public function getTweetsByUsername(string $username)
    {
        $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

        $query_params = [
            'screen_name' => $username,
            'exclude_replies' => true,
            'include_rts' => false,
            'trim_user' => true,
            'contributor_details' => false,
        ];

        $get_field = '?' . http_build_query($query_params);

        $tweets_zero_indexed = $this->request($url, $get_field);

        $tweets = $this->getAllCachedTweets();

        foreach ($tweets_zero_indexed as $tweet) {
            $tweets[$tweet['id_str']]['replied'] = !empty($tweets[$tweet['id_str']]['replied']) ? $tweets[$tweet['id_str']]['replied'] : false;
            $tweets[$tweet['id_str']]['tweet'] = $tweet;
        }

        Cache::put('tweets', $tweets, 60);

        return $tweets;
    }

    public function getAllCachedTweets()
    {
        return Cache::get('tweets', []);
    }
}
