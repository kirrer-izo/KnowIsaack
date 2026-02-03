<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLandingPageRequest;
use App\Http\Requests\UpdateLandingPageRequest;
use App\Http\Resources\LandingPageResource;
use App\Models\LandingPage;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct() {
        $this->middleware('auth:sanctum')->except(['index','show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return LandingPageResource::collection(LandingPage::with('techStacks')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLandingPageRequest $request)
    {
        $landingPage = LandingPage::create(
            $request->only(['about_me', 'skills'])
            );
        
        if ($request->has('tech_stack_ids')) {
            $landingPage->techStacks()->sync($request->tech_stack_ids);
        }

        $landingPage->load('techStacks');

        return response()->json($landingPage, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new LandingPageResource(LandingPage::findorFail($id)->load('techStacks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLandingPageRequest $request, string $id)
    {
        $landingPage = LandingPage::findorFail($id);
        $landingPage->update($request->only(['about_me', 'skills']));

        if ($request->has('tech_stack_ids')) {
            $landingPage->techStacks()->sync($request->tech_stack_ids);
        }
        
        $landingPage->load('techStacks');

        return response()->json($landingPage, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        LandingPage::findorFail($id)->delete();
        return response(null, 204);
    }
}
