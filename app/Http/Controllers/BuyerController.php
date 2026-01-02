<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use App\Http\Requests\BuyerRequest;
use App\Models\Buyer;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    use SafeExecution;

    public function index()
    {
        return $this->safeExecute(function () {
            $buyers = Buyer::all();
            return view('buyers.index', compact('buyers'));
        }, 'dashboard');
    }

    public function create(Request $request)
    {
        return $this->safeExecute(function () use ($request) {
            $fromSale = $request->query('from_sale');
            return view('buyers.create', compact('fromSale'));
        }, 'buyers.index');
    }

    public function store(BuyerRequest $request)
    {
        return $this->safeExecute(function () use ($request) {
            $buyer = Buyer::create($request->validated());

            if ($request->has('from_sale')) {
                return redirect()->route('penjualan.create', [
                    'buyer_id' => $buyer->id,
                    'from_sale' => $request->input('from_sale')
                ])->with('success', 'Pembeli berhasil dibuat. Silakan lanjut buat transaksi baru.');
            }

            return redirect()->route('buyers.index')->with('success', 'Buyer ditambahkan');
        }, 'buyers.index');
    }

    public function edit(Buyer $buyer)
    {
        return $this->safeExecute(function () use ($buyer) {
            return view('buyers.edit', ['buyer' => $buyer]);
        }, 'buyers.index');
    }

    public function update(BuyerRequest $request, Buyer $buyer)
    {
        return $this->safeExecute(function () use ($request, $buyer) {
            $buyer->update($request->validated());
            return redirect()->route('buyers.index')->with('success', 'Buyer diperbarui');
        }, 'buyers.index');
    }

    public function destroy(Buyer $buyer)
    {
        return $this->safeExecute(function () use ($buyer) {
            $buyer->delete();
            return redirect()->route('buyers.index')->with('success', 'Buyer dihapus');
        }, 'buyers.index');
    }
}
