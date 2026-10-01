<?php

namespace Tests\Unit;

use App\Enums\AssetStatus;
use App\Services\AssetStatusTransition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AssetStatusTransitionTest extends TestCase
{
    private AssetStatusTransition $transitions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transitions = new AssetStatusTransition;
    }

    public function test_allowed_transitions_from_available(): void
    {
        $this->assertTrue($this->transitions->canTransition(AssetStatus::Available, AssetStatus::Assigned));
        $this->assertTrue($this->transitions->canTransition(AssetStatus::Available, AssetStatus::Maintenance));
        $this->assertTrue($this->transitions->canTransition(AssetStatus::Available, AssetStatus::Retired));
        $this->assertTrue($this->transitions->canTransition(AssetStatus::Available, AssetStatus::Lost));
    }

    public function test_assigned_can_return_to_available(): void
    {
        $this->assertTrue($this->transitions->canTransition(AssetStatus::Assigned, AssetStatus::Available));
        $this->assertFalse($this->transitions->canTransition(AssetStatus::Assigned, AssetStatus::Retired));
    }

    public function test_retired_is_terminal(): void
    {
        foreach (AssetStatus::cases() as $status) {
            $this->assertFalse($this->transitions->canTransition(AssetStatus::Retired, $status));
        }
    }

    public function test_lost_can_only_be_reinstated(): void
    {
        $this->assertTrue($this->transitions->canTransition(AssetStatus::Lost, AssetStatus::Available));
        $this->assertFalse($this->transitions->canTransition(AssetStatus::Lost, AssetStatus::Maintenance));
    }

    public function test_transition_to_same_status_is_not_allowed(): void
    {
        $this->assertFalse($this->transitions->canTransition(AssetStatus::Available, AssetStatus::Available));
    }

    public function test_assert_throws_for_invalid_transition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->transitions->assertCanTransition(AssetStatus::Retired, AssetStatus::Assigned);
    }

    public function test_assert_does_not_throw_for_valid_transition(): void
    {
        $this->transitions->assertCanTransition(AssetStatus::Maintenance, AssetStatus::Available);

        $this->assertTrue(true);
    }
}
