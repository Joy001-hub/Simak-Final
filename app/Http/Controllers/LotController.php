<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use App\Http\Requests\LotRequest;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Http\Request;

class LotController extends Controller
{
    use SafeExecution;

    public function index()
    {
        return $this->safeExecute(function () {
            $lots = Lot::with('project')->get();
            return view('kavling.index', ['lots' => $lots]);
        }, 'dashboard');
    }

    public function create()
    {
        return $this->safeExecute(function () {
            $projects = Project::all();
            return view('kavling.create', compact('projects'));
        }, 'kavling.index');
    }

    public function store(LotRequest $request)
    {
        return $this->safeExecute(function () use ($request) {
            $projectId = $request->input('project_id');
            $project = Project::findOrFail($projectId);
            $currentLotCount = Lot::where('project_id', $projectId)->count();
            $projectLimit = (int) ($project->total_units ?? 0);

            if ($request->input('mode') === 'bulk') {
                $prefix = $request->input('bulk_prefix');
                $start = (int) $request->input('bulk_start');
                $end = (int) $request->input('bulk_end');
                $suffix = $request->input('bulk_suffix');
                $plannedCount = $end - $start + 1;

                if ($projectLimit > 0 && ($currentLotCount + $plannedCount) > $projectLimit) {
                    return back()->withErrors(['bulk_end' => "Limit {$projectLimit} unit"])->withInput();
                }

                $created = 0;
                $skipped = 0;
                $commonData = $request->validated();
                unset($commonData['bulk_prefix'], $commonData['bulk_start'], $commonData['bulk_end'], $commonData['bulk_suffix']);

                for ($i = $start; $i <= $end; $i++) {
                    if ($projectLimit > 0 && ($currentLotCount + $created) >= $projectLimit) {
                        $skipped += ($end - $i + 1);
                        break;
                    }

                    $blockNumber = trim($prefix . '-' . $i . ($suffix ? ' ' . $suffix : ''));
                    $exists = Lot::where('project_id', $projectId)->where('block_number', $blockNumber)->exists();

                    if (!$exists) {
                        $lotData = array_merge($commonData, ['block_number' => $blockNumber]);
                        Lot::create($lotData);
                        $created++;
                    } else {
                        $skipped++;
                    }
                }

                $msg = "{$created} kavling berhasil ditambahkan.";
                if ($skipped > 0) {
                    $msg .= " {$skipped} kavling dilewatkan karena duplikat atau melebihi limit.";
                }

                return redirect()->route('kavling.index')->with('success', $msg);
            }

            if ($projectLimit > 0 && $currentLotCount >= $projectLimit) {
                return back()->withErrors(['block_number' => "Limit {$projectLimit} unit"])->withInput();
            }

            Lot::create($request->validated());
            return redirect()->route('kavling.index')->with('success', 'Kavling ditambahkan');
        }, 'kavling.index');
    }

    public function edit(Lot $kavling)
    {
        return $this->safeExecute(function () use ($kavling) {
            $projects = Project::all();
            return view('kavling.edit', [
                'lot' => $kavling,
                'projects' => $projects
            ]);
        }, 'kavling.index');
    }

    public function update(LotRequest $request, Lot $kavling)
    {
        return $this->safeExecute(function () use ($request, $kavling) {
            $kavling->update($request->validated());
            return redirect()->route('kavling.index')->with('success', 'Kavling diperbarui');
        }, 'kavling.index');
    }

    public function destroy(Lot $kavling, Request $request)
    {
        return $this->safeExecute(function () use ($kavling, $request) {
            $kavling->delete();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kavling dihapus']);
            }

            return redirect()->route('kavling.index')->with('success', 'Kavling dihapus');
        }, 'kavling.index');
    }

    public function pricing(Lot $lot)
    {
        return $this->safeJson(function () use ($lot) {
            $basePrice = (int) ($lot->base_price ?? 0);
            $dpPercent = 20;
            $dpNominal = $basePrice > 0 ? (int) round($basePrice * ($dpPercent / 100)) : 0;

            return response()->json([
                'id' => $lot->id,
                'project' => optional($lot->project)->name,
                'block_number' => $lot->block_number,
                'area' => $lot->area,
                'base_price' => $basePrice,
                'status' => $lot->status,
                'is_sold' => (bool) $lot->sale()->exists(),
                'payment_defaults' => [
                    'dp_percent' => $dpPercent,
                    'dp_nominal' => $dpNominal,
                    'tenor_months' => 12,
                    'due_day' => 10,
                ],
            ]);
        });
    }
}