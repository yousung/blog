@component('mail::message')
# {{ $post->title }}<br/>
## {{ $post->subTitle }}<br/>

Lovizu New Posting

@component('mail::button', ['url' => route('post.show', optimus($post->id))])
Goto Post
@endcomponent

Thanks, {{ $name }}<br>
by Lovizu.
@endcomponent
