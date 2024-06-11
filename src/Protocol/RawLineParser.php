<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\Protocol;

class RawLineParser
{

    public const string DEFAULT_PART_DELIMITER = ' ';
    public const string DEFAULT_PART_ENCLOSURE = '"';

    public function parse(
        string $input,
        string $delimiter = self::DEFAULT_PART_DELIMITER,
        string $enclosure = self::DEFAULT_PART_ENCLOSURE
    ): array
    {
        $result = [];
        $part = '';
        $isEscaped = false;
        $isEnclosed = false;
        foreach (str_split($input) as $char) {
            switch ($char) {
                case '\\':
                    if ($isEscaped) {
                        $part .= $char;
                    } else {
                        $isEscaped = true;
                        continue 2;
                    }
                    break;
                case $delimiter:
                    if ($isEscaped || $isEnclosed) {
                        $part .= $char;
                    } else {
                        $result[] = $part;
                        $part = '';
                    }
                    break;
                case $enclosure:
                    $part .= $char;
                    if (!$isEscaped) {
                        $isEnclosed = !$isEnclosed;
                    }
                    break;
                default:
                    $part .= $char;
            }
            $isEscaped = false;
        }
        if ($part) {
            $result[] = $part;
        }

        return $result;
    }


}