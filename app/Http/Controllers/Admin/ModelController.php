<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\MobileListing;
use App\Models\MobileModel;
use App\Models\MobileRequest;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModelController extends Controller
{
   public function index($id)
{
   $brand = Brand::with(['mobileModels' => function($query) {
    $query->orderBy('id', 'desc'); 
    }])->findOrFail($id);
    $models = $brand->mobileModels;
    $modelUsedInRequests = MobileRequest::pluck('model_id')->toArray();
    $modelUsedInListings = MobileListing::pluck('model')->toArray(); 
    $modelUsedInVendorMobiles = VendorMobile::pluck('model_id')->toArray();
    
    return view('admin.brands.models', compact('models' , 'brand', 'modelUsedInRequests', 'modelUsedInListings', 'modelUsedInVendorMobiles'));
}

 public function store(Request $request)
{
    $request->validate([
        'brand_id' => 'required|exists:brands,id', // ✅ brand exist hona chahiye
        'name'     => 'required|array',
        'name.*'   => 'required|string|distinct|unique:models,name',
    ]);

    $createdModels = [];

    foreach ($request->name as $name) {
        $createdModels[] = MobileModel::create([
            'brand_id' => $request->brand_id, // ✅ brand id store correctly
            'name'     => $name,
        ]);
    }

    return response()->json([
        'message' => 'Models Created Successfully',
        'data'    => $createdModels
    ]);
}



    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:models,name,' . $id,
        ]);

        $model = MobileModel::findOrFail($id);
        $model->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Model Updated Successfully',
            'data' => $model,
        ]);
    }

    public function destroy($id)
    {
        $model = MobileModel::findOrFail($id);
        $model->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Model Deleted Successfully',
        ]);
    }
}