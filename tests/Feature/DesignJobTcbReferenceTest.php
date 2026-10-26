<?php

namespace Tests\Feature;

use App\DesignJob;
use Tests\Feature\Accounting\AccountingTestCase;

/** The "TCB Reference No" next to the Job No on the job card. */
class DesignJobTcbReferenceTest extends AccountingTestCase
{
    public function test_a_tcb_reference_is_saved_shown_and_searchable(): void
    {
        $this->get(route('crm.design_jobs.create'))->assertOk()->assertSee('TCB Reference No');

        $this->post(route('crm.design_jobs.store'), ['product' => 'TCB Ref Box', 'priority_level' => 'regular', 'tcb_reference' => '  TCB-18299-A  '])->assertSessionHasNoErrors();
        $job = DesignJob::where('title', 'TCB Ref Box')->firstOrFail();
        $this->assertSame('TCB-18299-A', $job->tcb_reference);

        $this->get(route('crm.design_jobs.job_card.edit', $job->id))->assertOk()->assertSee('value="TCB-18299-A"', false);
        $this->get(route('crm.design_jobs.index'))->assertOk()->assertSee('TCB-18299-A');
        $found = $this->get(route('crm.design_jobs.index', ['search' => '18299']))->assertOk()->viewData('jobs');
        $this->assertSame([$job->id], collect($found->items())->pluck('id')->all());

        // Changed or cleared from the job card.
        $this->post(route('crm.design_jobs.job_card.update', $job->id), ['product' => 'TCB Ref Box', 'priority_level' => 'regular', 'tcb_reference' => 'TCB-18300-B'])->assertSessionHasNoErrors();
        $this->assertSame('TCB-18300-B', $job->fresh()->tcb_reference);
        $this->post(route('crm.design_jobs.job_card.update', $job->id), ['product' => 'TCB Ref Box', 'priority_level' => 'regular', 'tcb_reference' => ''])->assertSessionHasNoErrors();
        $this->assertNull($job->fresh()->tcb_reference);

        $this->post(route('crm.design_jobs.job_card.update', $job->id), ['product' => 'TCB Ref Box', 'priority_level' => 'regular', 'tcb_reference' => str_repeat('x', 101)])
            ->assertSessionHasErrors('tcb_reference');
    }
}
