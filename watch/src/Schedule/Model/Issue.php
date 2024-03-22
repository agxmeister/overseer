<?php

namespace Watch\Schedule\Model;

class Issue extends Node
{
    const STATE_QUEUED = 'queued';
    const STATE_STARTED = 'started';
    const STATE_COMPLETED = 'completed';
    const STATE_UNKNOWN = 'unknown';
}
