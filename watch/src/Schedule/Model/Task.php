<?php

namespace Watch\Schedule\Model;

class Task extends Node
{
    const STATE_STARTED = 'started';
    const STATE_COMPLETED = 'completed';
    const STATE_UNKNOWN = 'unknown';
}
