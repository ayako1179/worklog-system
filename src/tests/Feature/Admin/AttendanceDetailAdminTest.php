<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceDetailAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createStaff()
    {
        return User::factory()->create([
            'role' => 'staff',
        ]);
    }

    public function test_管理者は勤怠詳細画面で選択した勤怠データを確認できる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-01',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'note' => 'サンプル備考',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('サンプル備考');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_出勤時間が退勤時間より後ならエラーになる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-01',
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", [
                'work_date' => '2025-12-01',
                'work_start' => '18:00',
                'work_end' => '09:00',
                'note' => 'test',
            ]);

        $response->assertSessionHasErrors('work_time');

        $response = $this->followingRedirects()->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $response->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    public function test_休憩開始が退勤より後ならエラーになる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-01',
            'work_start' => '09:00:00',
            'work_end' => '12:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '10:00:00',
            'break_end' => '11:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", [
                'work_date' => '2025-12-01',
                'work_start' => '09:00',
                'work_end' => '12:00',
                'breaks' => [
                    ['start' => '12:30', 'end' => '13:00'],
                ],
                'note' => 'test',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了が退勤より後ならエラーになる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-01',
            'work_start' => '09:00:00',
            'work_end' => '12:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '10:00:00',
            'break_end' => '11:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", [
                'work_date' => '2025-12-01',
                'work_start' => '09:00',
                'work_end' => '12:00',
                'breaks' => [
                    ['start' => '10:00', 'end' => '13:00'],
                ],
                'note' => 'test',
            ]);

        $response->assertSessionHasErrors('breaks.0.end');
    }

    public function test_備考が未入力ならエラーになる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-01',
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", [
                'work_date' => '2025-12-01',
                'work_start' => '09:00',
                'work_end' => '18:00',
                'note' => '',
            ]);

        $response->assertSessionHasErrors('note');

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $response->assertSee('備考を記入してください');
    }
}
