@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Projects</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola proyek perumahan</p>
        </div>
        <a href="{{ route('projects.create') }}" class="chip is-active"
            style="display:inline-flex; align-items:center; gap:8px;">
            <span style="font-size:18px; line-height:0.9;">+</span> Add Project
        </a>
    </div>

    <div class="card" style="padding: 0;">
        <table class="table-clean">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Project Name</th>
                    <th style="width:200px;">Location</th>
                    <th>Keterangan</th>
                    <th style="width:200px;">Status Kavling</th>
                    <th style="text-align:left; width:140px; padding-left:14px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    @php
                        $progress = $project->total_units ? round(($project->sold_units / $project->total_units) * 100) : 0;
                    @endphp
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td style="font-weight:700; color:#0f172a;">{{ $project->name }}</td>
                        <td>{{ $project->location }}</td>
                        <td>{{ $project->notes }}</td>
                        <td>
                            <div style="display:flex; flex-direction:column; gap:6px; width: 100%;">
                                <div style="display:flex; justify-content:space-between; font-size:12px; color:#0f172a;">
                                    <span>{{ $project->sold_units }} / {{ $project->total_units }} Terjual</span>
                                    <span>{{ $project->available ?? 0 }} unit tersedia</span>
                                </div>
                                <div style="width:100%; height:8px; background:#f1f5f9; border-radius:12px; overflow:hidden;">
                                    <div style="width:{{ $progress }}%; height:100%; background: var(--primary);"></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding-left:14px; white-space: nowrap;">
                            <a href="{{ route('projects.edit', $project) }}" class="btn light"
                                style="padding:8px 10px; border-color:#e5e7eb;">Edit</a>
                            <form action="{{ route('projects.destroy', $project) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn"
                                    style="padding:8px 10px; border-color:#e5e7eb; color:#b4232a; margin-left:6px;"
                                    onclick="return confirm('Hapus project ini?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection