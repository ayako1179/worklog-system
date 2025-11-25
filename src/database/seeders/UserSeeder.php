<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザー
        User::create([
            'role' => 'admin',
            'name' => '佐藤　花子',
            'email' => 'admin@coachtech.com',
            'password' => Hash::make('adminpass'),
            'email_verified_at' => now(),
        ]);

        // 一般ユーザー
        User::create([
            'role' => 'staff',
            'name' => '西　伶奈',
            'email' => 'reina.n@coachtech.com',
            'password' => Hash::make('password1'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'staff',
            'name' => '山田　太郎',
            'email' => 'taro.y@coachtech.com',
            'password' => Hash::make('password2'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'staff',
            'name' => '増田　一世',
            'email' => 'issei.m@coachtech.com',
            'password' => Hash::make('password3'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'staff',
            'name' => '山本　敬吉',
            'email' => 'keikichi.y@coachtech.com',
            'password' => Hash::make('password4'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'staff',
            'name' => '秋田　朋美',
            'email' => 'tomomi.a@coachtech.com',
            'password' => Hash::make('password5'),
        ]);

        User::create([
            'role' => 'staff',
            'name' => '中西　教夫',
            'email' => 'norio.n@coachtech.com',
            'password' => Hash::make('password6'),
        ]);
    }
}
