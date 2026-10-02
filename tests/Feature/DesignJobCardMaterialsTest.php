<?php

namespace Tests\Feature;

use App\DesignJob;
use App\DesignJobCard;
use App\Http\Controllers\Crm\DesignJobCardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class DesignJobCardMaterialsTest extends TestCase
{
    public function test_procurement_rows_can_be_saved_reordered_and_printed(): void
    {
        DB::beginTransaction();

        try {
            $job = DesignJob::create([
                'job_number' => 'TEST-MATERIALS-' . uniqid(),
                'workspace_id' => 1,
                'estimate_ticket_id' => 0,
                'designer_id' => 0,
                'title' => 'Test box',
                'status' => 'designing',
            ]);
            $card = DesignJobCard::create(['design_job_id' => $job->id]);
            $sync = new ReflectionMethod(DesignJobCardController::class, 'syncMaterials');
            $sync->setAccessible(true);
            $controller = new DesignJobCardController();

            $rows = [];
            for ($i = 1; $i <= 25; $i++) {
                $rows[] = [
                    'item' => 'Item ' . $i,
                    'specs' => 'Spec ' . $i,
                    'qty' => (string) $i,
                    'needed_by' => '2026-10-15',
                    'remarks' => 'Remark ' . $i,
                ];
            }
            $sync->invoke($controller, $card, $rows);
            $this->assertSame(25, $card->materials()->count());
            $this->assertSame('Item 25', $card->materials()->get()->last()->item);

            $sync->invoke($controller, $card, [
                ['item' => 'Replacement', 'specs' => 'Revised', 'qty' => '2', 'needed_by' => '2026-10-20', 'remarks' => 'Urgent'],
                ['item' => '', 'specs' => '', 'qty' => '', 'needed_by' => '', 'remarks' => ''],
            ]);
            $this->assertSame(1, $card->materials()->count());
            $this->assertSame('Replacement', $card->materials()->first()->item);

            $save = new ReflectionMethod(DesignJobCardController::class, 'saveCard');
            $save->setAccessible(true);
            $card = $save->invoke($controller, Request::create('/', 'POST', [
                'product' => 'Test box',
                'priority_level' => 'regular',
                'wizard_current_step' => 12,
                'materials' => [[
                    'item' => 'Final item',
                    'specs' => 'Final spec',
                    'qty' => '12',
                    'needed_by' => '2026-10-22',
                    'remarks' => 'Approved',
                ]],
            ]), $job, false);
            $this->assertSame('Procurement List', $card->currentCardLabel());
            $this->assertSame('Final item', $card->materials()->first()->item);

            $html = view('crm.design_jobs.job_card_pdf', [
                'job' => $job,
                'card' => $card,
                'stocks' => collect(),
                'materials' => $card->materials,
                'print' => false,
            ])->render();
            $this->assertStringContainsString('Procurement List', $html);
            $this->assertStringContainsString('Final item', $html);
            $this->assertStringContainsString('22 Oct 2026', $html);

        } finally {
            DB::rollBack();
        }
    }
}
