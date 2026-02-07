@extends('admin.layout')

@section('title', 'Allowed Origins')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-8 flex items-center justify-between">
            <div>
                 <h2 class="text-3xl font-bold font-outfit text-white">Allowed Origins</h2>
                 <p class="text-slate-400 mt-2">Manage URLs allowed to access the API via CORS.</p>
            </div>
            
             <!-- Add New Button (Triggers Modal - Simplified with details/summary or Alpine.js would be better but keeping vanilla) -->
             <button onclick="document.getElementById('add-modal').classList.remove('hidden')" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-500/30 transition-all flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Origin
            </button>
        </div>

        @if(session('success'))
            <div class="mb-6 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
             <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="glass rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-slate-300">
                    <thead class="bg-white/5 uppercase text-xs font-bold text-slate-400 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">URL</th>
                            <th class="px-6 py-4">Created At</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="origins-table-body" class="divide-y divide-white/5">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="add-modal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('add-modal')"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-8 glass rounded-3xl shadow-2xl">
            <h3 class="text-xl font-bold font-outfit text-white mb-6">Add Allowed Origin</h3>
            <form id="add-form" onsubmit="handleStore(event)">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Origin URL</label>
                    <input type="url" name="origin_url" placeholder="https://example.com" required
                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-white transition-all">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 rounded-xl border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-all">Cancel</button>
                    <button type="submit" class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-500/30 transition-all">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('edit-modal')"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-8 glass rounded-3xl shadow-2xl">
            <h3 class="text-xl font-bold font-outfit text-white mb-6">Edit Origin</h3>
            <form id="edit-form" onsubmit="handleUpdate(event)">
                <input type="hidden" id="edit-id">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Origin URL</label>
                    <input type="url" name="origin_url" id="edit-url-input" required
                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-white transition-all">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 rounded-xl border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-all">Cancel</button>
                    <button type="submit" class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-500/30 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = '/api/admin/allowed-origins';

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }

        const authHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + getCookie('admin_token')
        };

        // Fetch and Render
        async function fetchOrigins() {
            try {
                const response = await fetch(API_URL, { headers: authHeaders });
                if (!response.ok) throw new Error('Failed to fetch');
                const result = await response.json();
                const origins = result.data || result;
                renderTable(origins);
            } catch (error) {
                console.error(error);
            }
        }

        function renderTable(origins) {
            const tbody = document.getElementById('origins-table-body');
            tbody.innerHTML = '';

            if (origins.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-12 text-center text-slate-500">No origins allowed yet.</td></tr>`;
                return;
            }

            origins.forEach(origin => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-white/5 transition-colors';
                tr.innerHTML = `
                    <td class="px-6 py-4 font-mono text-indigo-300">${origin.origin_url}</td>
                    <td class="px-6 py-4 text-sm text-slate-500">${new Date(origin.created_at).toLocaleDateString()}</td>
                    <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                        <button onclick="openEditModal(${origin.id}, '${origin.origin_url}')" class="p-2 rounded-lg hover:bg-white/10 text-slate-400 hover:text-white transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button onclick="handleDelete(${origin.id})" class="p-2 rounded-lg hover:bg-red-500/20 text-slate-400 hover:text-red-400 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Add
        async function handleStore(e) {
            e.preventDefault();
            const form = e.target;
            const origin_url = form.origin_url.value;

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: authHeaders,
                    body: JSON.stringify({ origin_url })
                });

                if (response.ok) {
                    closeModal('add-modal');
                    form.reset();
                    fetchOrigins();
                } else {
                    alert('Failed to add origin');
                }
            } catch (error) {
                console.error(error);
            }
        }

        // Edit
        function openEditModal(id, url) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-url-input').value = url;
            document.getElementById('edit-modal').classList.remove('hidden');
        }

        async function handleUpdate(e) {
            e.preventDefault();
            const id = document.getElementById('edit-id').value;
            const origin_url = document.getElementById('edit-url-input').value;

            try {
                const response = await fetch(`${API_URL}/${id}`, {
                    method: 'PUT',
                    headers: authHeaders,
                    body: JSON.stringify({ origin_url })
                });

                if (response.ok) {
                    closeModal('edit-modal');
                    fetchOrigins();
                } else {
                    alert('Failed to update origin');
                }
            } catch (error) {
                console.error(error);
            }
        }

        // Delete
        async function handleDelete(id) {
            if (!confirm('Are you sure?')) return;

            try {
                const response = await fetch(`${API_URL}/${id}`, {
                    method: 'DELETE',
                    headers: authHeaders
                });

                if (response.ok) {
                    fetchOrigins();
                } else {
                    alert('Failed to delete origin');
                }
            } catch (error) {
                console.error(error);
            }
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Init
        document.addEventListener('DOMContentLoaded', fetchOrigins);
    </script>
@endsection
