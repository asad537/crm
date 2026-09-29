<?php

namespace Tests\Unit;

use App\DesignJobCard;
use Tests\TestCase;

class DesignJobCardStageTest extends TestCase
{
    public function test_saved_active_step_maps_to_the_card_being_worked_on(): void
    {
        $card = new DesignJobCard(['section_choices' => ['__active_step' => 1]]);
        $this->assertSame('Dummy / Sample Approval', $card->currentCardLabel());

        $card->section_choices = ['__active_step' => 4];
        $this->assertSame('Foam', $card->currentCardLabel());

        $card->section_choices = ['__active_step' => 5];
        $this->assertSame('Printing', $card->currentCardLabel());

        $card->section_choices = ['__active_step' => 6];
        $this->assertSame('Lamination', $card->currentCardLabel());
    }

    public function test_old_cards_without_saved_position_do_not_claim_a_stage(): void
    {
        $card = new DesignJobCard(['section_choices' => ['printing' => 'yes']]);
        $this->assertNull($card->currentCardLabel());
    }
}
