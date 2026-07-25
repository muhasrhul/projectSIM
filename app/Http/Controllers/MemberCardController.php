<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class MemberCardController extends Controller
{
    public function show($token)
    {
        $member = Member::where('card_token', $token)->firstOrFail();
        
        return view('member-card', compact('member'));
    }
}
