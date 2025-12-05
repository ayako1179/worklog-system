<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use App\Models\CorrectionBreak;
use Carbon\Carbon;
// use Illuminate\Foundation\Testing\WithFaker;

class AttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後ならエラーになる()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post("/attendance/detail/{$attendance->id}", [
                'work_start' => '19:00',
                'work_end' => '18:00',
                'note' => 'test',
            ]);

        $response->assertSessionHasErrors(['work_time']);
    }

    public function test_休憩開始が退勤より後ならエラーになる()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '09:00:00',
            'work_end' => '12:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '10:00:00',
            'break_end' => '11:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post("/attendance/detail/{$attendance->id}", [
                'work_start' => '09:00',
                'work_end' => '12:00',
                'breaks' => [
                    ['start' => '13:00', 'end' => '14:00']
                ],
                'note' => 'test',
            ]);

        $response->assertSessionHasErrors();
        $response->assertSessionHasErrors(['breaks.0.start']);
    }

    public function test_休憩終了が退勤より後ならエラーになる()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'work_start' => '09:00:00',
            'work_end' => '12:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '10:00:00',
            'break_end' => '11:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post("/attendance/detail/{$attendance->id}", [
                'work_start' => '09:00',
                'work_end' => '12:00',
                'breaks' => [
                    ['start' => '11:00', 'end' => '13:00']
                ],
                'note' => 'test',
            ]);

        $response->assertSessionHasErrors(['breaks.0.start']);
    }

    public function test_備考が未入力ならエラーになる()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post("/attendance/detail/{$attendance->id}", [
                'work_start' => '09:00',
                'work_end' => '18:00',
                'note' => '',
            ]);

        $response->assertSessionHasErrors(['note']);
    }

    public function test_修正申請が正常に作成される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        $this->actingAs($user)
            ->post("/attendance/detail/{$attendance->id}", [
                'work_start' => '10:00',
                'work_end' => '19:00',
                'note' => '修正理由',
                'breaks' => [],
            ]);

        $this->assertDatabaseHas('corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'reason' => '修正理由',
            'approval_status' => 'pending',
        ]);
    }

    public function test_承認待ちタブに自分の申請が表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        Correction::created([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'reason' => '修正理由',
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=pending');

        $response->assertSee('承認待ち');
        $response->assertSee('申請はありません');
    }

    public function test_承認済みタブに管理者が承認した申請が表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        Correction::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'reason' => 'OK',
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=approved');

        $response->assertSee('OK');
        $response->assertSee('承認済み');
    }

    public function test_申請一覧の詳細から勤怠詳細画面に遷移できる()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        Correction::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'reason' => 'test',
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=pending');

        $response->assertSee('詳細');

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->id}");

        $response->assertSee('勤怠詳細');
    }
}
