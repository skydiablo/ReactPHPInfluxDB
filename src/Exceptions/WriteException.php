<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\Exceptions;

use JetBrains\PhpStorm\Pure;
use SkyDiablo\ReactphpInfluxDB\Measurement\Point;

class WriteException extends \Exception implements ExceptionInterface
{

    const CODE = 500;

    /**
     * @param Point[] $points
     * @param \Throwable|null $previous
     */
    public function __construct(protected array $points, ?\Throwable $previous = null)
    {
        parent::__construct('Write error', self::CODE, $previous);
    }

    /**
     * @return Point[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }

}