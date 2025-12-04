<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarController extends Controller
{
    // 🟢 PUBLIC — Browse cars
    public function index(Request $request)
    {
        $query = Car::with('images');

        // SEARCH
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('brand', 'LIKE', "%{$request->search}%")
                  ->orWhere('model', 'LIKE', "%{$request->search}%");
            });
        }

        // BRAND FILTER
        if ($request->brand) {
            $query->where('brand', 'LIKE', "%{$request->brand}%");
        }

        // MODEL FILTER
        if ($request->model) {
            $query->where('model', 'LIKE', "%{$request->model}%");
        }

        // YEAR
        if ($request->year) {
            $query->where('year', $request->year);
        }

        // PRICE
        if ($request->price_min) {
            $query->where('price_per_day', '>=', $request->price_min);
        }

        if ($request->price_max) {
            $query->where('price_per_day', '<=', $request->price_max);
        }

        return response()->json($query->paginate(10), 200);
    }

    // 🟢 PUBLIC — Single car
    public function show($id)
    {
        $car = Car::with('images')->find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        return response()->json($car);
    }

    // 🔐 ADMIN — Create car
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
            'message' => 'Car created successfully',
            'car'     => $car
        ], 201);
    }

    // 🔐 ADMIN — Update car
    public function update(Request $request, $id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $request->validate([
            'brand'         => 'sometimes|required|string',
            'model'         => 'sometimes|required|string',
            'plate_number'  => "sometimes|required|string|unique:cars,plate_number,$id",
            'year'          => 'sometimes|required|integer',
            'price_per_day' => 'sometimes|required|numeric',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Exclude image field so update() won't break
        $car->update($request->except('image'));

        // Replace main image
        if ($request->hasFile('image')) {

            // delete old image
            if ($car->image) {
                Storage::disk('public')->delete($car->image);
            }

            $path = $request->file('image')->store('cars', 'public');
            $car->image = $path;
            $car->save();
        }

        return response()->json([
            'message' => 'Car updated successfully',
            'car'     => $car
        ]);
    }

    // 🔐 ADMIN — Delete car
    public function destroy($id)
    {
        $car = Car::with('images')->find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        // delete main image
        if ($car->image) {
            Storage::disk('public')->delete($car->image);
        }

        // delete all sub images
        foreach ($car->images as $img) {
            Storage::disk('public')->delete($img->image);
            $img->delete();
        }

        $car->delete();

        return response()->json(['message' => 'Car deleted successfully']);
    }

    // 🔐 ADMIN — Upload MULTIPLE images
    public function uploadImages(Request $request, $id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:4096'
        ]);

        $uploaded = [];

        foreach ($request->file('images') as $file) {
            $path = $file->store('cars', 'public');

            $img = CarImage::create([
                'car_id' => $id,
                'image'  => $path,
            ]);

            $uploaded[] = asset("storage/$path");
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images'  => $uploaded
        ]);
    }

    // 🔐 ADMIN — Delete single image
    public function deleteImage($imageId)
    {
        $image = CarImage::find($imageId);

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        Storage::disk('public')->delete($image->image);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
