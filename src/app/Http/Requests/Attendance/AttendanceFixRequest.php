<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceFixRequest extends FormRequest
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
            'breaks.*.start.date_format' => '休憩時間が勤務時間外です',
            'breaks.*.end.date_format' => '休憩時間が勤務時間外です',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $start = $this->work_start;
            $end = $this->work_end;

            if (strtotime($start) >= strtotime($end)) {
                $validator->errors()->add('work_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $date = $this->route('date') ?? $this->work_date;
            $workStartCarbon = Carbon::parse("$date $start");
            $workEndCarbon = Carbon::parse("$date $end");

            foreach ($this->breaks ?? [] as $key => $break) {
                $bs = $break['start'] ?? null;
                $be = $break['end'] ?? null;

                if (!$bs && !$be) continue;

                $bsC = $bs ? Carbon::parse("$date $bs") : null;
                $beC = $be ? Carbon::parse("$date $be") : null;

                if (($bsC && $bsC->lt($workStartCarbon)) ||
                    ($beC && $beC->gt($workEndCarbon))
                ) {
                    $validator->errors()->add(
                        "breaks.$key.start",
                        '休憩時間が勤務時間外です'
                    );
                }
            }
        });
    }
}
