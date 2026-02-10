@extends('admin.layout')

@section('title', 'Email History')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-extrabold tracking-tight font-outfit text-white">Email History</h1>
    <div class="text-slate-400 text-sm">Track all sent emails and their status</div>
</div>

<div class="glass rounded-2xl overflow-hidden border border-white/10">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-white/5 border-b border-white/10">
                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Recipient</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Subject</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Template</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Status</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Sent At</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($logs as $log)
            <tr class="hover:bg-white/[0.02] transition-colors">
                <td class="px-6 py-4">
                    <div class="font-medium text-white">{{ $log->recipient }}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-slate-300">{{ $log->subject }}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="text-indigo-400 text-sm italic">{{ $log->template->name ?? 'N/A' }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 bg-green-500/10 text-green-400 text-[10px] font-bold uppercase tracking-wider rounded-full border border-green-500/20">
                        {{ $log->status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="text-slate-500 text-xs">{{ $log->created_at->format('M d, H:i') }}</div>
                </td>
                <td class="px-6 py-4 text-center">
                    <form action="{{ route('admin.email-logs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Delete this log entry?')">
                        @csrf
                        @method('DELETE')
                        <button class="p-2 text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                    No email history found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $logs->links() }}
</div>
@endsection
