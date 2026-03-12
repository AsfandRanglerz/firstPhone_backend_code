<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\MobileListing;
use App\Models\MobileRequest;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandsController extends Controller
{
    //

    public function index()   {
       $brands = Brand::orderBy('id', 'desc')->get();
       $brandUsedInVendorMobiles = VendorMobile::pluck('brand_id')->toArray();
       
        return view('admin.brands.index', compact('brands', 'brandUsedInVendorMobiles'));
    }


public function store(Request $request)
{
    $request->validate([
        'name' => 'required|array',
        'name.*' => 'required|string|unique:brands,name',
        'slug' => 'nullable|array',
    ]);

    $brands = [];
    $errors = [];

    foreach ($request->name as $index => $name) {
        try {
            $brands[] = Brand::create([
                'name' => $name,
                'slug' => $request->slug[$index] ?? \Str::slug($name),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation specifically
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errors["name.$index"] = ["The brand name '$name' has already been taken"];
                continue;
            }
            throw $e;
        }
    }

    if (!empty($errors)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Some brands could not be created',
            'errors' => $errors
        ], 422);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Brand created successfully',
        'data' => $brands
    ]);
}

public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|unique:brands,name,' . $id,
        'slug' => 'nullable|string',
    ]);

    $brand = Brand::findOrFail($id);

    $brand->update([
        'name' => $request->name,
        'slug' => $request->slug ?? \Str::slug($request->name),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Brand updated successfully',
        'data' => $brand
    ]);
}

public function delete($id) {
    $find = Brand::find($id);

    $find->delete();

    return redirect()->route('brands.index')->with(['success' => 'Brand deleted successfully']);
    
}


}