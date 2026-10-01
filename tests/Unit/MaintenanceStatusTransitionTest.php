<?php

namespace Tests\Unit;

use App\Enums\MaintenanceStatus;
use App\Services\MaintenanceStatusTransition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MaintenanceStatusTransitionTest extends TestCase
{
    private MaintenanceStatusTransition $transitions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transitions = new MaintenanceStatusTransition;
    }

    public function test_scheduled_can_start_complete_or_cancel(): void
    {
        $this->assertTrue($this->transitions->canTransition(MaintenanceStatus::Scheduled, MaintenanceStatus::InProgress));
        $this->assertTrue($this->transitions->canTransition(MaintenanceStatus::Scheduled, MaintenanceStatus::Completed));
        $this->assertTrue($this->transitions->canTransition(MaintenanceStatus::Scheduled, MaintenanceStatus::Cancelled));
    }

    public function test_in_progress_can_complete_or_cancel(): void
    {
        $this->assertTrue($this->transitions->canTransition(MaintenanceStatus::InProgress, MaintenanceStatus::Completed));
        $this->assertTrue($this->transitions->canTransition(MaintenanceStatus::InProgress, MaintenanceStatus::Cancelled));
        $this->assertFalse($this->transitions->canTransition(MaintenanceStatus::InProgress, MaintenanceStatus::Scheduled));
    }

    public function test_completed_and_cancelled_are_terminal(): void
    {
        foreach ([MaintenanceStatus::Completed, MaintenanceStatus::Cancelled] as $terminal) {
            foreach (MaintenanceStatus::cases() as $status) {
                $this->assertFalse($this->transitions->canTransition($terminal, $status));
            }
        }
    }

    public function test_assert_throws_for_invalid_transition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->transitions->assertCanTransition(MaintenanceStatus::Completed, MaintenanceStatus::InProgress);
    }
}
