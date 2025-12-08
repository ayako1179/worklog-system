<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;

class UserInfoAdminTest extends TestCase
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

    public function test_管理者はスタッフ一覧で全スタッフの氏名メールアドレスを確認できる()
    {
        $admin = $this->createAdmin();

        $staff1 = $this->createStaff();
        $staff2 = $this->createStaff();

        $response = $this->actingAs($admin)
            ->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee($staff1->email);
        $response->assertSee($staff2->name);
        $response->assertSee($staff2->email);
    }

    public function test_管理者はスタッフの勤怠一覧を正しく確認できる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-11-10',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_work_time' => '08:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$staff->id}?month=2025-11");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');
    }

    public function test_前月ボタンで前月の勤怠が表示される()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-10-15',
            'work_start' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$staff->id}?month=2025-11");

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$staff->id}?month=2025-10");

        $response->assertStatus(200);
        $response->assertSee('2025/10');
        $response->assertSee('09:00');
    }

    public function test_翌月ボタンで翌月の勤怠が表示される()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-10',
            'work_start' => '10:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$staff->id}?month=2025-12");

        $response->assertStatus(200);
        $response->assertSee('2025/12');
        $response->assertSee('10:00');
    }

    public function test_勤怠一覧から勤怠詳細画面に遷移できる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-11-05',
            'work_start' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$staff->id}?month=2025-11");

        $response->assertSee("/admin/attendance/{$attendance->id}");

        $detail = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $detail->assertStatus(200);
        $detail->assertSee('09:00');
    }
}
