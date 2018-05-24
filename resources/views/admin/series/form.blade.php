<div class="form-group">
    <label>Title</label>
    <input class="form-control" name="title" id="title" placeholder="Title" value="{{ old('title', $series->title) }}">
</div>

<div class="form-group">
    <label>Sub Title</label>
    <input class="form-control" name="subTitle" id="subTitle" placeholder="Sub Title" value="{{ old('subTitle', $series->subTitle) }}">
</div>

<div class="pull-right">
    <button type="submit" class="btn btn-primary">Submit Button</button>
    <button type="reset" class="btn btn-default">Reset Button</button>
</div>