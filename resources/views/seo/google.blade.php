{{--Analytics--}}
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-112233205-2"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-112233205-2');
</script>

<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
    // (adsbygoogle = window.adsbygoogle || []).push({
    //     google_ad_client: "ca-pub-1322828698634218",
    //     enable_page_level_ads: true
    // });
</script>

{!! isset($itemSchma) ? $itemSchma->toScript() : '' !!}
{!! isset($itemListSchma) ? $itemListSchma->toScript() : '' !!}