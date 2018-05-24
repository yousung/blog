<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeriesRequest extends FormRequest
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
        ];
    }

    public function getData()
    {
        $this['slug'] = make_slug($this->input('title'));
        $this['user_id'] = \Auth::user()->id;
        return $this->all();
    }
}
