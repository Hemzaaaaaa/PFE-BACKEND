<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Car;
use App\Models\Reservation;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * MAIN DASHBOARD STATS
     */
    public function stats()
    {
        // USER COUNTS
        $totalUsers     = User::count();
        $totalAdmins    = User::where('role', 'admin')->count();
        $totalStaff     = User::where('role', 'staff')->count();
        $totalCustomers = User::where('role', 'customer')->count();

        // CARS COUNT
        $totalCars = Car::count();

        // RESERVATION COUNTS
        $totalReservations     = Reservation::count();
        $pendingReservations   = Reservation::where('status', 'pending')->count();
        $acceptedReservations  = Reservation::where('status', 'accepted')->count();
        $declinedReservations  = Reservation::where('status', 'declined')->count();

        // TIME FILTERS
        $today     = Carbon::today();
        $thisWeek  = Carbon::now()->subDays(7);
        $thisMonth = Carbon::now()->startOfMonth();

        $todayReservations   = Reservation::whereDate('created_at', $today)->count();
        $weeklyReservations  = Reservation::where('created_at', '>=', $thisWeek)->count();
        $monthlyReservations = Reservation::where('created_at', '>=', $thisMonth)->count();

        // REVENUE (only accepted)
        $estimatedRevenue = Reservation::where('status', 'accepted')
            ->sum('price_estimate');

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

            'estimated_revenue' => $estimatedRevenue ?? 0
        ], 200);
    }

    /**
     * MONTHLY STATS (For charts)
     */
    public function monthlyStats()
    {
        // RESERVATIONS COUNT BY MONTH
        $reservations = Reservation::selectRaw('COUNT(*) as total, MONTH(created_at) as month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // REVENUE BY MONTH
        $revenue = Reservation::where('status', 'accepted')
            ->selectRaw('SUM(price_estimate) as total, MONTH(created_at) as month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'reservations' => $reservations,
            'revenue'      => $revenue,
        ]);
    }
}
