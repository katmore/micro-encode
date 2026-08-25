<?php
declare(strict_types=1);

namespace MicroEncode;

interface EncoderInterface
{
    public function __toString(): string;

    public function getEncodedValue(): string;
}
