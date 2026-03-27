<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserRolePermission;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorMobileListingController extends Controller
{
     public function index()
    {
        // $mobiles = VendorMobile::with('model','brand', 'vendor')->latest()->get();
        return view('admin.vendormobilelisting.index');
    }

    public function getVendorMobileData(Request $request)
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

    $query = VendorMobile::with(['model','brand','vendor'])->latest();

    return datatables()->of($query)

        ->addIndexColumn()

            ->filterColumn('vendor_info', function($query, $keyword) {
        $query->whereHas('vendor', function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('phone', 'like', "%{$keyword}%");
        });
    })

    ->filterColumn('brand', function($query, $keyword) {
    $query->whereHas('brand', function($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
    });
})

        ->filterColumn('model', function($query, $keyword) {
    $query->whereHas('model', function($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
    });
})

        ->filterColumn('created_at', function($query, $keyword) {
        $query->whereRaw(
            "DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') LIKE ?",
            ["%{$keyword}%"]
        );
    })

    ->filterColumn('ram', function($query, $keyword) {
        $query->where('ram', 'like', "%{$keyword}%");
    })

    ->filterColumn('storage', function($query, $keyword) {
        $query->where('storage', 'like', "%{$keyword}%");
    })

    ->filterColumn('price', function($query, $keyword) {
        $query->where('price', 'like', "%{$keyword}%");
    })

    ->filterColumn('condition', function($query, $keyword) {
        $query->where('condition', 'like', "%{$keyword}%");
    })

    ->filterColumn('color', function($query, $keyword) {
        $query->where('color', 'like', "%{$keyword}%");
    })

    ->filterColumn('processor', function($query, $keyword) {
        $query->where('processor', 'like', "%{$keyword}%");
    })

    ->filterColumn('display', function($query, $keyword) {
        $query->where('display', 'like', "%{$keyword}%");
    })

    ->filterColumn('charging', function($query, $keyword) {
        $query->where('charging', 'like', "%{$keyword}%");
    })

    ->filterColumn('refresh_rate', function($query, $keyword) {
        $query->where('refresh_rate', 'like', "%{$keyword}%");
    })

    ->filterColumn('main_camera', function($query, $keyword) {
        $query->where('main_camera', 'like', "%{$keyword}%");
    })

    ->filterColumn('main_camera', function($query, $keyword) {
        $query->where('main_camera', 'like', "%{$keyword}%");
    })

    ->filterColumn('ultra_camera', function($query, $keyword) {
        $query->where('ultra_camera', 'like', "%{$keyword}%");
    })

    ->filterColumn('telephoto_camera', function($query, $keyword) {
        $query->where('telephoto_camera', 'like', "%{$keyword}%");
    })

    ->filterColumn('front_camera', function($query, $keyword) {
        $query->where('front_camera', 'like', "%{$keyword}%");
    })

    ->filterColumn('build', function($query, $keyword) {
        $query->where('build', 'like', "%{$keyword}%");
    })

    ->filterColumn('stock', function($query, $keyword) {
        $query->where('stock', 'like', "%{$keyword}%");
    })

    ->filterColumn('ai_features', function($query, $keyword) {
        $query->where('ai_features', 'like', "%{$keyword}%");
    })

    ->filterColumn('battery_health', function($query, $keyword) {
        $query->where('battery_health', 'like', "%{$keyword}%");
    })

    ->filterColumn('os_version', function($query, $keyword) {
        $query->where('os_version', 'like', "%{$keyword}%");
    })

    ->filterColumn('warranty_start', function($query, $keyword) {
        $query->where('warranty_start', 'like', "%{$keyword}%");
    })

    ->filterColumn('warranty_end', function($query, $keyword) {
        $query->where('warranty_end', 'like', "%{$keyword}%");
    })

    ->filterColumn('about', function($query, $keyword) {
        $query->where('about', 'like', "%{$keyword}%");
    })

    ->filterColumn('pta_approved', function($query, $keyword) {
    $keyword = strtolower($keyword);

    $query->where(function($q) use ($keyword) {
        if (str_contains($keyword, 'approved')) {
            $q->orWhere('pta_approved', 0);
        }
        if (str_contains($keyword, 'not')) {
            $q->orWhere('pta_approved', 1);
        }
    });
})
    

        ->editColumn('created_at', fn($m) => $m->created_at?->format('d M Y, h:i A'))

        // ✅ Vendor Info (Name + Email + Phone)
        ->addColumn('vendor_info', function ($m) {
            $name = $m->vendor->name ?? '';
            $email = $m->vendor
                ? '<a href="mailto:'.$m->vendor->email.'">'.$m->vendor->email.'</a>' : '';
            $phone = $m->vendor
                ? '<a href="tel:'.$m->vendor->phone.'">'.$m->vendor->phone.'</a>' : '';

            return $name.'<br>'.$email.'<br>'.$phone;
        })

        ->addColumn('brand', fn($m) => $m->brand->name ?? '<span class="text-muted">No Brand</span>')
        ->addColumn('model', fn($m) => $m->model->name ?? '<span class="text-muted">No Model</span>')

        ->addColumn('ram', fn($m) => $m->ram ?? '<span class="text-muted">No RAM</span>')
        ->addColumn('storage', fn($m) => $m->storage ?? '<span class="text-muted">No ROM</span>')
        ->addColumn('price', fn($m) => $m->price ?? '<span class="text-muted">No Price</span>')
        ->addColumn('condition', fn($m) => $m->condition ?? '<span class="text-muted">No Condition</span>')
        ->addColumn('color', fn($m) => $m->color ?? '<span class="text-muted">No Color</span>')
        ->addColumn('processor', fn($m) => $m->processor ?? '<span class="text-muted">No Processor</span>')
        ->addColumn('display', fn($m) => $m->display ?? '<span class="text-muted">No Display</span>')
        ->addColumn('charging', fn($m) => $m->charging ?? '<span class="text-muted">No Charging</span>')
        ->addColumn('refresh_rate', fn($m) => $m->refresh_rate ?? '<span class="text-muted">No Refresh Rate</span>')
        ->addColumn('main_camera', fn($m) => $m->main_camera ?? '<span class="text-muted">No Main Camera</span>')
        ->addColumn('ultra_camera', fn($m) => $m->ultra_camera ?? '<span class="text-muted">No Ultra Camera</span>')
        ->addColumn('telephoto_camera', fn($m) => $m->telephoto_camera ?? '<span class="text-muted">No Telephoto</span>')
        ->addColumn('front_camera', fn($m) => $m->front_camera ?? '<span class="text-muted">No Front Camera</span>')
        ->addColumn('build', fn($m) => $m->build ?? '<span class="text-muted">No Build</span>')
        ->addColumn('stock', fn($m) => $m->stock ?? '<span class="text-muted">No Stock</span>')

        ->addColumn('pta', fn($m) => $m->pta_approved == 0 ? 'Approved' : 'Not Approved')

        ->addColumn('ai_features', fn($m) => $m->ai_features ?? '<span class="text-muted">No AI Features</span>')
        ->addColumn('battery_health', fn($m) => $m->battery_health ?? '<span class="text-muted">No Battery</span>')
        ->addColumn('os_version', fn($m) => $m->os_version ?? '<span class="text-muted">No OS</span>')
        ->addColumn('warranty_start', fn($m) => $m->warranty_start ?? '<span class="text-muted">No Start</span>')
        ->addColumn('warranty_end', fn($m) => $m->warranty_end ?? '<span class="text-muted">No End</span>')

        ->addColumn('about', fn($m) => $m->about ?? '')

        // ✅ VIEW BUTTON
        ->addColumn('view', function ($m) {
            return '<a class="btn btn-primary" href="'.route('vendormobile.show', $m->id).'">View</a>';
        })

        // ✅ DELETE
        ->addColumn('actions', function ($m) use ($sideMenuPermissions) {

            $btn = '';

            if (
                Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Vendor Mobiles') &&
                $sideMenuPermissions['Vendor Mobiles']->contains('delete'))
            ) {
                $btn .= '
                <form id="delete-form-'.$m->id.'" 
                    action="'.route('vendormobile.delete',$m->id).'" 
                    method="POST">
                    '.csrf_field().'
                    '.method_field('DELETE').'
                </form>

                <button class="show_confirm btn" 
                    style="background:#009245;"
                    data-form="delete-form-'.$m->id.'">
                    <i class="fa fa-trash"></i>
                </button>';
            }

            return $btn;
        })

        ->rawColumns([
            'vendor_info','brand','model','ram','storage','price','condition',
            'color','processor','display','charging','refresh_rate',
            'main_camera','ultra_camera','telephoto_camera','front_camera',
            'build','stock','ai_features','battery_health','os_version',
            'warranty_start','warranty_end','view','actions'
        ])

        ->make(true);
}

   public function show($id)
{
    $mobiles = collect([VendorMobile::findOrFail($id)]);
    return view('admin.vendormobilelisting.show', compact('mobiles'));
}

    public function delete($id)
{
    $mobile = VendorMobile::findOrFail($id); 
    $mobile->delete();
    return redirect()->route('vendormobile.index')->with('success', 'Vendor Mobile Deleted Successfully');
}

    
}
