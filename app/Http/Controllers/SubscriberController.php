<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{
    /**
     * Subscribe a new email to the newsletter.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('email'),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));

        // Check if already subscribed
        $existingSubscriber = Subscriber::where('email', $email)->first();

        if ($existingSubscriber) {
            if ($existingSubscriber->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already subscribed.',
                ], 409);
            }

            // Reactivate inactive subscriber
            $existingSubscriber->activate();

            return response()->json([
                'success' => true,
                'message' => 'Welcome back! You have been resubscribed.',
            ]);
        }

        // Create new subscriber
        Subscriber::create([
            'email' => $email,
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing!',
        ]);
    }
}
