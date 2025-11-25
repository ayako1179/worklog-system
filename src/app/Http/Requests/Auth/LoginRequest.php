<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
// use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FortifyLoginRequest
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
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'ログイン情報が登録されていません',

            'password.required' => 'パスワードを入力してください',
            'password.min' => 'ログイン情報が登録されていません',
            'auth.failed' => 'ログイン情報が登録されていません',
        ];
    }
}
