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
            // 出勤・退勤
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i'],

            // 既存休憩
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],

            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            // 出勤・退勤
            'work_start.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_end.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_start.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_end.date_format' => '出勤時間もしくは退勤時間が不適切な値です',

            // 休憩開始・終了（勤務時間外）
            'breaks.*.start.date_format' => '休憩時間が勤務時間外です',
            'breaks.*.end.date_format' => '休憩時間が勤務時間外です',

            // 備考
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $date = $this->work_date;

            $workStart = Carbon::parse("{$date} {$this->work_start}");

            $workEnd = $this->work_end
                ? Carbon::parse("{$date} {$this->work_end}")
                : null;

            // $start = $this->work_start;
            // $end = $this->work_end;

            // $start = substr($this->work_start, 0, 5);
            // $end = substr($this->work_end, 0, 5);

            if ($workEnd && $workStart->gte($workEnd)) {
                $validator->errors()->add('work_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // $date = $this->route('date') ?? $this->work_date;
            // $date = $this->work_date;

            // $workStartCarbon = Carbon::parse("{$date} {$start}");
            // $workEndCarbon = Carbon::parse("{$date} {$end}");

            foreach ($this->breaks ?? [] as $key => $break) {

                $bs = $break['start'] ?? null;
                $be = $break['end'] ?? null;

                if (!$bs && !$be) continue;

                // $bs = $bs ? substr($bs, 0, 5) : null;
                // $be = $be ? substr($be, 0, 5) : null;

                $bsC = $bs ? Carbon::parse("{$date} {$bs}") : null;
                $beC = $be ? Carbon::parse("{$date} {$be}") : null;

                if ($workEnd) {
                    if (($bsC && $bsC->lt($workStart)) || ($beC && $beC->gt($workEnd))) {
                        $validator->errors()->add("breaks.$key.start", '休憩時間が勤務時間外です');
                    }
                }

                // if (($bsC && $bsC->lt($workStartCarbon)) ||
                //     ($beC && $beC->gt($workEndCarbon))
                // ) {

                //     $validator->errors()->add(
                //         "breaks.$key.start",
                //         '休憩時間が勤務時間外です'
                //     );
                // }
            }
        });
    }
}
