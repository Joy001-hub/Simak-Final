@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Edit Kavling</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Ubah data kavling</p>
        </div>
    </div>
    <form action="{{ route('kavling.update', $lot) }}" method="POST" class="card" style="max-width:600px; gap:10px;">
        @csrf
        @method('PUT')
        <div class="field">
            <label class="hint">Project <span style="color:red">*</span></label>
            <select class="input" name="project_id" required>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @if($lot->project_id == $project->id) selected @endif>{{ $project->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label class="hint">Blok/Number <span style="color:red">*</span></label>
            <input class="input" type="text" name="block_number" value="{{ $lot->block_number }}" required>
        </div>
        <div class="field">
            <label class="hint">Luas (m²) <span style="color:red">*</span></label>
            <input class="input" type="number" name="area" min="0" value="{{ $lot->area }}" required>
        </div>
        <div class="field">
            <label class="hint">Harga Dasar (Rp) <span style="color:red">*</span></label>
            <input class="input" type="number" name="base_price" min="0" value="{{ $lot->base_price }}" required>
        </div>
        <div class="field">
            <label class="hint">Status <span style="color:red">*</span></label>
            <select class="input" name="status" required>
                <option value="available" @if($lot->status == 'available') selected @endif>Available</option>
                <option value="sold" @if($lot->status == 'sold') selected @endif>Sold</option>
                <option value="reserved" @if($lot->status == 'reserved') selected @endif>Reserved</option>
                <option value="active" @if($lot->status == 'active') selected @endif>Active</option>
            </select>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('kavling.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>
@endsection
