<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProjectController extends Controller
{

    public function __construct() {
        $this->middleware('auth:sanctum')->except(['index','show']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('techStacks')->get();

        return response()->json([
            'projects' => $projects
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create(
            $request->only(['name', 'description', 'goal', 'source_code', 'live_demo'])
        );

        if ($request->has('tech_stack_ids')) {
            $project->techStacks()->sync($request->tech_stack_ids);
        }

        $project->load('techStacks');

        return response()->json($project,201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return response()->json($project->load('techStacks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update(
            $request->only(['name', 'description', 'goal', 'source_code', 'live_demo'])
            );

        if ($request->has('tech_stack_ids')) {
            $project->techStacks()->sync($request->tech_stack_ids);
        }

        $project->load('techStacks');
        return response()->json($project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(null,204);
    }
}
