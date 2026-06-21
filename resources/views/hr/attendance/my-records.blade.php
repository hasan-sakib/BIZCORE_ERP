@extends('layouts.app')
@section('title', 'My Attendance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">My Attendance Records</h5>
    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">All Records</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>{{ $record->date?->format('d M Y, D') }}</td>
                        <td>{{ $record->check_in ?? '—' }}</td>
                        <td>{{ $record->check_out ?? '—' }}</td>
                        <td>{{ $record->working_hours ?? '—' }}</td>
                        <td>
                            @php $statusColors = ['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info']; @endphp
                            <span class="badge bg-{{ $statusColors[$record->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$record->status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_',' ',$record->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No attendance records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($records->hasPages())
    <div class="card-footer">{{ $records->links() }}</div>
    @endif
</div>
@endsection
