@extends('admin.layout')

@section('title', 'Plan Features Management')

@section('content')
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold font-outfit text-white">Plan Features</h2>
                <p class="text-slate-400 mt-2">Manage reusable features that can be assigned to different plans.</p>
            </div>
            <button onclick="openAddModal()" class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-500 hover:bg-indigo-600 text-white font-bold transition-all shadow-lg shadow-indigo-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Feature
            </button>
        </div>

        <!-- Features Table/List -->
        <div class="glass rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 border-b border-white/5">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Key</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Title Template</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Unit</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="features-table-body" class="divide-y divide-white/5">
                        <!-- Loaded via API -->
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-10 h-10 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                                    <p class="text-slate-400">Loading features...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="feature-modal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6">
            <div class="glass rounded-3xl shadow-2xl overflow-hidden border border-white/10">
                <div class="p-6 border-b border-white/5 bg-white/5 flex items-center justify-between">
                    <h3 id="modal-title" class="text-xl font-bold text-white">Add New Feature</h3>
                    <button onclick="closeModal()" class="text-slate-400 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="feature-form" onsubmit="saveFeature(event)" class="p-6 space-y-4">
                    <input type="hidden" id="feature-id">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1.5 ml-1">Feature Key</label>
                        <input type="text" id="feature-key" required placeholder="e.g. view_contacts" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all font-mono text-sm">
                        <p class="mt-1 text-[10px] text-slate-500 ml-1">Unique identifier for the feature.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1.5 ml-1">Title Template</label>
                        <input type="text" id="feature-template" required placeholder="e.g. View upto :count contact details" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                        <p class="mt-1 text-[10px] text-slate-500 ml-1">Use <code>:placeholder</code> for dynamic values (e.g., :count, :limit).</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1.5 ml-1">Unit (Optional)</label>
                        <input type="text" id="feature-unit" placeholder="e.g. Contacts, Passes, Files" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="closeModal()" class="flex-1 py-3 px-4 rounded-xl bg-white/5 hover:bg-white/10 text-white font-bold transition-all border border-white/5">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 py-3 px-4 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white font-bold transition-all shadow-lg shadow-indigo-500/20">
                            Save Feature
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let features = [];

        // Fetch Features on Load
        document.addEventListener('DOMContentLoaded', fetchFeatures);

        async function fetchFeatures() {
            try {
                const response = await fetch('/api/admin/plan/features', {
                    headers: {
                        'Authorization': 'Bearer ' + getCookie('admin_token'),
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                features = result.data || result || [];
                renderFeatures();
            } catch (error) {
                console.error('Error fetching features:', error);
                showToast('Failed to load features', 'error');
            }
        }

        function renderFeatures() {
            const tbody = document.getElementById('features-table-body');
            if (features.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500 italic">
                            No features found. Create your first feature to get started.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = features.map(feature => `
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded bg-slate-800 border border-white/10 font-mono text-xs text-indigo-300">
                            ${feature.key}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-300">
                        ${highlightPlaceholders(feature.title_template)}
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-slate-500 text-sm">${feature.unit || '-'}</span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button onclick="editFeature(${feature.id})" class="p-2 rounded-lg bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 transition-all" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button onclick="deleteFeature(${feature.id})" class="p-2 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function highlightPlaceholders(text) {
            return text.replace(/:(\w+)/g, '<span class="text-amber-400 font-bold">:$1</span>');
        }

        function openAddModal() {
            document.getElementById('modal-title').innerText = 'Add New Feature';
            document.getElementById('feature-id').value = '';
            document.getElementById('feature-form').reset();
            document.getElementById('feature-modal').classList.remove('hidden');
        }

        function editFeature(id) {
            const feature = features.find(f => f.id === id);
            if (!feature) return;

            document.getElementById('modal-title').innerText = 'Edit Feature';
            document.getElementById('feature-id').value = feature.id;
            document.getElementById('feature-key').value = feature.key;
            document.getElementById('feature-template').value = feature.title_template;
            document.getElementById('feature-unit').value = feature.unit || '';
            document.getElementById('feature-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('feature-modal').classList.add('hidden');
        }

        async function saveFeature(e) {
            e.preventDefault();
            const id = document.getElementById('feature-id').value;
            const data = {
                key: document.getElementById('feature-key').value,
                title_template: document.getElementById('feature-template').value,
                unit: document.getElementById('feature-unit').value
            };

            const url = id ? `/api/admin/plan/features/${id}` : '/api/admin/plan/features';
            const method = id ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Authorization': 'Bearer ' + getCookie('admin_token'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.Message || error.message || 'Validation failed');
                }

                showToast(id ? 'Feature updated successfully' : 'Feature created successfully');
                closeModal();
                fetchFeatures();
            } catch (error) {
                console.error('Error saving feature:', error);
                showToast(error.message, 'error');
            }
        }

        async function deleteFeature(id) {
            if (!confirm('Are you sure you want to delete this feature? Plans using this feature may break.')) return;

            try {
                const response = await fetch(`/api/admin/plan/features/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + getCookie('admin_token'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    showToast('Feature deleted successfully');
                    fetchFeatures();
                } else {
                    throw new Error('Failed to delete feature');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-emerald-500' : 'bg-red-500';
            toast.className = `fixed bottom-8 right-8 px-6 py-3 rounded-xl ${bgColor} text-white font-bold shadow-2xl z-[100] transition-all transform translate-y-20 opacity-0`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-20', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 10);
            
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
                toast.classList.remove('translate-y-0', 'opacity-100');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
@endsection
