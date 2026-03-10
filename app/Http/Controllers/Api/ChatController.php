<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function chatImages(Request $request)
{
    try {

         $request->validate([
            'image' => 'required|max:2048',
        ],
        [
            'image.max' => 'The image size should not exceed 2MB'
        ]
        );

        $uploadedImages = [];

        $files = $request->file('image');

        // convert single image to array
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {

            $filename = uniqid().'_profile.'.$file->getClientOriginalExtension();

            $file->move(public_path('admin/assets/chatimages/'), $filename);

            $imagePath = 'public/admin/assets/chatimages/' . $filename;

             $chat = Chat::create([
                'image' => $imagePath
            ]);

            $uploadedImages[] = asset($imagePath);
        }

        return response()->json([
            'images' => $uploadedImages
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'error' => $e->validator->errors()->first()
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 400);
    }
}
}
