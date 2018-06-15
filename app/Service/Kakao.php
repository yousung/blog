<?php

namespace app\Service;

class Kakao
{
    private $client;
    private $kakaoKey;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        $this->kakaoKey = env('KKAO_API');
    }

    public function thumbnail($file)
    {
        $size = getimagesize($file);
        $response = $this->client->post('https://kapi.kakao.com/v1/vision/thumbnail/crop', [
            'multipart' => [
                [
                    'name' => 'file',
                    'filename' => basename($file),
                    'contents' => file_get_contents($file),
                ],
                [
                    'name' => 'width',
                    'contents' => $size[0],
                ],
                [
                    'name' => 'height',
                    'contents' => $size[1],
                ],
            ],
            'headers' => [
                'Authorization' => " KakaoAK {$this->kakaoKey}",
            ],
        ]);

        $json = \GuzzleHttp\json_decode($response->getBody());

        return $json->thumbnail_image_url ?? '';
    }
}
