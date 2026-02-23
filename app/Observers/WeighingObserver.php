<?php

namespace App\Observers;

use App\Models\TbsPrice;
use App\Models\Weighing;

class WeighingObserver
{
    /**
     * Handle the Weighing "creating" event.
     * Auto-set price from daily TBS price if not provided
     */
    public function creating(Weighing $weighing): void
    {
        $this->autoSetPrice($weighing);
    }

    /**
     * Handle the Weighing "updating" event.
     * Recalculate total price if netto or price changes
     */
    public function updating(Weighing $weighing): void
    {
        // If price is not set, try to auto-set it
        if (!$weighing->price_per_kg) {
            $this->autoSetPrice($weighing);
        }

        // Always recalculate total price when netto or price changes
        $this->calculateTotalPrice($weighing);
    }

    /**
     * Auto-set price based on supplier type from queue
     */
    private function autoSetPrice(Weighing $weighing): void
    {
        // Skip if price is already set
        if ($weighing->price_per_kg) {
            return;
        }

        // Get supplier type from queue
        $supplierType = $this->getSupplierType($weighing);

        if ($supplierType) {
            $price = TbsPrice::getPriceForDate($supplierType);
            if ($price) {
                $weighing->price_per_kg = $price;
            }
        }
    }

    /**
     * Get supplier type from the associated queue
     */
    private function getSupplierType(Weighing $weighing): ?string
    {
        // If queue is already loaded
        if ($weighing->relationLoaded('queue') && $weighing->queue) {
            return $weighing->queue->supplier_type;
        }

        // If we have queue_id, load the queue
        if ($weighing->queue_id) {
            $queue = \App\Models\Queue::find($weighing->queue_id);
            return $queue?->supplier_type;
        }

        return null;
    }

    /**
     * Calculate total price from netto weight and price per kg
     */
    private function calculateTotalPrice(Weighing $weighing): void
    {
        if ($weighing->netto_weight && $weighing->price_per_kg) {
            $weighing->total_price = $weighing->netto_weight * $weighing->price_per_kg;
        }
    }
}
