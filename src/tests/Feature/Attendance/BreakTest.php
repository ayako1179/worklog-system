<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Carbon::setTestNow('2025-12-01 09:00');

        $user = User::factory()->create();
        $attendance = Attendance::factory()->present()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/breaks/start');
        $this->post('/breaks/end');

        Carbon::setTestNow('2025-12-01 11:00');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');
    }

    public function test_出勤中なら_休憩は何回でもできて_休憩入ボタンが再表示される()
    {
        Carbon::setTestNow('2025-12-01 10:00');

        $user = User::factory()->create();
        Attendance::factory()->present()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/breaks/start');
        $this->post('/breaks/end');

        Carbon::setTestNow('2025-12-01 11:00');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');
    }

    public function test_休憩中なら_休憩戻ボタンが表示され_処理後に出勤中に戻る()
    {
        Carbon::setTestNow('2025-12-01 12:00');

        $user = User::factory()->create();
        Attendance::factory()->present()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/breaks/start');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');

        $this->post('/breaks/end');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'present',
        ]);
    }

    public function test_休憩戻も何回でもできて_再度休憩入ボタンが表示される()
    {
        Carbon::setTestNow('2025-12-01 13:00');

        $user = User::factory()->create();
        Attendance::factory()->present()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/breaks/start');
        $this->post('/breaks/end');

        $this->post('/breaks/start');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');
    }

    public function test_休憩時刻が勤怠一覧画面に正しく反映される()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '2025-12-01 09:00:00',
            'status' => 'present',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-12-01 14:00:00',
            'break_end' => '2025-12-01 14:30:00',
        ]);

        $attendance->update(['total_break_time' => '00:30']);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');

        $response->assertDontSee('14:00');
        $response->assertDontSee('14:30');

        $response->assertSee('00:30');
    }
}
