<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Car;
use Illuminate\Http\Request;
use App\Notifications\ReservationStatusUpdated;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CREATE RESERVATION (USER, VERIFIED)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'car_id' => 'required|exists:cars,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Prevent double booking
        $overlap = Reservation::where('car_id', $request->car_id)
            ->where('status', '!=', 'declined')
            ->where(function ($query) use ($request) {
                $query->where('start_date', '<=', $request->end_date)
                      ->where('end_date', '>=', $request->start_date);
            })
            ->exists();

        if ($overlap) {
            return response()->json(['message' => 'Car already reserved for these dates'], 409);
        }

        // Price calculation
        $car = Car::findOrFail($request->car_id);
        $days = Carbon::parse($request->start_date)->diffInDays($request->end_date) + 1;
        $priceEstimate = $days * $car->price_per_day;

        // Create reservation
        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'car_id' => $request->car_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'price_estimate' => $priceEstimate
        ]);

        return response()->json([
            'message' => 'Reservation created successfully',
            'reservation' => $reservation
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | CALENDAR — Get reserved dates for a car
    |--------------------------------------------------------------------------
    */
    public function calendar($id)
    {
        $ranges = Reservation::where('car_id', $id)
            ->where('status', '!=', 'declined')
            ->get(['start_date', 'end_date']);

        return response()->json($ranges);
    }


    /*
    |--------------------------------------------------------------------------
    | USER RESERVATIONS
    |--------------------------------------------------------------------------
    */
    public function myReservations(Request $request)
    {
        return Reservation::where('user_id', $request->user()->id)
            ->with('car')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE RESERVATION STATUS (ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined'
        ]);

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        $reservation->update(['status' => $request->status]);

        // Notify user by email
        $reservation->user->notify(new ReservationStatusUpdated($reservation));

        return response()->json([
            'message' => 'Reservation status updated',
            'reservation' => $reservation
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN — LIST ALL RESERVATIONS
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return Reservation::with('user', 'car')->get();
    }
}
