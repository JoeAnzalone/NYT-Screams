<?php

namespace App;

use Cache;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
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

    public function getPageTitle(string $url)
    {
        $client = new Client();

        $cookie_jar = new \GuzzleHttp\Cookie\CookieJar();

        $html = (string) $client->request('GET', $url, [
            'cookies' => $cookie_jar,
        ])->getBody();

        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $og_title = $crawler->filter('[property="og:title"]')->first()->attr('content');

        return $og_title;
    }

    public function uploadImage(string $image_data)
    {
        return $this->postRequest('https://upload.twitter.com/1.1/media/upload.json', [
            'media_data' => $image_data,
        ]);
    }

    public function craftReply(array $tweet)
    {
        $expanded_url = $tweet['tweet']['entities']['urls'][0]['expanded_url'];

        $username = $tweet['tweet']['user']['screen_name'];
        $article_title = $this->getPageTitle($expanded_url) . ' And We\'re Screaming!';

        $image_data = (string) ImageHelper::createImage($article_title);
        $image_data = base64_encode($image_data);

        $media_id = $this->uploadImage($image_data)['media_id'];

        return [
            'in_reply_to_status_id' => $tweet['tweet']['id_str'],
            'status' => sprintf('.@%s', $username),
            'media_ids' => $media_id,
        ];
    }

    public function chooseLatestTweetAndReply()
    {
        $tweet = $this->chooseLatestTweet();
        $reply = $this->craftReply($tweet);
        $this->postTweet($reply);
    }
}
