<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報がUIと同じ形式で出力されている()
    {
        Carbon::setTestNow(Carbon::parse('2025-12-01 12:34:00'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'staff'
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $expectedDate = Carbon::now()->format('Y年n月j日');
        $expectedTime = Carbon::now()->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }
}
