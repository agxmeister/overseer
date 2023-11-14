<?php

namespace Watch\Schedule;

interface Builder
{
    public function run(): self;
    public function release(): array;
    public function addMilestone(): self;
    public function addMilestoneBuffer(): self;
    public function addFeedingBuffers(): self;
    public function addDates(): self;
    public function addBuffersConsumption(): self;
}
