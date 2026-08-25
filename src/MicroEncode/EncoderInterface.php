<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

interface EncoderInterface
{
    public function __toString(): string;

    public function getEncodedValue(): string;
}
