@extends('admin.layout')

@section('title', 'Subscriptions Overview')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h2 class="text-3xl font-bold font-outfit text-white">Plan Subscriptions</h2>
            <p class="text-slate-400 mt-2">Monitor active and historical subscription plans across your user base.</p>
        </div>

        <!-- Subscriptions Table -->
        <div class="glass rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 border-b border-white/5">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">User</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Plan</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Pricing</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Dates</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody id="subscriptions-table-body" class="divide-y divide-white/5">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-10 h-10 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                                    <p class="text-slate-400">Loading subscriptions...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div id="pagination-container" class="p-4 border-t border-white/5 flex justify-end gap-2"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', () => fetchSubscriptions(1));

        async function fetchSubscriptions(page) {
            currentPage = page;
            const tbody = document.getElementById('subscriptions-table-body');
            
            try {
                const response = await fetch(`/api/admin/subscriptions?page=${page}`, {
                    headers: {
                        'Authorization': 'Bearer ' + getCookie('admin_token'),
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                
                // Handle different response structures
                const meta = result.data || result;
                const subs = meta.data || [];
                
                renderSubscriptions(subs);
                renderPagination(meta);
            } catch (error) {
                console.error('Error fetching subscriptions:', error);
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-red-400">Failed to load subscriptions.</td></tr>`;
            }
        }

        function renderSubscriptions(subs) {
            const tbody = document.getElementById('subscriptions-table-body');
            if (subs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No subscriptions found.</td></tr>`;
                return;
            }

            tbody.innerHTML = subs.map(sub => `
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center font-bold text-xs text-indigo-400 border border-white/10 uppercase">
                                ${sub.user ? sub.user.name.charAt(0) : '?'}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-white">${sub.user ? sub.user.name : 'Unknown User'}</span>
                                <span class="text-[10px] text-slate-500">${sub.user ? sub.user.email : '-'}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-slate-300 font-medium">${sub.plan ? sub.plan.name : 'N/A'}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-white">$${sub.price || '0.00'}</span>
                            <span class="text-[10px] text-slate-500 capitalize">${sub.payment_method || 'stripe'}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs space-y-1">
                            <div class="flex items-center gap-2 text-slate-400">
                                <span class="w-10">Start:</span>
                                <span class="text-slate-300">${formatDate(sub.starts_at || sub.created_at)}</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-400">
                                <span class="w-10">End:</span>
                                <span class="text-slate-300">${formatDate(sub.ends_at)}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        ${getStatusBadge(sub)}
                    </td>
                </tr>
            `).join('');
        }

        function getStatusBadge(sub) {
            const now = new Date();
            const end = sub.ends_at ? new Date(sub.ends_at) : null;
            
            let status = 'active';
            let colorClass = 'bg-emerald-500/20 text-emerald-400';
            
            if (end && end < now) {
                status = 'expired';
                colorClass = 'bg-slate-500/20 text-slate-400';
            } else if (sub.status === 'canceled') {
                status = 'canceled';
                colorClass = 'bg-red-500/20 text-red-400';
            }

            return `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${colorClass}">${status}</span>`;
        }

        function renderPagination(meta) {
            const container = document.getElementById('pagination-container');
            if (!meta.links || meta.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            container.innerHTML = meta.links.map(link => `
                <button 
                    onclick="fetchSubscriptions(${link.url ? new URL(link.url).searchParams.get('page') : currentPage})"
                    class="px-3 py-1 rounded-lg text-sm font-medium transition-all ${link.active ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white disabled:opacity-50'}"
                    ${!link.url ? 'disabled' : ''}
                >
                    ${link.label.replace('&laquo; ', '').replace(' &raquo;', '')}
                </button>
            `).join('');
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'Lifetime';
            return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
@endsection
