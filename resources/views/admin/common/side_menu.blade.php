<div class="main-sidebar sidebar-style-2">
    <aside" id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ url('/admin/dashboard') }}">
                <img alt="image" src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" class="header-logo"
                    style="width: 200px; height: auto; margin-top:20px; margin-bottom: 3px;" />
                {{-- <span class="logo-name">Crop Secure</span> --}}
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li class="dropdown {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <a href="{{ url('/admin/dashboard') }}" class="nav-link"><i
                        data-feather="home"></i><span>Dashboard</span></a>
            </li>



            {{--  Roles --}}
            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('view')))
                <li class="dropdown {{ request()->is('admin/roles*') ? 'active' : '' }}">
                    <a href="{{ url('admin/roles') }}" class="nav-link"><i
                            data-feather="user"></i><span>Roles</span></a>
                </li>
            @endif



            {{--  SubAdmin --}}
            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Sub Admins') && $sideMenuPermissions['Sub Admins']->contains('view')))
                <li class="dropdown {{ request()->is('admin/subadmin*') ? 'active' : '' }}">
                    <a href="{{ url('admin/subadmin') }}" class="nav-link"><i data-feather="user"></i><span>Sub
                            Admins</span></a>
                </li>
            @endif

            {{--  Users --}}
            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Users') && $sideMenuPermissions['Users']->contains('view')))
                <li class="dropdown">
                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i data-feather="layout"></i> <!-- Icon for header section -->
                        <span>Users</span>
                    </a>
                    <ul
                        class="dropdown-menu {{ request()->is('admin/user*') || request()->is('admin/vendor*') ? 'show' : '' }}">
                        <li class="dropdown {{ request()->is('admin/user*') ? 'active' : '' }}">
                            <a href="{{ url('admin/user') }}" class="nav-link">
                                <i data-feather="users"></i>
                                <span>Customers</span>
                            </a>
                        </li>
            @endif
            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Vendors') && $sideMenuPermissions['Vendors']->contains('view')))
                <li class="dropdown {{ request()->is('admin/vendor*') ? 'active' : '' }}">
                    <a href="{{ url('admin/vendor') }}" class="nav-link">
                        <i data-feather="users"></i>
                        <span>Vendors</span>
                         <div id="vendorpendingCounter"
                        class="badge {{ request()->is('admin/vendor*') ? 'bg-white text-dark' : 'bg-primary text-white' }} rounded-circle"
                        style="display: inline-flex; justify-content: center; align-items: center; 
                            min-width: 22px; height: 22px; border-radius: 50%; 
                            text-align: center; font-size: 12px; margin-left: 5px; padding: 3px;">
                        0
                    </div>
                    </a>
                </li>
            @endif
        </ul>

        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Brands') && $sideMenuPermissions['Brands']->contains('view')))
            <li class="dropdown {{ request()->is('admin/brands*') ? 'active' : '' }}">
                <a href="
                {{ route('brands.index') }}
                " class="nav-link">
                    <i data-feather="layers"></i><span>Brands</span>
                </a>
            </li>
        @endif

        {{-- Subscription Plans --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Subscription Plans') &&
                    $sideMenuPermissions['Subscription Plans']->contains('view')))
            <li class="dropdown {{ request()->is('admin/subscriptions*') ? 'active' : '' }}">
                <a href="
                 {{ route('subscription.index') }}
                 " class="nav-link">
                    <i data-feather="package"></i><span>Subscription Plans</span>
                </a>
            </li>
        @endif

        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('MobileListing') && $sideMenuPermissions['MobileListing']->contains('view')))
            <li class="dropdown {{ request()->is('admin/mobilelisting*') ? 'active' : '' }}">
                <a href="{{ url('admin/mobilelisting') }}" class="nav-link">
                    <i data-feather="smartphone"></i>
                    <span>Customer Mobiles</span>
                    <div id="updatemobilelistingCounter"
                        class="badge {{ request()->is('admin/mobilelisting*') ? 'bg-white text-dark' : 'bg-primary text-white' }} rounded-circle"
                        style="display: inline-flex; justify-content: center; align-items: center; 
                            min-width: 22px; height: 22px; border-radius: 50%; 
                            text-align: center; font-size: 12px; margin-left: 5px; padding: 3px;">
                        0
                    </div>
                </a>
            </li>
        @endif

        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('VendorMobile') && $sideMenuPermissions['VendorMobile']->contains('view')))
            <li class="dropdown {{ request()->is('admin/listingvendor*') ? 'active' : '' }}">
                <a href="{{ url('admin/listingvendor') }}" class="nav-link">
                    <i data-feather="smartphone"></i>
                    <span>Vendor Mobiles</span>
                </a>
            </li>
        @endif

        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('MobileRequest') && $sideMenuPermissions['MobileRequest']->contains('view')))
            <li class="dropdown {{ request()->is('admin/mobilerequest*') ? 'active' : '' }}">
                <a href="{{ url('admin/mobilerequest') }}" class="nav-link">
                    <i data-feather="smartphone"></i>
                    <span>Mobile Requests</span>
                    <div id="updatemobilerequestCounter"
                        class="badge {{ request()->is('admin/mobilerequest*') ? 'bg-white text-dark' : 'bg-primary text-white' }} rounded-circle"
                        style="display: inline-flex; justify-content: center; align-items: center; 
                             min-width: 22px; height: 22px; border-radius: 50%; 
                             text-align: center; font-size: 12px; margin-left: 5px; padding: 3px;">
                        0
                    </div>
                </a>
            </li>
        @endif

        {{-- Orders --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Orders') && $sideMenuPermissions['Orders']->contains('view')))
            <li class="dropdown {{ request()->is('admin/orders*') ? 'active' : '' }}">
                <a href="{{ route('order.index') }}" class="nav-link">
                    <i data-feather="shopping-cart"></i>
                    <span>Orders</span>
                    <div id="updateOrdersCounter"
                        class="badge {{ request()->is('admin/orders*') ? 'bg-white text-dark' : 'bg-primary text-white' }} rounded-circle"
                        style="display: inline-flex; justify-content: center; align-items: center; 
                     min-width: 22px; height: 22px; border-radius: 50%; 
                     text-align: center; font-size: 12px; margin-left: 5px; padding: 3px;">
                        0
                    </div>
                </a>
            </li>
        @endif

        {{-- Cancel Orders --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Cancel Orders') && $sideMenuPermissions['Cancel Orders']->contains('view')))
            <li class="dropdown {{ request()->is('admin/cancel-orders*') ? 'active' : '' }}">
                <a href="{{ route('cancel-order.index') }}" class="nav-link">
                    <i data-feather="x-circle"></i>
                    <span>Cancel Orders</span>
                    <div id="updateCancelOrdersCounter"
                        class="badge {{ request()->is('admin/cancel-orders*') ? 'bg-white text-dark' : 'bg-primary text-white' }} rounded-circle"
                        style="display: inline-flex; justify-content: center; align-items: center; 
                min-width: 22px; height: 22px; border-radius: 50%; 
                text-align: center; font-size: 12px; margin-left: 5px; padding: 3px;">
                        0
                    </div>
                </a>
            </li>
        @endif

         {{-- Sales Reporting --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Sales Report') && $sideMenuPermissions['Sales Report']->contains('view')))
            <li class="dropdown {{ request()->is('admin/reports*') ? 'active' : '' }}">
                <a href="{{ route('reports.index') }}" class="nav-link">
                    <i data-feather="bar-chart-2"></i>
                    <span>Sales Reporting</span>
                </a>
            </li>
        @endif


        {{-- Notification --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('view')))
            <li class="dropdown {{ request()->is('admin/notification*') ? 'active' : '' }}">
                <a href="
                {{ route('notification.index') }}
                " class="nav-link">
                    <i data-feather="bell"></i><span>Notifications</span>
                </a>
            </li>
        @endif

        {{--  SEO --}}
        {{-- @if (Auth::guard('admin')->check() || ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['seo']->contains('seo')))
                
                <li class="dropdown {{ request()->is('admin/seo*') ? 'active' : '' }}">
                    <a href="{{ url('admin/seo') }}" class="nav-link"><i
                            data-feather="trending-up"></i><span>SEO</span></a>
                </li>
            @endif --}}


        {{--  FAQS --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Faqs') && $sideMenuPermissions['Faqs']->contains('view')))
            <li class="dropdown {{ request()->is('admin/faq*') ? 'active' : '' }}">
                <a href="{{ url('admin/faq') }}" class="nav-link">
                    <i data-feather="settings"></i>
                    <span>FAQ's</span>
                </a>
            </li>
        @endif


        {{-- Contact Us  --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Contact us') && $sideMenuPermissions['Contact us']->contains('view')))
            {{-- Contact Us --}}
            <li class="dropdown {{ request()->is('admin/admin/contact-us*') ? 'active' : '' }}">
                <a href="{{ url('admin/admin/contact-us') }}" class="nav-link"><i data-feather="mail"></i><span>Contact
                        Us</span></a>
            </li>
        @endif


        {{--  About Us --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('About us') && $sideMenuPermissions['About us']->contains('view')))
            <li class="dropdown {{ request()->is('admin/about-us*') ? 'active' : '' }}">
                <a href="{{ url('admin/about-us') }}" class="nav-link"><i data-feather="help-circle"></i><span>About
                        Us</span></a>
            </li>
        @endif


        {{--  Privacy Policy --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Privacy & Policy') && $sideMenuPermissions['Privacy & Policy']->contains('view')))
            <li class="dropdown {{ request()->is('admin/privacy-policy*') ? 'active' : '' }}">
                <a href="{{ url('admin/privacy-policy') }}" class="nav-link"><i data-feather="shield"></i><span>Privacy Policy</span></a>
            </li>
        @endif

        {{--  Terms & Conditions --}}
        @if (Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Terms & Conditions') &&
                    $sideMenuPermissions['Terms & Conditions']->contains('view')))
            <li class="dropdown {{ request()->is('admin/term-condition*') ? 'active' : '' }}">
                <a href="{{ url('admin/term-condition') }}" class="nav-link"><i
                        data-feather="file-text"></i><span>Terms
                        & Conditions</span></a>
            </li>
        @endif



        </ul>
        </aside>
</div>
