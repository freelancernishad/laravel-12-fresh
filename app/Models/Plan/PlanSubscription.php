<?php

namespace App\Models\Plan;

use App\Models\User;
use App\Models\Feature;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_subscription_id',  // optional: only for recurring
        'start_date',
        'end_date',
        'next_billing_date',       // optional: for recurring
        'original_amount',
        'final_amount',
        'discount_amount',
        'discount_percent',
        'status',                  // active, canceled, expired, past_due
        'plan_features',
        'billing_interval',        // optional: monthly, yearly
        'billing_cycles_completed' // optional: for recurring
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'next_billing_date' => 'datetime',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'plan_features' => 'array',
        'billing_cycles_completed' => 'integer',
    ];

    protected $appends = ['formatted_plan_features'];
    protected $hidden = ['plan_features'];

    // ---------------------------
    // Relationships
    // ---------------------------
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    // ---------------------------
    // Accessors
    // ---------------------------
    public function getFormattedPlanFeaturesAttribute()
    {
        if (empty($this->plan_features) || !is_array($this->plan_features)) return [];

        return collect($this->plan_features)->map(function ($item) {
            $feature = PlanFeature::where('key', $item['key'])->first();
            if (!$feature) return $item['value'] ?? '';
            $data = $item;
            unset($data['key']);
            return $feature->render($data);
        })->filter()->all();
    }

    // ---------------------------
    // Helper Methods
    // ---------------------------

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && (!$this->end_date || now()->lessThanOrEqualTo($this->end_date));
    }

    /**
     * Mark subscription as canceled
     */
    public function cancel()
    {
        $this->update(['status' => 'canceled']);
    }

    /**
     * Increment billing cycle for recurring subscriptions
     */
    public function incrementBillingCycle()
    {
        if (!$this->billing_interval) return;

        $this->increment('billing_cycles_completed');

        // Update next billing date based on interval
        $interval = $this->billing_interval;
        $this->next_billing_date = match ($interval) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $this->save();
    }

    /**
     * Check if subscription is recurring
     */
    public function isRecurring(): bool
    {
        return !empty($this->stripe_subscription_id) && !empty($this->billing_interval);
    }
}
