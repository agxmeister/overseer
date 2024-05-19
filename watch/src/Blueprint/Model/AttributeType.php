<?php

namespace Watch\Blueprint\Model;

enum AttributeType
{
    case Sequence;
    case Schedule;
    case Date;
    case Default;
}
