@push('script')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css">
    <script src="/ckeditor/ckeditor.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
@endpush

<div class="form-group">
    <label>Series</label>
    <select name="series_id" id="series_id" class="form-control">
            <option value="">시리즈 없음</option>
        @foreach(\App\Series::latest()->get() as $series)
            <option value="{{ $series->id }}" {{ $series->id === $post->series_id ? 'selected' : '' }} >{{ $series->title }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Title</label>
    <input class="form-control" name="title" id="title" placeholder="Title" value="{{ old('title', $post->title) }}">
</div>

<div class="form-group">
    <label>Sub Title</label>
    <input class="form-control" name="subTitle" id="subTitle" placeholder="Sub Title" value="{{ old('subTitle', $post->subTitle) }}"></div>

<div class="form-group">
    <label>Context</label>
    <textarea class="form-control ckeditor" name="context" id="context" rows="15">{!! old('context', e($post->context)) !!}</textarea>
</div>

<div class="form-group">
    <label>Tags</label>
    <input class="form-control" name="tags" data-role="tagsinput" value="{{ implode(',', optional($post->tags)->pluck('name')->toArray()) }}">
</div>

<div class="pull-right">
    <button type="submit" class="btn btn-primary">Submit Button</button>
</div>