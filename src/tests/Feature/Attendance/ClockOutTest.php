<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_退勤ボタンが表示され_処理後に退勤済になる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '2025-12-01 09:00:00',
            'status' => 'present',
        ]);

        $this->travelTo(Carbon::parse('2025-12-01 10:00:00'));

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤');

        $this->travelTo(Carbon::parse('2025-12-01 18:00:00'));
        $this->actingAs($user)->post('/attendances/end');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'finished',
            'work_end' => '18:00:00',
        ]);
    }

    public function test_退勤時刻が勤怠一覧画面に正しく表示される()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '2025-12-01 09:00:00',
            'status' => 'present',
        ]);

        $this->travelTo(Carbon::parse('2025-12-01 10:00:00'));
        $this->actingAs($user)->post('/attendances/start');

        $this->travelTo(Carbon::parse('2025-12-01 18:00:00'));
        $this->actingAs($user)->post('/attendances/end');

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');
        $response->assertSee('18:00');
    }
}
