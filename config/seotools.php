<?php

return [
    'meta' => [
        /*
         * The default configurations to be used by the meta generator.
         */
        'defaults' => [
            'title' => '러비쥬', // set false to total remove
            'description' => '감성 개발자 러비쥬 블로그입니다', // set false to total remove
            'separator' => ' - ',
            'keywords' => ['개발', '감성', '기술&테크', '블로그', '감성블로그', '감성개발자', '감성 개발자', '러비쥬', 'Love Visual'],
            'canonical' => null, // Set null for using Url::current(), set false to total remove
        ],

        /*
         * Webmaster tags are always added.
         */
        'webmaster_tags' => [
            'google' => env('GOOGLE-WEB-MASTER'),
            'bing' => null,
            'alexa' => null,
            'pinterest' => null,
            'yandex' => null,
            'naver-site-verification' => env('NAVER-WEB-MASTER'),
        ],
    ],
    'opengraph' => [
        /*
         * The default configurations to be used by the opengraph generator.
         */
        'defaults' => [
            'title' => '러비쥬', // set false to total remove
            'description' => '감성 개발자 러비쥬 블로그입니다', // set false to total remove
            'url' => null, // Set null for using Url::current(), set false to total remove
            'type' => false,
            'site_name' => false,
            'images' => [
                '/images/yousung.jpg',
            ],
        ],
    ],
    'twitter' => [
        /*
         * The default values to be used by the twitter cards generator.
         */
        'defaults' => [
          //'card'        => 'summary',
          //'site'        => '@LuizVinicius73',
        ],
    ],
];
