<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * Upload Image
     * 
     * @param  mixed $request
     * @return void
     */
    public function uploadImage(Request $request)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $image_path = $request->file('image')->store('images');

        return response()->json([
            'status_code' => 200,
            'message' => 'Image uploaded successfully',
            'image_path' => $image_path,
        ]);
    }

    /**
     * Get Accessible Image URL
     * 
     * @param  mixed $image_path
     * @return String $image_url
     */
     public function getAccessibleImageURL($image_path)
     {
         $image_url = asset( $image_path);
 
         return $image_url;
     }

}
