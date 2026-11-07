<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\country\StoreCountryRequest;
use App\Http\Requests\country\UpdateCountryRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $count = $request->count ?? 10;
        $search = $request->searchChar;

        $countries = Country::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('e_name', 'like', "%{$search}%");
            })
            ->paginate($count);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    public function getAll()
    {
        $countries = Country::get();

        return response()->json([
            'success' => true,
            'data' => CountryResource::collection($countries)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        $validated = $request->validated();

        $country = Country::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Permission Created Successfully',
            'data' => new CountryResource($country),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json(['message' => 'country not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CountryResource($country),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, string $id)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json(['message' => 'Country not found'], 404);
        }

        $validated = $request->validated();

        $country->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'country updated successfully',
            'data' => new CountryResource($country),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json(['message' => 'country not found'], 404);
        }

        $country->delete();

        return response()->json([
            'success' => true,
            'message' => 'country deleted successfully'
        ]);
    }
}
