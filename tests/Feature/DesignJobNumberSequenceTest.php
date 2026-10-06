<?php

namespace Tests\Feature;

use App\DesignJob;
use App\Http\Controllers\Crm\DesignJobCardController;
use App\Support\CrmWorkspaceContext;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class DesignJobNumberSequenceTest extends TestCase
{
    public function test_imported_ams_numbers_continue_sequentially(): void
    {
        DB::beginTransaction();
        CrmWorkspaceContext::set(2);

        try {
            $maxExisting = DesignJob::where('job_number', 'like', 'AMS-%')
                ->pluck('job_number')
                ->reduce(function ($max, $number) {
                    return preg_match('/^AMS-(\d+)$/', $number, $matches)
                        ? max($max, (int) $matches[1]) : $max;
                }, 0);
            $start = max(62, $maxExisting);
            DB::table('design_job_number_sequences')->updateOrInsert(
                ['workspace_id' => 2],
                ['prefix' => 'AMS', 'last_number' => 62, 'created_at' => now(), 'updated_at' => now()]
            );

            $method = new ReflectionMethod(DesignJobCardController::class, 'newJobNumber');
            $method->setAccessible(true);
            $controller = new DesignJobCardController();

            $this->assertSame(sprintf('AMS-%04d', $start + 1), $method->invoke($controller));
            $this->assertSame(sprintf('AMS-%04d', $start + 2), $method->invoke($controller));
        } finally {
            DB::rollBack();
            CrmWorkspaceContext::clear();
        }
    }
}
