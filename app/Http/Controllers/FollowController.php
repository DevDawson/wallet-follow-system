<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    // Onyesha watumiaji wote na status ya kufuatana
    public function index()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('follow.index', compact('users'));
    }

    // Fuata mtumiaji
    public function follow(User $user)
    {
        if (Auth::user()->isFollowing($user)) {
            return back()->with('error', 'Tayari unamfuata mtumiaji huyu.');
        }

        Auth::user()->following()->attach($user->id);

        return back()->with('success', 'Sasa unamfuata ' . $user->name);
    }

    // Acha kumfuata
    public function unfollow(User $user)
    {
        if (!Auth::user()->isFollowing($user)) {
            return back()->with('error', 'Humfuati mtumiaji huyu.');
        }

        Auth::user()->following()->detach($user->id);

        return back()->with('success', 'Umemuachia kumfuata ' . $user->name);
    }

    // Onyesha followers
    public function followers()
    {
        $followers = Auth::user()->followers;
        return view('follow.followers', compact('followers'));
    }

    // Onyesha following
    public function following()
    {
        $following = Auth::user()->following;
        return view('follow.following', compact('following'));
    }
}