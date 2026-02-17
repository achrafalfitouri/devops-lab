<?php
// app/Traits/HasConditionalRelationships.php

namespace App\Traits;

trait HasConditionalRelationships
{
    /**
     * Get the appropriate foreign key for a relationship
     * Checks multiple possible keys in order of priority
     */
    protected function getRelationshipKey(array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (!empty($this->$key)) {
                return $key;
            }
        }

        // Return the last key as default fallback
        return end($possibleKeys);
    }

    /**
     * Create a conditional hasMany relationship
     */
    protected function conditionalHasMany($related, array $foreignKeys, $localKey = null)
    {
        $foreignKey = $this->getRelationshipKey($foreignKeys);
        return $this->hasMany($related, $foreignKey, $localKey ?? $foreignKey);
    }

    /**
     * Create a conditional belongsTo relationship
     */
    protected function conditionalBelongsTo($related, array $foreignKeys, $ownerKey = null)
    {
        $foreignKey = $this->getRelationshipKey($foreignKeys);
        return $this->belongsTo($related, $foreignKey, $ownerKey ?? 'id');
    }
}
