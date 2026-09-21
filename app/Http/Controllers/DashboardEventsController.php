<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardEventsController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->stream(function (): void {
            set_time_limit(0);
            $lastRevision = null;
            $lastKeepAlive = microtime(true);

            while (! connection_aborted()) {
                $revision = $this->revision();

                if ($revision !== $lastRevision) {
                    echo "event: dashboard.updated\n";
                    echo 'data: '.json_encode(['revision' => $revision])."\n\n";
                    $lastRevision = $revision;
                    $lastKeepAlive = microtime(true);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                } elseif (microtime(true) - $lastKeepAlive >= 15) {
                    echo ": keepalive\n\n";
                    $lastKeepAlive = microtime(true);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                sleep(2);
            }
        }, 200, [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function revision(): string
    {
        $tables = ['Document', 'Payment', 'PaymentType', 'Customer', 'DocumentItem', 'Product'];

        if (Schema::hasTable('StartingCash')) {
            $tables[] = 'StartingCash';
        }

        return collect($tables)
            ->map(function (string $table): string {
                $state = DB::table($table)
                    ->selectRaw('COUNT(*) as row_count, COALESCE(MAX(Id), 0) as last_id')
                    ->first();

                return $table.':'.$state->row_count.':'.$state->last_id;
            })
            ->implode('|');
    }
}
