<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Events\RiderLocationUpdated;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ১. সাধারণ লগিন রাউট (টেস্টিং এর জন্য)
Route::post('/login', function (Request $request) {
    // ইনপুট ভ্যালিডেশন (Optional but good practice)
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // টোকেন তৈরি করা হচ্ছে
    return response()->json([
        'token' => $user->createToken('rider-token')->plainTextToken,
        'user' => $user
    ]);
});


// ২. লোকেশন আপডেট রাউট
Route::middleware('auth:sanctum')->post('/update-location', function (Request $request) {
    // ভ্যালিডেশন: অবশ্যই নম্বর হতে হবে
    $request->validate([
        'lat' => 'required|numeric',
        'lng' => 'required|numeric',
    ]);

    $user = $request->user();

    // ১. ডাটাবেস আপডেট
    $user->update([
        'latitude' => $request->lat,
        'longitude' => $request->lng,
    ]);

    // ২. [গুরুত্বপূর্ণ] মেমোরি রিফ্রেশ করা (যাতে লেটেস্ট ডাটা পায়)
    // এটি না করলে ইভেন্টে পুরানো বা NULL ডাটা চলে যায়
    $user->refresh(); 

    // ৩. ইভেন্ট পাঠানো
    event(new RiderLocationUpdated($user));

    return response()->json(['message' => 'Location Updated']);
});