<?php

namespace App\Providers;

use App\Models\Faq;
 use Illuminate\Support\Facades\View;
use App\Models\Role;
use App\Models\User;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\AboutUs;
use App\Models\SubAdmin;
use App\Models\ContactUs;
use App\Models\Notification;
use App\Models\MobileListing;
use App\Models\PrivacyPolicy;
use App\Models\TermCondition;
use App\Observers\ModelObserver;

use App\Models\TermsAndConditions;
use App\Models\UserRolePermission;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use App\Repositories\VendorRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Api\AuthRepository;
use App\Repositories\Api\HomeRepository;

use App\Repositories\Api\OrderRepository;
use App\Repositories\Api\NotificationRepo;
use App\Repositories\NotificationRepository;
use App\Repositories\Api\RequestedMobileRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\VendorRepositoryInterface;
use App\Repositories\Api\Interfaces\AuthRepositoryInterface;
use App\Repositories\Api\Interfaces\HomeRepositoryInterface;
use App\Repositories\Api\Interfaces\OrderRepositoryInterface;
use App\Repositories\Api\Interfaces\NotificationRepoInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Api\Interfaces\RequestedMobileRepositoryInterface;
use App\Repositories\Api\Interfaces\VendorSubscriptionRepositoryInterface;
use App\Repositories\Api\VendorSubscriptionRepository;
use App\Repositories\Interfaces\OrderRepoInterface;
use App\Repositories\Interfaces\SubscriptionPlanInterface;
use App\Repositories\OrderRepo;
use App\Repositories\SubscriptionPlanRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(NotificationRepoInterface::class, NotificationRepo::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(HomeRepositoryInterface::class, HomeRepository::class);
        $this->app->bind(RequestedMobileRepositoryInterface::class, RequestedMobileRepository::class);
        $this->app->bind(OrderRepoInterface::class, OrderRepo::class);
        $this->app->bind(SubscriptionPlanInterface::class, SubscriptionPlanRepository::class);
        $this->app->bind(VendorSubscriptionRepositoryInterface::class, VendorSubscriptionRepository::class);

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */


    public function boot()
    {
        View::composer('*', function ($view) {
            $sideMenuPermissions = collect();

            if (Auth::guard('subadmin')->check()) {
                $user = Auth::guard('subadmin')->user();

                // Load roles from pivot
                $role = $user->roles()->first(); // assumes 1 role per subadmin

                if ($role) {
                    $roleId = $role->id;

                    $sideMenuPermissions = UserRolePermission::with(['permission', 'sideMenue'])
                        ->where('role_id', $roleId)
                        ->get()
                        ->groupBy(function ($item) {
                            return $item->sideMenue->name ?? 'undefined';
                        })
                        ->map(function ($items) {
                            return $items->pluck('permission.name');
                        });
                }
            }

            $view->with('sideMenuPermissions', $sideMenuPermissions);
        });

        view()->composer('*', function ($view) {
            $pendingVendorCount = Vendor::where('status', 'pending')->count();
            $view->with([ 'pendingVendorCount' => $pendingVendorCount ]);
        });
        SubAdmin::observe(ModelObserver::class);
        User::observe(ModelObserver::class);
        Role::observe(ModelObserver::class);
        Faq::observe(ModelObserver::class);
        AboutUs::observe(ModelObserver::class);
        Brand::observe(ModelObserver::class);
        PrivacyPolicy::observe(ModelObserver::class);
        Notification::observe(ModelObserver::class);
        Vendor::observe(ModelObserver::class);
        ContactUs::observe(ModelObserver::class);
        TermCondition::observe(ModelObserver::class);
        MobileListing::observe(ModelObserver::class);

    }
}