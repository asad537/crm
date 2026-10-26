<?php

namespace Tests\Feature;

use App\DeliveryChallan;
use App\DesignJob;
use Tests\Feature\Accounting\AccountingTestCase;

/** An accountant has full control of the Jobs module, like an admin: any job, job card, stage, status, dummy, challan, delete. */
class DesignJobAccountsAccessTest extends AccountingTestCase
{
    public function test_an_accountant_can_manage_any_job(): void
    {
        $designer = $this->makeUser('designer', 'Job Designer');
        $job = $this->job($designer);

        $this->signIn($accounts = $this->makeUser('accounts'));
        $list = $this->get(route('crm.design_jobs.index'))->assertOk()->assertSee($job->job_number)->assertSee('New Job');
        $this->assertStringContainsString(route('crm.design_jobs.stage', $job->id), $list->getContent(), 'stage dropdown on any job');
        $this->assertStringContainsString(route('crm.design_jobs.destroy', $job->id), $list->getContent(), 'actions menu with delete');
        $this->get(route('crm.design_jobs.create'))->assertOk();
        $this->get(route('crm.design_jobs.job_card.edit', $job->id))->assertOk()->assertSee('name="designer_id"', false);
        $this->get(route('crm.design_jobs.job_card.preview', $job->id))->assertOk();
        $this->get(route('crm.design_jobs.dummy', $job->id))->assertOk();

        $this->post(route('crm.design_jobs.stage', $job->id), ['production_stage' => 'printing'])->assertRedirect();
        $this->assertSame('printing', $job->fresh()->production_stage);
        $this->post(route('crm.design_jobs.status', $job->id), ['status' => 'delivered'])->assertRedirect();
        $this->assertSame('delivered', $job->fresh()->status);

        $this->post(route('crm.design_jobs.challan.store', $job->id), ['customer_name' => 'Box Client', 'items' => [['description' => 'Boxes', 'total_cartons' => '5']]])
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.design_jobs.index'));
        $challan = DeliveryChallan::where('design_job_id', $job->id)->firstOrFail();
        $this->put(route('crm.challans.update', $challan->id), ['customer_name' => 'Box Client LLC', 'items' => [['description' => 'Boxes', 'total_cartons' => '6']]])
            ->assertSessionHasNoErrors();
        $this->assertSame('Box Client LLC', $challan->fresh()->customer_name);
        $this->get(route('crm.challans.print', $challan->id))->assertOk();

        $other = $this->job($designer);
        $this->delete(route('crm.design_jobs.destroy', $other->id))->assertRedirect(route('crm.design_jobs.index'));
        $this->assertNull(DesignJob::find($other->id));
    }

    public function test_a_sales_user_still_only_views_and_other_roles_cannot_open_jobs(): void
    {
        $job = $this->job($this->makeUser('designer', 'Job Designer'));

        $this->signIn($this->makeUser('sales'));
        $list = $this->get(route('crm.design_jobs.index'))->assertOk()->assertSee($job->job_number);
        $this->assertStringNotContainsString(route('crm.design_jobs.stage', $job->id), $list->getContent(), 'stage shown as a label, not a dropdown');
        $this->post(route('crm.design_jobs.stage', $job->id), ['production_stage' => 'printing'])->assertForbidden();
        $this->delete(route('crm.design_jobs.destroy', $job->id))->assertForbidden();

        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.design_jobs.index'))->assertForbidden();
    }

    private function job($designer): DesignJob
    {
        return DesignJob::create(['job_number' => 'AMS-9'.random_int(100, 999), 'workspace_id' => $this->workspace->id, 'designer_id' => $designer->id,
            'estimate_ticket_id' => 0, 'title' => 'Accounts test box', 'status' => 'designing', 'production_stage' => 'dummy']);
    }
}
