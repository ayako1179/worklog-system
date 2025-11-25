<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'staff')
                    ->orderBy('name')
                    ->get();
                    
        return view('admin.staff.list', compact('users'));
    }
}
