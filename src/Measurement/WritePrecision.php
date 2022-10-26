<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\Measurement;

enum WritePrecision: string
{
    case MS = 'ms';
    case S = 's';
    case US = 'us';
    case NS = 'ns';
}