<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;

class ProjectController extends Controller
{
    use SafeExecution;

    public function index()
    {
        return $this->safeExecute(function () {
            $projects = Project::withCount([
                'lots as available' => function ($q) {
                    $q->where('status', 'available');
                }
            ])->get();

            return view('projects.index', compact('projects'));
        }, 'dashboard');
    }

    public function create()
    {
        return $this->safeExecute(function () {
            return view('projects.create');
        }, 'projects.index');
    }

    public function store(ProjectRequest $request)
    {
        return $this->safeExecute(function () use ($request) {
            Project::create($request->validated());
            return redirect()->route('projects.index')->with('success', 'Project ditambahkan');
        }, 'projects.index');
    }

    public function edit(Project $project)
    {
        return $this->safeExecute(function () use ($project) {
            return view('projects.edit', compact('project'));
        }, 'projects.index');
    }

    public function update(ProjectRequest $request, Project $project)
    {
        return $this->safeExecute(function () use ($request, $project) {
            $project->update($request->validated());
            return redirect()->route('projects.index')->with('success', 'Project diperbarui');
        }, 'projects.index');
    }

    public function destroy(Project $project)
    {
        return $this->safeExecute(function () use ($project) {
            $project->delete();
            return redirect()->route('projects.index')->with('success', 'Project dihapus');
        }, 'projects.index');
    }
}
