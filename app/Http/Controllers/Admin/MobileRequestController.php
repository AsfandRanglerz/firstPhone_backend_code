<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileRequest;
use App\Models\UserRolePermission;
use App\Models\Vendor;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileRequestController extends Controller
{
    public function index() 
    {
        // $mobilerequests = MobileRequest::with('brand', 'model', 'customer')->latest()->get();
        return view('admin.mobilerequest.index');
    } 

    public function getMobileRequestsData(Request $request)
{
    $sideMenuPermissions = collect();

    if (!Auth::guard('admin')->check()) {
        $user = Auth::guard('subadmin')->user();
        $roleId = $user->roles->first()->id ?? null;

        $permissions = UserRolePermission::with(['permission', 'sideMenue'])
            ->where('role_id', $roleId)
            ->get();

        $sideMenuPermissions = $permissions->groupBy('sideMenue.name')
            ->map(fn($items) => $items->pluck('permission.name'));
    }

    $query = MobileRequest::with(['brand', 'model', 'customer'])->latest();

    return datatables()->of($query)

        ->addIndexColumn()

        ->editColumn('created_at', function ($req) {
            return $req->created_at
                ? $req->created_at->format('d M Y, h:i A')
                : '';
        })

        // 👤 Customer Info
        ->addColumn('customer', function ($req) {
            if (!$req->customer) return '';

            return $req->customer->name . '<br>
                <a href="mailto:' . $req->customer->email . '">' . $req->customer->email . '</a><br>
                <a href="tel:' . $req->customer->phone . '">' . $req->customer->phone . '</a>';
        })

        // 📍 Location
        ->addColumn('location', fn($req) => $req->location ?? '')

        // 📱 Brand / Model
        ->addColumn('brand', fn($req) => $req->brand->name ?? 'N/A')
        ->addColumn('model', fn($req) => $req->model->name ?? 'N/A')

        // 💰 Prices
        ->addColumn('min_price', function ($req) {
            return $req->min_price
                ? number_format($req->min_price, 0)
                : '<span class="text-muted">No Price</span>';
        })

        ->addColumn('max_price', function ($req) {
            return $req->max_price
                ? number_format($req->max_price, 0)
                : '<span class="text-muted">No Price</span>';
        })

        // 📦 Specs
        ->addColumn('ram', fn($req) => $req->ram ?? '')
        ->addColumn('storage', fn($req) => $req->storage ?? '')
        ->addColumn('color', fn($req) => $req->color ?? '')
        ->addColumn('condition', fn($req) => $req->condition ?? '')

        // 📝 Description
        ->addColumn('description', function ($req) {
            return $req->description
                ? \Illuminate\Support\Str::limit($req->description, 50)
                : '<span class="text-muted">No Description</span>';
        })

        // 👀 Vendors Button
        ->addColumn('vendors', function ($req) {
            return '<a class="btn btn-primary"
                href="' . route('mobilerequest.show', $req->id) . '">View</a>';
        })

        // 🚦 Status
        ->addColumn('status', function ($req) {
            if ($req->status == 0) {
                return '<div class="badge badge-success">Seen</div>';
            } elseif ($req->status == 2) {
                return '<div class="badge badge-warning">UnSeen</div>';
            }
            return '';
        })

        // ⚙️ Actions
        ->addColumn('actions', function ($req) use ($sideMenuPermissions) {

            $buttons = '<div class="d-flex gap-1">';

            // DELETE
            if (
                Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Mobile Requests') &&
                $sideMenuPermissions['Mobile Requests']->contains('delete'))
            ) {
                $buttons .= '
                <form id="delete-form-'.$req->id.'"
                    action="'.route('mobilerequest.delete',$req->id).'"
                    method="POST">
                    '.csrf_field().method_field('DELETE').'
                </form>

                <button class="show_confirm btn"
                    style="background-color:#009245"
                    data-form="delete-form-'.$req->id.'">
                    <i class="fa fa-trash"></i>
                </button>';
            }

            // MARK AS READ
            if (
                $req->status == 2 &&
                (
                    Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Mobile Requests') &&
                    $sideMenuPermissions['Mobile Requests']->contains('mark as read'))
                )
            ) {
                $buttons .= '
                <form action="'.route('mobilerequest.markAsRead',$req->id).'"
                    method="POST">
                    '.csrf_field().method_field('PATCH').'
                    <button class="btn btn-warning">
                        <i class="fa fa-eye"></i> Mark as Read
                    </button>
                </form>';
            }

            $buttons .= '</div>';

            return $buttons;
        })

        ->rawColumns([
            'customer','min_price','max_price','description',
            'vendors','status','actions'
        ])

        ->make(true);
}

    public function show($id)
    { 
        $mobilerequests = MobileRequest::with(['brand', 'model'])->findOrFail($id);
        $vendors = VendorMobile::with('vendor')
        ->where('brand_id', $mobilerequests->brand_id)
        ->where('model_id', $mobilerequests->model_id)
        ->where('condition', $mobilerequests->condition)
        ->get()
        ->pluck('vendor')
        ->unique('id') 
        ->values();
        return view('admin.mobilerequest.show', compact('mobilerequests', 'vendors'));
    }

    public function mobileRequestCounter()
    {
        $count = MobileRequest::where('status', 2)->count();
        return response()->json(['count' => $count]);
    }

    public function markAsRead($id)
{
    $mobilerequest = MobileRequest::findOrFail($id);

    if ($mobilerequest->status == 2) {
        $mobilerequest->update(['status' => 0]); 
    }

    return redirect()->back()->with('success', 'Mobile Request Marked As Read');
}

    
    public function delete($id)
    {
        $mobilerequest = MobileRequest::findOrFail($id);
        $mobilerequest->delete();
        return redirect()->route('mobilerequest.index')->with('success', 'Mobile Request Deleted Successfully');
    }
}
