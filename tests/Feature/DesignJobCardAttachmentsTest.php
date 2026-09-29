<?php

namespace Tests\Feature;

use App\DesignJobCard;
use App\Http\Controllers\Crm\DesignJobCardController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class DesignJobCardAttachmentsTest extends TestCase
{
    public function test_attachments_can_be_saved_and_removed_from_a_job_card(): void
    {
        Storage::fake('local');
        DB::beginTransaction();

        try {
            $card = DesignJobCard::create();
            $method = new ReflectionMethod(DesignJobCardController::class, 'syncAttachments');
            $method->setAccessible(true);
            $controller = new DesignJobCardController();
            $uploaded = [];
            $removed = [];

            $upload = Request::create('/', 'POST', [], [], [
                'attachments' => [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')],
            ]);
            $method->invokeArgs($controller, [$upload, $card, &$uploaded, &$removed]);

            $attachment = $card->attachments()->firstOrFail();
            $this->assertSame('proof.pdf', $attachment->original_name);
            $this->assertSame([$attachment->path], $uploaded);
            Storage::disk('local')->assertExists($attachment->path);

            $remove = Request::create('/', 'POST', ['remove_attachments' => [$attachment->id]]);
            $method->invokeArgs($controller, [$remove, $card, &$uploaded, &$removed]);

            $this->assertSame([$attachment->path], $removed);
            $this->assertSame(0, $card->attachments()->count());
        } finally {
            DB::rollBack();
        }
    }
}
