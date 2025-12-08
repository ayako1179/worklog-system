<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Correction;
use App\Models\CorrectionBreak;

class AttendanceUpdateAdminTest extends TestCase
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

    public function test_承認待ちの修正申請が表示される()
    {
        $admin = $this->createAdmin();
        $staff1 = $this->createStaff();
        $staff2 = $this->createStaff();

        $attendance1 = Attendance::factory()->create(['user_id' => $staff1->id]);
        $attendance2 = Attendance::factory()->create(['user_id' => $staff2->id]);

        Correction::factory()->create([
            'user_id' => $staff1->id,
            'attendance_id' => $attendance1->id,
            'approval_status' => 'pending',
            'reason' => '修正理由１',
        ]);

        Correction::factory()->create([
            'user_id' => $staff2->id,
            'attendance_id' => $attendance2->id,
            'approval_status' => 'pending',
            'reason' => '修正理由２',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);
        $response->assertSee('修正理由１');
        $response->assertSee('修正理由２');
        $response->assertSee('承認待ち');
    }

    public function test_承認済みの修正申請が表示される()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create(['user_id' => $staff->id]);

        Correction::factory()->create([
            'user_id' => $staff->id,
            'attendance_id' => $attendance->id,
            'approval_status' => 'approved',
            'reason' => '承認済み理由',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済み理由');
        $response->assertSee('承認済み');
    }

    public function test_修正申請の詳細内容が正しく表示される()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-01',
        ]);

        $correction = Correction::factory()->create([
            'user_id' => $staff->id,
            'attendance_id' => $attendance->id,
            'corrected_start' => '10:00',
            'corrected_end' => '18:00',
            'reason' => '遅延のため修正',
            'approval_status' => 'pending',
        ]);

        CorrectionBreak::factory()->create([
            'correction_id' => $correction->id,
            'break_start' => '13:00',
            'break_end' => '14:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/stamp_correction_request/approve/{$correction->id}");

        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('18:00');
        $response->assertSee('13:00');
        $response->assertSee('14:00');
        $response->assertSee('遅延のため修正');
    }

    public function test_修正申請の承認処理が正しく行われる()
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_start' => '09:00:00',
            'work_end' => '17:00:00',
            'status' => 'finished',
        ]);

        $correction = Correction::factory()->create([
            'user_id' => $staff->id,
            'attendance_id' => $attendance->id,
            'corrected_start' => '10:00',
            'corrected_end' => '18:00',
            'reason' => '修正版備考',
            'approval_status' => 'pending',
        ]);

        CorrectionBreak::factory()->create([
            'correction_id' => $correction->id,
            'break_start' => '13:00',
            'break_end' => '14:00',
        ]);

        $this->actingAs($admin)
            ->post("/stamp_correction_request/approve/{$correction->id}");

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_start' => '10:00:00',
            'work_end' => '18:00:00',
            'note' => '修正版備考',
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('corrections', [
            'id' => $correction->id,
            'approval_status' => 'approved',
        ]);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '13:00:00',
            'break_end' => '14:00:00',
        ]);
    }
}
