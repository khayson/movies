<?php

namespace App\Console\Commands;

use App\Services\ProviderHealthProbe;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:probe-provider-health')]
#[Description('Probe embed provider endpoints and cache health status')]
class ProbeProviderHealth extends Command
{
    public function handle(ProviderHealthProbe $probe): int
    {
        $stats = $probe->probeAll();

        $this->info("Checked {$stats['checked']} providers: {$stats['healthy']} healthy, {$stats['unhealthy']} unhealthy.");

        return self::SUCCESS;
    }
}
