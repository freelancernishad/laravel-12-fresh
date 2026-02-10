<?php

require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

// Scenario Data
$startDate = Carbon::parse('2026-02-01'); // Started 10 days ago
$currentDate = Carbon::parse('2026-02-11'); // Today (10 days used)
$durationMonths = 6;
$endDate = $startDate->copy()->addMonths($durationMonths); // End date of current sub
$amountPaid = 60.00; // Plan A price

$newPlanPrice = 240.00; // Plan B price

echo "--- Current Plan A ---\n";
echo "Price: $" . number_format($amountPaid, 2) . "\n";
echo "Duration: 6 Months\n";
echo "Start Date: " . $startDate->toDateString() . "\n";
echo "End Date: " . $endDate->toDateString() . "\n";
echo "Total Days: " . $startDate->diffInDays($endDate) . " days\n";
echo "\n";

echo "--- Usage Status ---\n";
echo "Current Date: " . $currentDate->toDateString() . "\n";
echo "Used Days: " . $startDate->diffInDays($currentDate) . " days\n";
$remainingDays = $currentDate->diffInDays($endDate, false);
echo "Remaining Days: " . $remainingDays . " days\n";
echo "\n";

echo "--- Proration Calculation ---\n";
$totalDays = $startDate->diffInDays($endDate);
$dailyRate = $amountPaid / $totalDays;
echo "Daily Rate: $" . number_format($dailyRate, 4) . " / day\n";

$unusedValue = $dailyRate * $remainingDays;
echo "Unused Value (Credit): $" . number_format($unusedValue, 2) . "\n";

$payToday = $newPlanPrice - $unusedValue;
echo "\n--- Upgrade to Plan B ($240) ---\n";
echo "New Plan Price: $" . number_format($newPlanPrice, 2) . "\n";
echo "Less Credit: -$" . number_format($unusedValue, 2) . "\n";
echo "---------------------------------\n";
echo "PAY TODAY: $" . number_format($payToday, 2) . "\n";
