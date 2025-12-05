<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
// use Illuminate\Foundation\Testing\WithFaker;

class AttendanceListAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者は当日の全ユーザーの勤怠情報を確認できる()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $userA = User::factory()->create(['role' => 'staff']);
        $userB = User::factory()->create(['role' => 'staff']);

        $today = '2025-12-01';

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'work_date' => $today,
            'work_start' => '09:00',
            'work_end' => '18:00',
            'total_break_time' => '01:00:00',
            'total_work_time' => '08:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $userB->id,
            'work_date' => $today,
            'work_start' => '10:00',
            'work_end' => '19:00',
            'total_break_time' => '00:30:00',
            'total_work_time' => '08:30:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=' . $today);

        $response->assertSee($userA->name);
        $response->assertSee($userB->name);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_管理者画面に現在の日付が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->travelTo(Carbon::parse('2025-12-01'));

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2025/12/01');
    }

    public function test_前日ボタンで前日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->travelTo(Carbon::parse('2025-12-01'));

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2025-11-30');

        $response->assertStatus(200);
        $response->assertSee('2025/11/30');
    }

    public function test_翌日ボタンで翌日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->travelTo(Carbon::parse('2025-12-01'));

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2025-12-02');

        $response->assertStatus(200);
        $response->assertSee('2025/12/02');
    }
}
