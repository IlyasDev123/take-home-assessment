<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Generation\Enums\GenerationStatus;
use PHPUnit\Framework\TestCase;

class GenerationStatusTest extends TestCase
{
    public function test_pending_can_transition_to_queued(): void
    {
        $this->assertTrue(GenerationStatus::Pending->canTransitionTo(GenerationStatus::Queued));
    }

    public function test_pending_can_transition_to_failed(): void
    {
        $this->assertTrue(GenerationStatus::Pending->canTransitionTo(GenerationStatus::Failed));
    }

    public function test_pending_cannot_transition_to_succeeded(): void
    {
        $this->assertFalse(GenerationStatus::Pending->canTransitionTo(GenerationStatus::Succeeded));
    }

    public function test_queued_can_transition_to_processing(): void
    {
        $this->assertTrue(GenerationStatus::Queued->canTransitionTo(GenerationStatus::Processing));
    }

    public function test_queued_can_transition_directly_to_succeeded(): void
    {
        $this->assertTrue(GenerationStatus::Queued->canTransitionTo(GenerationStatus::Succeeded));
    }

    public function test_processing_can_transition_to_succeeded(): void
    {
        $this->assertTrue(GenerationStatus::Processing->canTransitionTo(GenerationStatus::Succeeded));
    }

    public function test_processing_can_transition_to_failed(): void
    {
        $this->assertTrue(GenerationStatus::Processing->canTransitionTo(GenerationStatus::Failed));
    }

    public function test_succeeded_is_terminal(): void
    {
        $this->assertTrue(GenerationStatus::Succeeded->isTerminal());
        $this->assertFalse(GenerationStatus::Succeeded->canTransitionTo(GenerationStatus::Failed));
    }

    public function test_failed_is_terminal(): void
    {
        $this->assertTrue(GenerationStatus::Failed->isTerminal());
        $this->assertFalse(GenerationStatus::Failed->canTransitionTo(GenerationStatus::Succeeded));
    }

    public function test_pending_and_queued_are_not_terminal(): void
    {
        $this->assertFalse(GenerationStatus::Pending->isTerminal());
        $this->assertFalse(GenerationStatus::Queued->isTerminal());
        $this->assertFalse(GenerationStatus::Processing->isTerminal());
    }
}
