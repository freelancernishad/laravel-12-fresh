<?php

use Illuminate\Support\Facades\Route;

// Load SystemSettingsRoutes
if (file_exists($SystemSettingsRoutes = __DIR__.'/Common/SystemSettingsRoutes.php')) {
    require $SystemSettingsRoutes;
}

// Load AllowedOriginRoutes
if (file_exists($AllowedOriginRoutes = __DIR__.'/Common/AllowedOriginRoutes.php')) {
    require $AllowedOriginRoutes;
}

// Load UserAuthRoutes
if (file_exists($UserAuthRoutes = __DIR__.'/Users/Auth/UserAuthRoutes.php')) {
    require $UserAuthRoutes;
}

// Load AdminAuthRoutes
if (file_exists($AdminAuthRoutes = __DIR__.'/Admins/Auth/AdminAuthRoutes.php')) {
    require $AdminAuthRoutes;
}

// Load AdminPlanRoutes
if (file_exists($AdminPlanRoutes = __DIR__.'/Admins/Plans/PlanRoutes.php')) {
    require $AdminPlanRoutes;
}


// Load AdminFeatureRoutes
if (file_exists($AdminFeatureRoutes = __DIR__.'/Admins/Plans/FeatureRoutes.php')) {
    require $AdminFeatureRoutes;
}

// Load AdminCouponRoutes
if (file_exists($AdminCouponRoutes = __DIR__.'/Admins/Coupon/CouponRoutes.php')) {
    require $AdminCouponRoutes;
}


// Load NotificationRoutes
if (file_exists($NotificationRoutes = __DIR__.'/Common/NotificationRoutes.php')) {
    require $NotificationRoutes;
}


// Load AdminSupportTickets
if (file_exists($AdminSupportTickets = __DIR__.'/Common/SupportAndConnect/Ticket/Admin/AdminTicketRoutes.php')) {
    require $AdminSupportTickets;
}


// Load UserSupportTickets
if (file_exists($UserSupportTickets = __DIR__.'/Common/SupportAndConnect/Ticket/User/UserTicketRoutes.php')) {
    require $UserSupportTickets;
}


// Load ContactRoutes
if (file_exists($ContactRoutes = __DIR__.'/Common/SupportAndConnect/Contact/ContactRoutes.php')) {
    require $ContactRoutes;
}


// Load MediaRoutes
if (file_exists($MediaRoutes = __DIR__.'/Common/Media/MediaRoutes.php')) {
    require $MediaRoutes;
}


// Load AdminUserRoutes
if (file_exists($AdminUserRoutes = __DIR__.'/Admins/UserManagement/UserManagementRoutes.php')) {
    require $AdminUserRoutes;
}

// Load TwilioRoutes
if (file_exists($TwilioRoutes = __DIR__.'/Common/Twilio/TwilioRoutes.php')) {
    require $TwilioRoutes;
}

