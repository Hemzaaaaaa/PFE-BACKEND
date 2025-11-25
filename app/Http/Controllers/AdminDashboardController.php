<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Car;
use App\Models\Reservation;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        // 📌 User Counts
        $totalUsers     = User::count();
        $totalAdmins    = User::where('role', 'admin')->count();
        $totalStaff     = User::where('role', 'staff')->count();
        $totalCustomers = User::where('role', 'customer')->count();

        // 📌 Cars Count
        $totalCars = Car::count();

        // 📌 Reservation Counts
        $totalReservations   = Reservation::count();
        $pendingReservations = Reservation::where('status', 'pending')->count();
        $acceptedReservations = Reservation::where('status', 'accepted')->count();
        $declinedReservations = Reservation::where('status', 'declined')->count();

        // 📌 Time-based statistics
        $today     = Carbon::today();
        $thisWeek  = Carbon::now()->subDays(7);
        $thisMonth = Carbon::now()->startOfMonth();

        $todayReservations     = Reservation::whereDate('created_at', $today)->count();
        $weeklyReservations    = Reservation::where('created_at', '>=', $thisWeek)->count();
        $monthlyReservations   = Reservation::where('created_at', '>=', $thisMonth)->count();

        // OPTIONAL: Revenue approximation (only accepted reservations)
        $estimatedRevenue = Reservation::where('status', 'accepted')->sum('price_estimate');

        return response()->json([
            'users' => [
                'total'     => $totalUsers,
                'admins'    => $totalAdmins,
                'staff'     => $totalStaff,
                'customers' => $totalCustomers,
            ],

            'cars' => [
                'total' => $totalCars,
            ],

            'reservations' => [
                'total'      => $totalReservations,
                'pending'    => $pendingReservations,
                'accepted'   => $acceptedReservations,
                'declined'   => $declinedReservations,

                'today'      => $todayReservations,
                'this_week'  => $weeklyReservations,
                'this_month' => $monthlyReservations,
            ],

            // OPTIONAL: only if you want revenue tracking
            'estimated_revenue' => $estimatedRevenue ?? 0
        ], 200);
    }
}
