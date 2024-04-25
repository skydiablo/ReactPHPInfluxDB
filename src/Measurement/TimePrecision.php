<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\Measurement;

enum TimePrecision: string
{
    case Seconds = 's'; // 1s
    case MilliSeconds = 'ms'; // 0,001s
    case MicroSeconds = 'us'; // 0,000 001 s
    case NanoSeconds = 'ns'; // 0,000 000 001 s
}