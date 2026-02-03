<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTechStackRequest;
use App\Http\Requests\UpdateTechStackRequest;
use App\Http\Resources\TechStackResource;
use App\Models\TechStack;
use Illuminate\Http\Request;

class TechStackController extends Controller
{
    public function __construct() {
        $this->middleware('auth:sanctum')->except(['index','show']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TechStackResource::collection(TechStack::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTechStackRequest $request)
    {
       $data = $request->validated();
       
       $techStack = TechStack::create($data);

       return response()->json($techStack, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new TechStackResource(TechStack::findorFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTechStackRequest $request, string $id)
    {
        $techStack = TechStack::findorFail($id);
        $data = $request->validated();
        $techStack->update($data);

        return response()->json($techStack, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        TechStack::findorFail($id)->delete();
        return response(null, 204);
    }
}
