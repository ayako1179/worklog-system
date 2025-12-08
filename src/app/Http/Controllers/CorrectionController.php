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

        if ($user->role === 'admin') {
            $corrections = Correction::with(['attendance', 'user'])
                ->where('approval_status', $tab)
                ->orderBy('created_at', 'desc')
                ->get();

            return view('admin.corrections.index', compact('corrections', 'tab'));
        }

        $corrections = Correction::with(['attendance', 'user'])
            ->where('user_id', $user->id)
            ->where('approval_status', $tab)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('corrections.index', compact('corrections', 'tab'));
    }
}
