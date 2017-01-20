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
    public function getRequest(string $url, array $query_params)
    {
        $get_field = '?' . http_build_query($query_params);

        $twitter = new TwitterAPIExchange($this->settings);

        $response = $twitter->setGetfield($get_field)
            ->buildOauth($url, 'GET')
            ->performRequest();

        return json_decode($response, true);
    }

    public function postRequest(string $url, array $post_fields)
    {
        $twitter = new TwitterAPIExchange($this->settings);

        $response = $twitter->setPostFields($post_fields)
            ->buildOauth($url, 'POST')
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
            'trim_user' => false,
            'contributor_details' => false,
        ];

        $tweets_zero_indexed = $this->getRequest($url, $query_params);

        $tweets = $this->getAllCachedTweets();

        foreach ($tweets_zero_indexed as $tweet) {
            $tweets[$tweet['id_str']]['replied'] = !empty($tweets[$tweet['id_str']]['replied']) ? $tweets[$tweet['id_str']]['replied'] : false;
            $tweets[$tweet['id_str']]['tweet'] = $tweet;
        }

        Cache::put('tweets', $tweets, 60);

        return $tweets;
    }

    public function postTweet(array $tweet)
    {
        $url = 'https://api.twitter.com/1.1/statuses/update.json';

        $response = $this->postRequest($url, $tweet);

        return $response;
    }

    public function getAllCachedTweets()
    {
        return Cache::get('tweets', []);
    }

    public function chooseLatestTweet()
    {
        $tweets = $this->getAllCachedTweets();

        foreach ($tweets as $tweet) {
            $urls = $tweet['tweet']['entities']['urls'];

            if ($tweet['replied'] || !count($urls)) {
                continue;
            }

            foreach ($urls as $url) {
                if (
                    starts_with($url['expanded_url'], 'http://nyti.ms') ||
                    starts_with($url['expanded_url'], 'https://nyti.ms')
                ) {
                    return $tweet;
                }
            }
        }
    }

    public function replyToTweet(string $id_to_reply_to, string $status)
    {
        return $this->postTweet([
            'status' => $status,
            'in_reply_to_status_id' => $id_to_reply_to,
        ]);
    }

    public function getPageTitle(string $url)
    {
        $html = file_get_contents($url);
        return '';
    }

    public function craftReply(array $tweet)
    {
        $expanded_url = $tweet['tweet']['entities']['urls'][0]['expanded_url'];

        $username = $tweet['tweet']['user']['screen_name'];
        $article_title = $this->getPageTitle($expanded_url);

        return sprintf('.@%s The article title is %s', $username, $article_title);
    }

    public function chooseLatestTweetAndReply()
    {
        $tweet = $this->chooseLatestTweet();
        $status = $this->craftReply($tweet);
        $this->replyToTweet($tweet['tweet']['id_str'], $status);
    }
}
