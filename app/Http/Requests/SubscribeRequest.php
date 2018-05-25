<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'email' => 'email'
        ];
    }
    
    public function attributes()
    {
        return [
            'name' => '닉네임',
            'email' => '이메일'
        ];
    }

    public function getData()
    {
        $this['email'] = encrypt($this->input('email'));
        $this['name'] = encrypt($this->input('name'));
        return $this->all();
    }
}
