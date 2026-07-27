<?php

namespace App\Models;

use Database\Factories\ProviderAnalyticFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $provider
 * @property string $region
 * @property int $hour_bucket
 * @property int $success_count
 * @property int $failure_count
 * @property int $buffer_count
 * @property int $avg_load_ms
 * @property string $date
 */
#[Fillable(['provider', 'region', 'hour_bucket', 'success_count', 'failure_count', 'buffer_count', 'avg_load_ms', 'date'])]
class ProviderAnalytic extends Model
{
    /** @use HasFactory<ProviderAnalyticFactory> */
    use HasFactory;

    public function successRate(): float
    {
        $total = $this->success_count + $this->failure_count;

        return $total > 0 ? round($this->success_count / $total * 100, 1) : 0;
    }
}
