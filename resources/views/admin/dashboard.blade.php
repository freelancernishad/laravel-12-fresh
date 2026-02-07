@extends('admin.layout')

@section('title', 'Dashboard Overview')

@section('content')
    <!-- System Information -->
    <div class="glass rounded-3xl overflow-hidden mb-8">
        <div class="p-6 border-b border-white/5 bg-white/5">
            <h3 class="text-xl font-bold font-outfit text-white">System Environment</h3>
            <p class="text-xs text-slate-400 mt-1">Real-time server and connection status</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-white/5">
            <!-- Client IP -->
            <div class="p-6">
                <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Your Request IP</span>
                <span class="text-lg font-mono text-indigo-400">{{ $systemInfo['client_ip'] }}</span>
            </div>
            <!-- Server IP -->
            <div class="p-6">
                <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Server Local IP</span>
                <span class="text-lg font-mono text-emerald-400">{{ $systemInfo['server_ip'] }}</span>
            </div>
            <!-- Outbound IP -->
            <div class="p-6">
                <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Server Outbound IP</span>
                <span class="text-lg font-mono text-amber-400">{{ $systemInfo['outbound_ip'] }}</span>
            </div>
            <!-- Laravel/PHP -->
            <div class="p-6">
                <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Version Status</span>
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-white">Laravel: <span class="text-indigo-300">{{ $systemInfo['laravel_version'] }}</span></span>
                    <span class="text-sm font-medium text-white">PHP: <span class="text-emerald-300">{{ $systemInfo['php_version'] }}</span></span>
                </div>
            </div>
        </div>
        <div class="p-6 bg-slate-900/50 border-t border-white/5 flex flex-wrap gap-8">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-xs text-slate-400">Database: <span class="text-white">{{ $systemInfo['database_connection'] }}</span></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span class="text-xs text-slate-400">OS: <span class="text-white">{{ $systemInfo['server_os'] }}</span></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <span class="text-xs text-slate-400">Software: <span class="text-white">{{ $systemInfo['server_software'] }}</span></span>
            </div>
        </div>
    </div>
@endsection
