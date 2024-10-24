<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// models
use App\Models\User;

// kreait-firebase
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            "firebase_id" => "string|required",
            "device_id" => "string|required"
        ]);

        $firebase_id = $request->firebase_id;
        $device_id = $request->device_id;

        $auth = app('firebase.auth');

        try {
            $verifiedIdToken = $auth->verifyIdToken($firebase_id);
        } catch (FailedToVerifyToken $e) {
            echo 'The token is invalid: ' . $e->getMessage();
        }

        $uid = $verifiedIdToken->claims()->get('sub');

        $firebase_user = $auth->getUser($uid);

        $user = User::where("email", $firebase_user->email)->first();

        if ($user) {
            $token = $user->createToken("user_login")->plainTextToken;
            $type = isset($user->name) ? "old" : "new";
            $user->device_id = $device_id;
            return response([
                "token" => $token,
                "data" => $user,
                "type" => $type
            ], 200);

        } else {
            $type = "new";
            $user = User::create([
                "email" => $firebase_user->email,
                "device_id" => $device_id
            ]);
            $token = $user->createToken("user_login")->plainTextToken;

            return response([
                "token" => $token,
                "data" => $user,
                "type" => $type
            ], 201);

        }









    }

    public function register(Request $request)
    {
        $request->validate([
            "name" => "string|required"
        ]);

        $name = $request->name;

        $user = User::find(auth()->user()->id);

        $user->name = $name;

        return response([
            "message" => "Data Updated Successfully",
            "status" => true
        ], 200);
    }
}
