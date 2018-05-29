<?php

namespace App\Listeners;

use App\Events\ModelChange;
use App\Post;
use PhpXmlRpc\Value;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use App\Events\NaverBlog;
use Illuminate\Contracts\Queue\ShouldQueue;

class NaverBlogListener implements ShouldQueue
{
    private $apiUser;
    private $apiPassword;
    private $client;
    private $response;
    private $request;

    public function __construct()
    {
        $endPoint = 'https://api.blog.naver.com/xmlrpc';
        $this->apiUser = env('NAVER-BLOG-ID');
        $this->apiPassword = env('NAVER-BLOG-PASSWORD');
        $this->client = new Client($endPoint);
        $this->client->return_type = 'json';
        $this->client->setSSLVerifyPeer(false);
    }

    public function handle(NaverBlog $event)
    {
        $type = $event->type;
        $post = $event->post;

        if (method_exists($this, $type)) {
            $this->{$type}($post);
        }
    }

    /**
     * @param $method
     * @param $data
     * @return $result
     */
    private function result($method, $data)
    {
        $this->request = new Request($method, $data);
        $this->response = $this->client->send($this->request);

        $result = new \stdClass();

        $result->xml = $this->response->value();
        $result->errno = $this->response->faultCode();
        $result->errstr = $this->response->faultString();

        return $result;
    }

    /**
     * @param $context
     * @return string image url Change context
     */
    private function getImages($context)
    {
        preg_match_all("/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/i", $context, $item);
        $images = [];
        if ($item[1] && count($item[1])) {
            foreach ($item[1] as $image) {
                $images[] = $image;
            }
        }

        foreach ($images as $image) {
            $tempUrl = $this->uploadMedia($image);
            $replaceUrl = $tempUrl ?? $image;
            $context = str_replace($image, $replaceUrl, $context);
        }

        return $context;
    }

    /**
     * @param $post
     * @param $context
     * @return array struct
     */
    private function getStruct($post, $context)
    {
        $postUrl = route('post.show', optimus($post->id));
        $context = "<h2>{$post->subTitle}</h2>{$context}<br/><br/><a href=\"{$postUrl}\">{$postUrl}</a>";

        $struct = [
            'title' => new Value($post->title, 'string'),
            'description' => new Value($context, 'string'),
        ];

        if ($category = optional($post->series)->name) {
            $struct['categories'] = new Value(strip_tags(trim($category)), 'string');
        }

        if (count($post->tags)) {
            $tags = implode(',', optional($post->tags)->pluck('name')->toArray());
            $struct['tags'] = new Value(strip_tags(trim($tags)), 'string');
        }

        return $struct;
    }

    /*
     * Naver Blog New Post
     */
    private function new($post)
    {
        // 이미 naver에 작성된 상황이면 수정으로 변경
        if ($post->naver) {
            return $this->edit($post);
        }

        $method = 'metaWeblog.newPost';

        $context = $this->getImages($post->context);
        $struct = $this->getStruct($post, $context);

        $data = [
            new Value($this->apiUser, 'string'),
            new Value($this->apiUser, 'string'),
            new Value($this->apiPassword, 'string'),
            new Value($struct, 'struct'),
            new Value(true, 'boolean'),
        ];

        $result = $this->result($method, $data);

        $post->naver = $result->xml->me['string'] ?? null;
        $post->save();

        ModelChange::dispatch('post');
    }

    /*
     * Naver Blog Post Delete & New Post
     */
    private function edit($post)
    {
        // 네이버에 작성된 것이 없을 경우 새로 작성으로 변경
        if (!$post->naver) {
            return $this->new($post);
        }

        //삭제
        $this->del($post);
        $post->naver = null;
        $post->save();

        $this->new($post);
    }

    /*
     * Naver Blog Post Delete
     */
    private function del($post)
    {
        $method = 'blogger.deletePost';

        // 등록된 네이버 블로그가 없는 경우 취소
        if (!$post->naver) {
            return false;
        }

        $data = [
            new Value('', 'string'),
            new Value($post->naver, 'string'),
            new Value($this->apiUser, 'string'),
            new Value($this->apiPassword, 'string'),
            new Value(true, 'boolean'),
        ];

        $this->result($method, $data);
    }

    /*
     * Naver File Server Upload
     * return Naver Image Server Upload Image URL;
     */
    private function uploadMedia($url)
    {
        $tempFileName = explode('/', $url);
        $name = $fileName ?? $tempFileName[count($tempFileName) - 1];
        $mime = \Image::make($url)->mime();
        $bits = file_get_contents($url);

        $method = 'metaWeblog.newMediaObject';

        $struct = array(
            'bits' => new Value($bits, 'base64'),
            'type' => new Value($mime, 'string'),
            'name' => new Value($name, 'string'),
        );

        $media = array(
            new Value($this->apiUser, 'string'),
            new Value($this->apiUser, 'string'),
            new Value($this->apiPassword, 'string'),
            new Value($struct, 'struct'),
        );

        $result = $this->result($method, $media);

        return $result->xml->me['struct']['url']->me['string'] ?? null;
    }
}
