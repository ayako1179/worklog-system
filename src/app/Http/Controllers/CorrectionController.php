<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Correction;

class CorrectionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->query('tab', 'pending');

        // 管理者の場合：全スタッフの修正申請を表示
        if ($user->role === 'admin') {

            $corrections = Correction::with(['attendance', 'user'])
                ->where('approval_status', $tab)
                ->orderBy('created_at', 'desc')
                ->get();

            // 管理者専用 Blade を返す
            return view('admin.corrections.index', compact('corrections', 'tab'));
        }

        // 一般ユーザーの場合：自分の申請のみ
        $corrections = Correction::with(['attendance', 'user'])
            ->where('user_id', $user->id)
            ->where('approval_status', $tab)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('corrections.index', compact('corrections', 'tab'));
    }
}
