<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        Auth::user()->update([
            'fcm_token' => $request->token
        ]);

        return response()->json([
            'message' => 'FCM token saved'
        ]);
    }
}
