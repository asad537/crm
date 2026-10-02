<?php

namespace Tests\Feature;

use App\CrmUser;
use App\CrmWorkspace;
use App\DeliveryChallan;
use App\DesignJob;
use App\Http\Controllers\Crm\DeliveryChallanController;
use App\Support\CrmWorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryChallanTest extends TestCase
{
    public function test_challan_can_be_saved_and_edited_without_opening_print(): void
    {
        DB::beginTransaction();

        try {
            $workspace = CrmWorkspace::create([
                'name' => 'Challan test',
                'slug' => 'challan-test-' . uniqid(),
                'is_active' => true,
            ]);
            CrmWorkspaceContext::set($workspace->id);
            $user = CrmUser::create([
                'name' => 'Challan tester',
                'email' => 'challan-' . uniqid() . '@example.invalid',
                'password' => bcrypt('test-password'),
                'role' => 'admin',
            ]);
            Auth::guard('crm')->setUser($user);
            $job = DesignJob::create([
                'job_number' => 'TEST-CHALLAN-' . uniqid(),
                'workspace_id' => $workspace->id,
                'designer_id' => $user->id,
                'estimate_ticket_id' => 0,
                'title' => 'Test box',
                'status' => 'designing',
            ]);

            $controller = new DeliveryChallanController();
            $saved = $controller->store(Request::create('/', 'POST', [
                'customer_name' => 'First customer',
                'items' => [['description' => 'Boxes', 'total_cartons' => '5']],
            ]), $job->id);

            $this->assertSame(route('crm.design_jobs.index'), $saved->getTargetUrl());
            $challan = DeliveryChallan::where('design_job_id', $job->id)->firstOrFail();
            $this->assertSame('First customer', $challan->customer_name);
            $this->assertSame('close', $job->fresh()->production_stage);
            $this->assertSame($challan->id, DesignJob::with('challan')->findOrFail($job->id)->challan->id);

            $updated = $controller->update(Request::create('/', 'PUT', [
                'customer_name' => 'Updated customer',
                'items' => [['description' => 'Updated boxes', 'total_cartons' => '8']],
            ]), $challan->id);

            $this->assertSame(route('crm.design_jobs.index'), $updated->getTargetUrl());
            $this->assertSame('Updated customer', $challan->fresh()->customer_name);
            $this->assertSame('Updated boxes', $challan->fresh()->items[0]['description']);
            $this->assertSame(1, DeliveryChallan::where('design_job_id', $job->id)->count());
        } finally {
            DB::rollBack();
            CrmWorkspaceContext::clear();
        }
    }
}
