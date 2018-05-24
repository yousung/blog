<?php

namespace App\Http\Requests;

use App\Tag;
use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'subTitle' => 'required',
            'context' => 'required',
        ];
    }

    public function getData()
    {
        $this['user_id'] = \Auth::user()->id;

        return $this->all();
    }

    public function getTags()
    {
        $tags = explode(',', $this->input('tags'));
        $modelTags = [];
        foreach ($tags as $tag) {
            $name = strtoupper($tag);
            $slug = make_slug($name);
            if ($slug) {
                if ($model = Tag::where('slug', $slug)->first()) {
                    $modelTags[] = $model->id;
                } else {
                    $model = Tag::create([
                        'name' => $name,
                        'slug' => $slug,
                    ]);
                }

                $modelTags[] = $model ? $model->id : null;
            }
        }

        return array_filter($modelTags);
    }
}
