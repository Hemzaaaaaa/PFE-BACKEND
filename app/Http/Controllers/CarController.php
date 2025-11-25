<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    // 🟢 PUBLIC — Everyone can browse cars (with search & filters)
    public function index(Request $request)
    {
        $query = Car::query();

        // 🔍 Generic search (brand or model)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'LIKE', "%$search%")
                  ->orWhere('model', 'LIKE', "%$search%");
            });
        }

        // 🔍 Specific brand filter
        if ($request->has('brand')) {
            $query->where('brand', 'LIKE', '%' . $request->brand . '%');
        }

        // 🔍 Specific model filter
        if ($request->has('model')) {
            $query->where('model', 'LIKE', '%' . $request->model . '%');
        }

        // 🔍 Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // 💰 Minimum price filter
        if ($request->has('price_min')) {
            $query->where('price_per_day', '>=', $request->price_min);
        }

        // 💰 Maximum price filter
        if ($request->has('price_max')) {
            $query->where('price_per_day', '<=', $request->price_max);
        }

        // 📄 Paginate (10 per page)
        $cars = $query->paginate(10);

        return response()->json($cars, 200);
    }

    // 🟢 PUBLIC — View single car
    public function show($id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        return response()->json($car, 200);
    }

    // 🔐 ADMIN + STAFF — Create car (supports image)
    public function store(Request $request)
    {
        $request->validate([
            'brand'         => 'required|string',
            'model'         => 'required|string',
            'plate_number'  => 'required|string|unique:cars',
            'year'          => 'required|integer',
            'price_per_day' => 'required|numeric',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Upload image if present
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('cars', 'public');
        }

        $car = Car::create([
            'brand'         => $request->brand,
            'model'         => $request->model,
            'plate_number'  => $request->plate_number,
            'year'          => $request->year,
            'price_per_day' => $request->price_per_day,
            'image'         => $imagePath,
        ]);

        return response()->json([
            'message'    => 'Car added successfully',
            'car'        => $car,
            'image_url'  => $imagePath ? asset('storage/' . $imagePath) : null
        ], 201);
    }

    // 🔐 ADMIN + STAFF — Update car (supports new image upload)
    public function update(Request $request, $id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $request->validate([
            'brand'         => 'sometimes|required|string',
            'model'         => 'sometimes|required|string',
            'plate_number'  => 'sometimes|required|string|unique:cars,plate_number,' . $id,
            'year'          => 'sometimes|required|integer',
            'price_per_day' => 'sometimes|required|numeric',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Update text fields
        $car->brand         = $request->brand ?? $car->brand;
        $car->model         = $request->model ?? $car->model;
        $car->plate_number  = $request->plate_number ?? $car->plate_number;
        $car->year          = $request->year ?? $car->year;
        $car->price_per_day = $request->price_per_day ?? $car->price_per_day;

        // Upload new image if provided
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('cars', 'public');
            $car->image = $imagePath;
        }

        $car->save();

        return response()->json([
            'message'    => 'Car updated successfully',
            'car'        => $car,
            'image_url'  => $car->image ? asset('storage/' . $car->image) : null
        ], 200);
    }

    // 🔐 ADMIN + STAFF — Delete car
    public function destroy($id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $car->delete();

        return response()->json(['message' => 'Car deleted successfully'], 200);
    }

    // 🔐 ADMIN + STAFF — Upload image separately
    public function uploadImage(Request $request, $id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Store file in storage/app/public/cars
        $path = $request->file('image')->store('cars', 'public');

        // Update car record
        $car->image = $path;
        $car->save();

        return response()->json([
            'message'   => 'Image uploaded successfully',
            'image_url' => asset('storage/' . $path),
            'car'       => $car
        ], 200);
    }
}
