<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->time('work_start');
            $table->time('work_end')->nullable();
            $table->string('status', 20);
            $table->time('total_break_time')->nullable();
            $table->time('total_work_time')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // 複合ユニーク制約：同じユーザーが同じ日に複数の出勤記録を持てない
            $table->unique(['user_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
