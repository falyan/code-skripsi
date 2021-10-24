<?php

namespace App\Http\Services\Profile;

use App\Http\Services\Service;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileCommands extends Service
{
    public function changePassword($request)
    {
        $user = Auth::user();
        if (empty($user)) {
            throw new Exception('ID user tidak valid.', 400);
        }
        
        $new_password = Hash::make($request->password);
        if (Hash::check($request->old_password, $user->password)) {
            User::where('id', Auth::id())->update(['password' => $new_password]);
        }else {
            throw new Exception('Kata sandi lama tidak sesuai.', 400);
        }
    }
}
