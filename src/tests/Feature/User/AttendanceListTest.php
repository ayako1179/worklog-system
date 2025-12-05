<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分の勤怠情報が全て表示される()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '2025-12-01 09:00:00',
            'work_end' => '2025-12-01 18:00:00',
            'total_break_time' => '01:00:00',
            'total_work_time' => '08:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');
    }

    public function test_現在の月が表示される()
    {
        $user = User::factory()->create();

        $this->travelTo(Carbon::parse('2025-12-05'));

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('2025/12');
    }

    public function test_前月ボタンを押すと前月の情報が表示される()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-15',
            'work_start' => '2025-11-15 10:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');
        $response = $this->actingAs($user)->get('/attendance/list?month=2025-11');

        $response->assertSee('10:00');
        $response->assertSee('2025/11');
    }

    public function test_翌月ボタンを押すと翌月の情報が表示される()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-01-10',
            'work_start' => '2026-01-10 08:30:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');
        $response = $this->actingAs($user)->get('/attendance/list?month=2026-01');

        $response->assertSee('08:30');
        $response->assertSee('2026/01');
    }

    public function test_詳細ボタンを押すと勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');

        $detailUrl = route('attendance.detail.show', [
            'id' => $attendance->id,
            'date' => '2025-12-01'
        ]);

        $response->assertSee($detailUrl);
    }
}
