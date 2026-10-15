<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreAccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json(['data' => ['name' => $user->name, 'email' => $user->email, 'phone' => $user->customer->phone, 'address' => $user->customer->address]]);
    }

    public function update(StoreProfileRequest $request)
    {
        DB::transaction(function () use ($request) {
            $request->user()->update(['name' => $request->validated('name')]);
            $request->user()->customer->update($request->validated());
        });

        return $this->show($request);
    }
}
