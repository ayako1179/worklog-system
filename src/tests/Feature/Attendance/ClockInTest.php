<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが勤務外の時に表示され_出勤処理後にステータスが勤務中になる()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 1, 9, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤');

        $this->post('/attendances/start');

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'present',
            'work_start' => '09:00:00',
        ]);
    }

    public function test_出勤は1日に1回のみで_退勤済や勤務中の場合は出勤ボタンが表示されない()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 1, 9, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff',
        ]);

        Attendance::factory()->finished()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('出勤');
    }

    public function test_出勤した時刻が勤怠一覧画面に正しく反映されている()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 1, 9, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendances/start');

        $attendance = Attendance::first();

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');

        $response->assertSee('09:00');
    }
}
