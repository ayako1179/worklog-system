<?php

namespace App\Http\Requests\Admin\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'work_start.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_end.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_start.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_end.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format' => '休憩時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $date = $this->work_date;

            $workStart = Carbon::parse("{$date} {$this->work_start}");
            $workEnd = Carbon::parse("{$date} {$this->work_end}");

            if ($workStart->gte($workEnd)) {
                $validator->errors()->add('work_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            foreach ($this->input('breaks', []) as $key => $break) {
                $bs = $break['start'] ?? null;
                $be = $break['end'] ?? null;

                if (!$bs && !$be) continue;

                $bsC = $bs ? Carbon::parse("{$date} {$bs}") : null;
                $beC = $be ? Carbon::parse("{$date} {$be}") : null;

                if ($bsC && ($bsC->lt($workStart) || $bsC->gt($workEnd))) {
                    $validator->errors()->add("breaks.$key.start", '休憩時間が不適切な値です');
                }

                if ($beC && $beC->gt($workEnd)) {
                    $validator->errors()->add("breaks.$key.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
