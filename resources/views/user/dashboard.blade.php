<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Plan Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .purchase-btn { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .purchase-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5); }
    </style>
</head>
<body class="min-h-screen p-6 md:p-12">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-16">
            <div class="space-y-2">
                <span class="px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase tracking-widest border border-indigo-500/20">Impersonation Mode</span>
                <h1 id="welcome-text" class="text-4xl md:text-5xl font-black text-white leading-tight">Welcome Back</h1>
                <p id="user-email" class="text-slate-400 font-medium">Loading your profile...</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="logout()" class="px-6 py-3 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-300 font-bold border border-white/5 transition-all">Exit Impersonation</button>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- User Info Card -->
            <div class="lg:col-span-1">
                <div class="glass p-8 rounded-[2.5rem] sticky top-8">
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-3xl font-black text-white shadow-2xl mb-6" id="user-avatar">-</div>
                    <div class="space-y-6">
                        <div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Account Holder</p>
                            <h3 id="display-name" class="text-xl font-bold text-white">---</h3>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Member Since</p>
                            <p id="joined-date" class="text-slate-300 font-medium italic">---</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Status</p>
                            <span id="status-badge" class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-widest">Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Purchase Section -->
            <div class="lg:col-span-2 space-y-8">
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-white">Upgrade Your Experience</h2>
                        <p class="text-slate-400 mt-1">Choose a plan that fits your needs.</p>
                    </div>
                </div>

                <!-- Coupon & Payment Type Section -->
                <div class="glass p-6 rounded-3xl border-white/5 flex flex-col md:flex-row items-center gap-6">
                    <div class="flex-1">
                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Payment Settings</p>
                        <p class="text-slate-400 text-sm">Apply a promo code and choose your billing preference.</p>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                        <!-- Toggle -->
                        <div class="flex items-center gap-3 bg-white/5 p-1.5 rounded-2xl border border-white/10 shrink-0">
                            <button onclick="setPaymentType('subscription')" id="btn-recurring" 
                                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tight transition-all bg-indigo-500 text-white">Recurring</button>
                            <button onclick="setPaymentType('single')" id="btn-single" 
                                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tight transition-all text-slate-500 hover:text-white">One-Time</button>
                        </div>

                        <!-- Coupon Input -->
                        <div class="relative w-full md:w-48">
                            <input type="text" id="coupon-code" placeholder="Promo Code" 
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-indigo-500/50 transition-all text-center">
                        </div>
                    </div>
                </div>

                <div id="plan-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Loading skeleton -->
                    <div class="glass p-8 rounded-[2rem] border-white/5 animate-pulse min-h-[300px]"></div>
                    <div class="glass p-8 rounded-[2rem] border-white/5 animate-pulse min-h-[300px]"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const token = getCookie('user_token');
        let selectedPaymentType = 'subscription';

        function setPaymentType(type) {
            selectedPaymentType = type;
            const recBtn = document.getElementById('btn-recurring');
            const singleBtn = document.getElementById('btn-single');

            if (type === 'subscription') {
                recBtn.className = 'px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tight transition-all bg-indigo-500 text-white';
                singleBtn.className = 'px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tight transition-all text-slate-500 hover:text-white';
            } else {
                recBtn.className = 'px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tight transition-all text-slate-500 hover:text-white';
                singleBtn.className = 'px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tight transition-all bg-indigo-500 text-white';
            }
        }

        if (!token) {
            window.location.href = '/admin/users';
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchUserProfile();
            fetchPlans();
        });

        async function fetchUserProfile() {
            try {
                const response = await fetch('/api/auth/user/me', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const result = await response.json();
                const user = result.data || result;
                
                document.getElementById('welcome-text').innerText = `Welcome, ${user.name.split(' ')[0]}`;
                document.getElementById('user-email').innerText = user.email;
                document.getElementById('display-name').innerText = user.name;
                document.getElementById('user-avatar').innerText = user.name.charAt(0);
                document.getElementById('joined-date').innerText = new Date(user.created_at).toLocaleDateString();
                
                if (!user.is_active) {
                    const badge = document.getElementById('status-badge');
                    badge.innerText = 'Inactive';
                    badge.className = 'px-2 py-0.5 rounded-full bg-red-500/10 text-red-400 text-[10px] font-black uppercase tracking-widest';
                }
            } catch (e) {
                console.error('Failed to load user profile');
            }
        }

        async function fetchPlans() {
            try {
                const response = await fetch('/api/plans/list');
                const result = await response.json();
                const plans = result.data || result.plans || [];
                renderPlans(plans);
            } catch (e) {
                document.getElementById('plan-list').innerHTML = '<p class="text-red-400">Failed to load plans.</p>';
            }
        }

        function renderPlans(plans) {
            const container = document.getElementById('plan-list');
            if (plans.length === 0) {
                container.innerHTML = '<p class="text-slate-500 italic">No plans available at the moment.</p>';
                return;
            }

            container.innerHTML = plans.map(plan => `
                <div class="glass p-8 rounded-[2rem] border-white/5 hover:border-indigo-500/30 transition-all flex flex-col group">
                    <div class="mb-6">
                        <h3 class="text-xl font-black text-white mb-2">${plan.name}</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-white">$${plan.monthly_price}</span>
                            <span class="text-slate-500 text-sm font-medium">/ ${plan.duration || 'month'}</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-3 mb-8 flex-1">
                        ${(plan.formatted_features || []).map(f => `
                            <li class="flex items-start gap-2 text-sm text-slate-400 font-medium text-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>${f}</span>
                            </li>
                        `).join('')}
                    </ul>

                    <button onclick="purchasePlan(${plan.id})" class="purchase-btn w-full py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest">
                        Get Started
                    </button>
                </div>
            `).join('');
        }

        async function purchasePlan(planId) {
            const couponCode = document.getElementById('coupon-code').value.trim();
            const btn = event.currentTarget;
            const originalText = btn.innerText;
            
            try {
                btn.innerText = 'Redirecting...';
                btn.disabled = true;

                const response = await fetch('/api/user/plans/purchase', {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`, 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        plan_id: planId, 
                        payment_type: selectedPaymentType,
                        coupon_code: couponCode,
                        success_url: window.location.origin + '/payment/success',
                        cancel_url: window.location.origin + '/payment/cancel'
                    })
                });
                const result = await response.json();
                const purchaseData = result.data || result;
                if (purchaseData.url) {
                    window.location.href = purchaseData.url;
                } else {
                    alert(purchaseData.error || result.Message || 'Purchase failed');
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Plan purchase failed');
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        function logout() {
            document.cookie = "user_token=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT";
            window.location.href = '/admin/users';
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
</body>
</html>
