@extends('admin.layout')

@section('title', 'Payment History')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h2 class="text-3xl font-bold font-outfit text-white">Transactions</h2>
            <p class="text-slate-400 mt-2">View all successful payments and financial transactions.</p>
        </div>

        <!-- Payments Table -->
        <div class="glass rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 border-b border-white/5">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Transaction ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">User</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Date</th>
                        </tr>
                    </thead>
                    <tbody id="payments-table-body" class="divide-y divide-white/5">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-10 h-10 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                                    <p class="text-slate-400">Loading payments...</p>
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

        document.addEventListener('DOMContentLoaded', () => fetchPayments(1));

        async function fetchPayments(page) {
            currentPage = page;
            const tbody = document.getElementById('payments-table-body');
            
            try {
                const response = await fetch(`/api/admin/payments?page=${page}`, {
                    headers: {
                        'Authorization': 'Bearer ' + getCookie('admin_token'),
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                
                // Handle different response structures
                const meta = result.data || result;
                const items = meta.data || [];
                
                renderPayments(items);
                renderPagination(meta);
            } catch (error) {
                console.error('Error fetching payments:', error);
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-red-400">Failed to load payments.</td></tr>`;
            }
        }

        function renderPayments(items) {
            const tbody = document.getElementById('payments-table-body');
            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No payments found.</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(payment => `
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    <td class="px-6 py-4">
                        <span class="text-xs font-mono text-indigo-300 bg-indigo-500/10 px-2 py-1 rounded border border-indigo-500/20">
                            ${payment.transaction_id || payment.id}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-white">${payment.user ? payment.user.name : 'Unknown'}</span>
                            <span class="text-[10px] text-slate-500">${payment.user ? payment.user.email : '-'}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-lg font-black text-white">$${payment.amount}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-lg bg-white/5 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                            ${payment.payable_type ? payment.payable_type.split('\\').pop() : 'Direct'}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-slate-400">${formatDate(payment.created_at)}</span>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(meta) {
            const container = document.getElementById('pagination-container');
            if (!meta.links || meta.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            container.innerHTML = meta.links.map(link => `
                <button 
                    onclick="fetchPayments(${link.url ? new URL(link.url).searchParams.get('page') : currentPage})"
                    class="px-3 py-1 rounded-lg text-sm font-medium transition-all ${link.active ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white disabled:opacity-50'}"
                    ${!link.url ? 'disabled' : ''}
                >
                    ${link.label.replace('&laquo; ', '').replace(' &raquo;', '')}
                </button>
            `).join('');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
@endsection
