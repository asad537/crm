<?php

namespace Tests\Feature;

use App\DesignJob;
use App\CrmUser;
use App\CrmWorkspace;
use App\Http\Controllers\Crm\DesignJobCardController;
use App\Support\CrmWorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class DesignJobNumberSequenceTest extends TestCase
{
    public function test_create_keeps_an_edited_number_and_allocates_an_unchanged_suggestion(): void
    {
        DB::beginTransaction();
        try {
            $workspace = CrmWorkspace::create([
                'name' => 'Job number test',
                'slug' => 'job-number-test-' . uniqid(),
                'is_active' => true,
            ]);
            CrmWorkspaceContext::set($workspace->id);
            DB::table('design_job_number_sequences')->insert([
                'workspace_id' => $workspace->id,
                'prefix' => 'AMS',
                'last_number' => 62,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = CrmUser::create([
                'name' => 'Job number tester',
                'email' => 'job-number-' . uniqid() . '@example.invalid',
                'password' => bcrypt('test-password'),
                'role' => 'admin',
            ]);
            Auth::guard('crm')->setUser($user);
            $controller = new DesignJobCardController();

            $controller->store(Request::create('/', 'POST', [
                'job_no' => 'SPECIAL-77',
                'job_no_suggestion' => 'AMS-0063',
                'product' => 'Custom numbered box',
                'priority_level' => 'regular',
            ]));
            $this->assertSame('SPECIAL-77', DesignJob::where('title', 'Custom numbered box')->value('job_number'));
            $this->assertSame(62, (int) DB::table('design_job_number_sequences')->where('workspace_id', $workspace->id)->value('last_number'));

            $controller->store(Request::create('/', 'POST', [
                'job_no' => 'AMS-0063',
                'job_no_suggestion' => 'AMS-0063',
                'product' => 'Auto numbered box',
                'priority_level' => 'regular',
            ]));
            $this->assertSame('AMS-0063', DesignJob::where('title', 'Auto numbered box')->value('job_number'));
            $this->assertSame(63, (int) DB::table('design_job_number_sequences')->where('workspace_id', $workspace->id)->value('last_number'));
        } finally {
            Auth::guard('crm')->logout();
            DB::rollBack();
            CrmWorkspaceContext::clear();
        }
    }

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

            $this->assertSame(sprintf('AMS-%04d', $start + 1), $controller->nextJobNumberPreview());
            $this->assertSame(62, (int) DB::table('design_job_number_sequences')->where('workspace_id', 2)->value('last_number'));
            $this->assertSame(sprintf('AMS-%04d', $start + 1), $method->invoke($controller));
            $this->assertSame(sprintf('AMS-%04d', $start + 2), $controller->nextJobNumberPreview());
            $this->assertSame(sprintf('AMS-%04d', $start + 2), $method->invoke($controller));
        } finally {
            DB::rollBack();
            CrmWorkspaceContext::clear();
        }
    }
}
