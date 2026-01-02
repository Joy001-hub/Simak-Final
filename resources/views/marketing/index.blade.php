@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Tim Marketing</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola data tim penjualan</p>
        </div>
        <a href="{{ route('marketing.create') }}" class="chip is-active"
            style="display:inline-flex; align-items:center; gap:8px;">
            <span style="font-size:18px; line-height:0.9;">+</span> Add Salesman
        </a>
    </div>

    <div class="card" style="padding: 0; width: 100%;">
        <table class="table-clean" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:260px;">Name</th>
                    <th style="width:200px;">Phone</th>
                    <th style="text-align:left; width:140px; padding-left:14px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teams as $person)
                    <tr>
                        <td>{{ $person->id }}</td>
                        <td style="font-weight:700; color:#0f172a;">{{ $person->name }}</td>
                        <td style="white-space: nowrap;">{{ $person->phone }}</td>
                        <td style="padding-left:14px; white-space: nowrap;">
                            <a href="{{ route('marketing.edit', $person) }}" class="btn"
                                style="padding:8px 10px; border-color:#e5e7eb;">Edit</a>
                            <form action="{{ route('marketing.destroy', $person) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn"
                                    style="padding:8px 10px; border-color:#e5e7eb; color:#b4232a; margin-left:6px;"
                                    onclick="return confirm('Hapus salesman ini?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:18px;">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection