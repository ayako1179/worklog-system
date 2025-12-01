<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AdminLoginRequest extends FormRequest
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
        ];
    }

    public function authenticate()
    {
        // 入力値でログイン試行
        $credentials = $this->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        // ロール確認
        $user = Auth::user();
        if ($user->role !== 'admin') {
            Auth::logout(); // セッション削除
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }
    }
}
