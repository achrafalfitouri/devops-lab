<?php
// app/Traits/HandlesConditionalRelationships.php

namespace App\Traits;

trait HandlesConditionalRelationships
{
    /**
     * Map of frontend relationship names to their dual relationship names
     * Format: 'frontendName' => ['processBased', 'quoteBased']
     */
    protected function getConditionalRelationshipsMap()
    {
        return [
            'invoices' => ['invoicesByProcess', 'invoicesByQuote'],
            'orderReceipts' => ['orderReceiptsByProcess', 'orderReceiptsByQuote'],
            'returnNotes' => ['returnNotesByProcess', 'returnNotesByQuote'],
            'invoiceCredits' => ['invoiceCreditsByProcess', 'invoiceCreditsByQuote'],
            'outputNotes' => ['outputNotesByProcess', 'outputNotesByQuote'],
            'refunds' => ['refundsByProcess', 'refundsByQuote'],
        ];
    }

    /**
     * Transform the loaded model to merge conditional relationships
     * Priority: process_group_id > quote_id
     */
    protected function mergeConditionalRelationships($model)
    {
        $map = $this->getConditionalRelationshipsMap();

        foreach ($map as $targetName => [$processBased, $quoteBased]) {
            $processLoaded = $model->relationLoaded($processBased);
            $quoteLoaded = $model->relationLoaded($quoteBased);

            $processHasData = $processLoaded && $model->$processBased->isNotEmpty();
            $quoteHasData = $quoteLoaded && $model->$quoteBased->isNotEmpty();

            // Priority logic: If both have data, use process_group_id
            if ($processHasData && $quoteHasData) {
                // Both have data - prioritize process
                $model->setRelation($targetName, $model->$processBased);
            } elseif ($processHasData) {
                // Only process has data
                $model->setRelation($targetName, $model->$processBased);
            } elseif ($quoteHasData) {
                // Only quote has data
                $model->setRelation($targetName, $model->$quoteBased);
            } elseif ($processLoaded || $quoteLoaded) {
                // At least one is loaded but both are empty - set empty collection
                $model->setRelation($targetName, collect());
            }

            // Remove the dual relationships from the response
            $model->unsetRelation($processBased);
            $model->unsetRelation($quoteBased);
        }

        return $model;
    }

    /**
     * Build relationships array with conditional relationships
     */
    protected function buildRelationshipsWithConditional(array $baseRelationships, array $conditionalConfig)
    {
        $map = $this->getConditionalRelationshipsMap();

        foreach ($conditionalConfig as $name => $callback) {
            if (isset($map[$name])) {
                // Add both variants of the conditional relationship
                [$processBased, $quoteBased] = $map[$name];
                $baseRelationships[$processBased] = $callback;
                $baseRelationships[$quoteBased] = $callback;
            }
        }

        return $baseRelationships;
    }

    /**
     * Optional: Get debug info about which relationships were used
     */
    protected function getConditionalRelationshipsDebugInfo($model)
    {
        $map = $this->getConditionalRelationshipsMap();
        $debug = [];

        foreach ($map as $targetName => [$processBased, $quoteBased]) {
            $processLoaded = $model->relationLoaded($processBased);
            $quoteLoaded = $model->relationLoaded($quoteBased);

            $processHasData = $processLoaded && $model->$processBased->isNotEmpty();
            $quoteHasData = $quoteLoaded && $model->$quoteBased->isNotEmpty();

            $debug[$targetName] = [
                'process_loaded' => $processLoaded,
                'process_has_data' => $processHasData,
                'process_count' => $processHasData ? $model->$processBased->count() : 0,
                'quote_loaded' => $quoteLoaded,
                'quote_has_data' => $quoteHasData,
                'quote_count' => $quoteHasData ? $model->$quoteBased->count() : 0,
                'used' => $processHasData ? 'process' : ($quoteHasData ? 'quote' : 'none'),
            ];
        }

        return $debug;
    }
}
