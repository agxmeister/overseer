<?php

namespace Watch\Blueprint\Line;

enum AttributeType
{
    case Sequence;
    case Schedule;
    case Date;
    case Default;
}
