@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Edit Project</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Ubah data proyek</p>
        </div>
    </div>
    <form action="{{ route('projects.update', $project) }}" method="POST" class="card" style="max-width:600px; gap:10px;">
        @csrf
        @method('PUT')
        <div class="field">
            <label class="hint">Nama Project <span style="color:red">*</span></label>
            <input class="input" type="text" name="name" value="{{ old('name', $project->name) }}" required>
        </div>
        <div class="field">
            <label class="hint">Lokasi <span style="color:red">*</span></label>
            <input class="input" type="text" name="location" value="{{ old('location', $project->location) }}" required>
        </div>
        <div class="field">
            <label class="hint">Catatan</label>
            <textarea class="input" name="notes" rows="2">{{ old('notes', $project->notes) }}</textarea>
        </div>
        <div class="field">
            <label class="hint">Total Unit</label>
            <input class="input" type="number" name="total_units" min="0"
                value="{{ old('total_units', $project->total_units) }}" placeholder="0">
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('projects.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan</button>
        </div>
    </form>
@endsection
