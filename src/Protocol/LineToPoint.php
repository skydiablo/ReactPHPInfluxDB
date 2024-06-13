<?php

namespace SkyDiablo\ReactphpInfluxDB\Protocol;

use SkyDiablo\ReactphpInfluxDB\Measurement\Point;

class LineToPoint
{

    private RawLineParser $rawLineParser;

    public function __construct()
    {
        $this->rawLineParser = new RawLineParser();
    }


    /**
     * @param string $rawLine
     * @return Point|null
     * @throws \Exception
     */
    public function toPoint(string $rawLine): ?Point
    {
        if (str_starts_with($rawLine, '#')) { // skip comment lines
            return null;
        }

        list($rawTags, $rawFields, $timestamp) = $this->rawLineParser->parse($rawLine);

        if (!($rawTags && $rawFields)) {
            return null;
        }

        $tagsTuple = $this->rawLineParser->parse($rawTags, ',');
        $point = Point::create(array_shift($tagsTuple));
        return $point
            ->setTags($this->parseTagsTuple($tagsTuple))
            ->setFields($this->parseFields($rawFields))
            ->setTime($timestamp ? (int)$timestamp : null);
    }

    protected function parseTagsTuple(array $tagsTuple): array
    {
        $tags = [];
        foreach ($tagsTuple as $tuple) {
            list($name, $value) = explode('=', $tuple, 2);
            $tags[$name] = $this->guessTypes($value);
        }
        return $tags;
    }

    /**
     * @param string $rawFields
     * @return array
     * @throws \Exception
     */
    protected function parseFields(string $rawFields): array
    {
        $fields = [];
        foreach ($this->rawLineParser->parse($rawFields, ',') as $tuple) {
            list($name, $value) = explode('=', $tuple, 2);
//            if ($value === null) {
//                throw new \Exception(sprintf('Invalid Tuple: Name "%s" / Value: "%s" [raw input: "%s"]', $name, $value, $rawFields));
//            }
            $fields[$name] = $this->guessTypes($value);
        }
        return $fields;
    }

    protected function guessTypes($value): mixed
    {
        switch (true) {
            case str_ends_with($value, 'i') && is_numeric(substr($value, 0, -1)):
                $value = (int)$value;
                break;
            case is_numeric($value):
                $value = (float)$value;
                break;
            case strtoupper($value) === 'NULL':
                $value = null;
                break;
            case is_array($value):
                $value = (function (array $data) {
                    $result = [];
                    foreach ($data as $key => $value) {
                        $result[$key] = $this->guessTypes($value);
                    }
                    return $result;
                })($value);
                break;
            case $value = ['t' => true, 'f' => false, 'true' => true, 'false' => false][$value] ?? $value; //beware, have to be the last in switch case list!
                break;
        }
        return is_string($value) ? $this->stripQuotes($value) : $value;
    }

    protected function stripQuotes(string $value): string
    {
        return (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'")) ?
            substr($value, 1, -1) : $value;
    }

}