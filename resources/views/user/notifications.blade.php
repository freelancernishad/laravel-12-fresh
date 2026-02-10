@extends('user.layout')

@section('title', 'Notifications')
@section('page_title', 'Notifications')
@section('page_subtitle', 'Stay updated with your account activity.')

@section('content')
<div id="section-notifications" class="content-section space-y-4">
     <div id="notifications-list" class="space-y-4">
        <!-- Notifications will be loaded here -->
        <div class="glass p-8 rounded-[2rem] border-white/5 text-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500 mx-auto mb-4"></div>
            <p class="text-slate-500">Loading notifications...</p>
         </div>
     </div>
</div>
@endsection

@section('init_scripts')
    fetchNotifications();
@endsection

@section('scripts')
<script>
    async function fetchNotifications() {
        const container = document.getElementById('notifications-list');
        try {
            const response = await fetch('/api/user/notifications', {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            });
            const result = await response.json();
            const notifications = result.data || result;

            if (notifications.length === 0) {
                 container.innerHTML = `
                    <div class="glass p-8 rounded-[2rem] border-white/5 text-center py-12">
                        <p class="text-slate-500 italic">No new notifications.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = notifications.map(n => `
                <div class="glass p-6 rounded-2xl border-white/5 flex items-start gap-4">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 mt-2 shrink-0"></div>
                    <div>
                        <h4 class="font-bold text-white text-sm mb-1">${n.data.title || 'Notification'}</h4>
                        <p class="text-slate-400 text-xs mb-3">${n.data.message || ''}</p>
                        <span class="text-[10px] text-slate-500 font-mono">${new Date(n.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
            `).join('');

        } catch (e) {
            console.error('Failed to load notifications');
            container.innerHTML = `<div class="glass p-8 rounded-[2rem] border-white/5 text-center py-12 text-red-400">Failed to load notifications.</div>`;
        }
    }
</script>
@endsection
