<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StatusCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff'
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff'
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'present',
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'break',
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'finished',
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}
