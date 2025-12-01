<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤中の時_休憩入ボタンが表示され_処理後に休憩中になる()
    {
        //
    }

    public function test_出勤中なら_休憩は何回でもできて_休憩入ボタンが再表示される()
    {
        //
    }

    public function test_休憩中なら_休憩戻ボタンが表示され_処理後に出勤中に戻る()
    {
        //
    }

    public function test_休憩戻も何回でもできて_再度休憩入ボタンが表示される()
    {
        //
    }

    public function test_休憩時刻が勤怠一覧画面に正しく反映される()
    {
        //
    }
}
