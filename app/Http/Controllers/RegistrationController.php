<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Customer;
use App\Models\User;
use App\Services\AuthCookies;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function __invoke(RegisterRequest $request, AuthCookies $cookies)
    {
        $data = $request->validated();
        $user = DB::transaction(function () use ($data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->account_type = 'customer';
            $user->save();
            $customer = new Customer(['name' => $user->name, 'email' => $user->email]);
            $customer->user_id = $user->id;
            $customer->save();

            return $user;
        });
        $token = Auth::guard('api')->login($user);

        return response()->json(['data' => new UserResource($user), 'expires_in' => (int) config('jwt.ttl') * 60], 201)
            ->withCookie($cookies->jwt($token));
    }
}
