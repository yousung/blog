<?php

/*
 *  유효성 검사 Form
 */
if (!function_exists('hasError')) {
    function hasError($errName, $isMsg = false)
    {
        if ($errors = session('errors', new \Illuminate\Support\MessageBag())) {
            if ($isError = $errors->has($errName)) {
                return $isMsg
                    ? $errors->first($errName, '<span class="text-danger">:message</span>')
                    : 'has-error';
            }
        }
    }
}

/*
 * 태그삭제
 */
if (!function_exists('noTag')) {
    function noTag($detail)
    {
//        $detail = strip_tags($detail);
//        $detail = str_replace('&nbsp;', '', $detail);
//        $detail = preg_replace("/[#\&\+\-@=\/\\\:;\.\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", ',', $detail);
//        $detail = str_replace('\r\n', ',', $detail);
//        $detail = str_replace('\n', ',', $detail);

//        return preg_replace('/\r\n|\r|\n/', ',', $detail);
        return strip_tags($detail);
    }
}

/*
 * 태그삭제
 */
if (!function_exists('optimus')) {
    function optimus($id = null)
    {
        $factory = app(\Jenssegers\Optimus\Optimus::class);

        if (0 === func_num_args()) {
            return $factory;
        }

        return $factory->encode($id);
    }
}

/*
 *  캐시 키 생성
 */
if (!function_exists('cache_key')) {
    function cache_key($base = null)
    {
        $base = $base ?? \request()->route()->getName();

        $key = ($query = request()->getQueryString())
            ? $base.'.'.urlencode($query)
            : $base;

        return md5($key);
    }
}

/*
 *      URL Location isActive Helper
 *      경로에 해당 URL이 포함되어있는지 확인 하는 헬퍼
 */
if (!function_exists('hasUrl')) {
    function hasUrl($searchUrl, $same = false)
    {
        $fullUrl = request()->fullUrl();
        $isActive = false;
        if (is_array($searchUrl)) {
            foreach ($searchUrl as $url) {
                if ($same) {
                    if ($url === $fullUrl) {
                        $isActive = true;
                    }
                } elseif (false !== strpos($fullUrl, $url)) {
                    $isActive = true;
                }
            }
        } else {
            $isActive = $same
                ? $fullUrl === $searchUrl
                : false !== strpos($fullUrl, $searchUrl);
        }

        return $isActive ? 'active' : '';
    }
}

/*
 * 이미지 URL
 */
if (!function_exists('image_path')) {
    function image_path($path)
    {
        return "blog/image/$path";
    }
}

/*
 * 이미지 URL
 */
if (!function_exists('file_path')) {
    function file_path($path)
    {
        return "onemedia/file/$path";
    }
}

/*
 * Query String Generate
 */
if (!function_exists('query_string')) {
    function query_string(...$param)
    {
        $rtn = http_build_query(array_filter(\Request::only($param)));

        return $rtn ? '?'.$rtn : '';
    }
}

/*
 * 이미지 자르기
 */
if (!function_exists('image_crop')) {
    function image_crop($fileUrl, $size)
    {
        if (!$fileUrl || !$size) {
            return null;
        }

        $kakaoAK = env('KKAO_API');

        $size = explode('x', $size);

        $response = \Curl::to('https://kapi.kakao.com/v1/vision/thumbnail/crop')
            ->withData([
                'image_url' => $fileUrl,
                'width' => $size[0],
                'height' => $size[1],
            ])
            ->withHeader("Authorization: KakaoAK {$kakaoAK}")
            ->post();

        $data = json_decode($response);

        return ($data && $data->thumbnail_image_url)
            ? str_replace('http://', 'https://', $data->thumbnail_image_url)
            : null;
    }
}

/*
 * Change String Url Slug
 */
if (!function_exists('make_slug')) {
    function make_slug($string, $separator = '-')
    {
        $url = trim($string);
        $url = strtolower($url);
        $url = preg_replace('|[^a-z-A-Z가-힣\p{Arabic}0-9 _]|iu', '', $url);
        $url = preg_replace('/\s+/', ' ', $url);
        $url = str_replace(' ', $separator, $url);

        return $url;
    }
}

/*
 * Change String Url Slug
 */
if (!function_exists('get_images')) {
    function get_images(\App\Post $post)
    {
        $images = [];
        if ($post->context) {
            preg_match_all("/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/i", $post->context, $item);

            if ($item[1] && count($item[1])) {
                foreach ($item[1] as $image) {
                    $images[] = $image;
                }
            }
        }

        $images = array_merge($images, [\URL::to('/images/yousung.jpg')]);

        return array_filter($images);
    }
}
