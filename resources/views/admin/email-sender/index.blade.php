@extends('admin.layout')

@section('title', 'Send Email')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight font-outfit text-white">Send Email</h1>
    </div>

    <form action="{{ route('admin.email-sender.send') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="glass p-8 rounded-2xl space-y-6">
            <!-- Template Selection -->
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Select Template</label>
                <select name="template_id" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all">
                    <option value="" class="bg-slate-900">-- Choose a template --</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" class="bg-slate-900">{{ $template->name }} ({{ $template->subject }})</option>
                    @endforeach
                </select>
                @error('template_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Select System Users -->
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Select System Users (Bulk)</label>
                    <select name="recipients[]" multiple class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all h-40">
                        <option value="all" class="bg-slate-900 font-bold text-indigo-400">SELECT ALL USERS</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" class="bg-slate-900">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-500 mt-2">Hold Ctrl (CMD) to select multiple users.</p>
                </div>

                <!-- Manual Emails -->
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Manual Email Addresses</label>
                    <textarea name="manual_emails" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all h-40" placeholder="user@example.com, another@example.com"></textarea>
                    <p class="text-[10px] text-slate-500 mt-2">Comma separated email addresses.</p>
                </div>
            </div>

            <div class="pt-4 border-t border-white/5 space-y-4">
                <div class="p-4 bg-indigo-500/10 rounded-xl border border-indigo-500/20 flex gap-3 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-indigo-300">Personalization Active</p>
                        <p class="text-xs text-indigo-300/70">The system will automatically replace <code class="bg-indigo-500/20 px-1 rounded">@{{name}}</code> with the recipient's name.</p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-10 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all shadow-xl shadow-indigo-500/20 transform hover:-translate-y-0.5 active:scale-95">
                        Send Now
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    @if(session('success'))
        <div class="mt-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif
</div>
@endsection
