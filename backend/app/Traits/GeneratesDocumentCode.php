<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait GeneratesDocumentCode
{
    /**
     * Generate unique document code with counter
     *
     * @param string $prefix Code prefix (e.g., 'F', 'BL', 'BC')
     * @param string $tableName Database table name
     * @return string Generated code
     */
    protected function generateUniqueCode(string $prefix, string $tableName): string
    {
        return DB::transaction(function () use ($prefix, $tableName) {
            $yearSuffix = date('y');
            $pattern = "$prefix-$yearSuffix-";

            // Get maximum counter using raw SQL for accuracy
            $result = DB::selectOne("
                SELECT MAX(
                    CAST(
                        SUBSTRING(code, ?) AS UNSIGNED
                    )
                ) as max_counter
                FROM {$tableName}
                WHERE code LIKE ?
                AND code NOT LIKE '%-deleted%'
                AND deleted_at IS NULL
                FOR UPDATE
            ", [strlen($pattern) + 1, $pattern . '%']);

            $maxCounter = $result->max_counter ?? 0;
            $newCounter = $maxCounter + 1;

            return $pattern . str_pad($newCounter, 3, '0', STR_PAD_LEFT);
        });
    }
}
