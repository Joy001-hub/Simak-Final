<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use App\Http\Requests\MarketerRequest;
use App\Models\Marketer;

class MarketingController extends Controller
{
    use SafeExecution;

    public function index()
    {
        return $this->safeExecute(function () {
            $teams = Marketer::all();
            return view('marketing.index', compact('teams'));
        }, 'dashboard');
    }

    public function create()
    {
        return $this->safeExecute(function () {
            return view('marketing.create');
        }, 'marketing.index');
    }

    public function store(MarketerRequest $request)
    {
        return $this->safeExecute(function () use ($request) {
            Marketer::create($request->validated());
            return redirect()->route('marketing.index')->with('success', 'Salesman ditambahkan');
        }, 'marketing.index');
    }

    public function edit(Marketer $marketing)
    {
        return $this->safeExecute(function () use ($marketing) {
            return view('marketing.edit', ['marketer' => $marketing]);
        }, 'marketing.index');
    }

    public function update(MarketerRequest $request, Marketer $marketing)
    {
        return $this->safeExecute(function () use ($request, $marketing) {
            $marketing->update($request->validated());
            return redirect()->route('marketing.index')->with('success', 'Salesman diperbarui');
        }, 'marketing.index');
    }

    public function destroy(Marketer $marketing)
    {
        return $this->safeExecute(function () use ($marketing) {
            $marketing->delete();
            return redirect()->route('marketing.index')->with('success', 'Salesman dihapus');
        }, 'marketing.index');
    }
}
