<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function send(Request $request) 
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string'
        ]);
            // send request to Node mailer
        try {
            $response = Http::post('http://localhost:3000/send-email', $validated);
            // check response
            if ($response->successful()) {
                return response()->json([
                    'message' => 'Email sent successfully'
                ]);
            }else {
                return response()->json([
                    'message' => 'Failed to send email',
                    'status' => $response->status(),
                    'body' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error connecting to mailer service',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
