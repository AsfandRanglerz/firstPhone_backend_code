<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    
    public function index()
    {
        // Get the first contact record (or all if you want)
        $contact = ContactUs::select('email', 'phone')->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact information not found'
            ], 404);
        }

        return response()->json([
            'data' => $contact
        ], 200);
    }

}
