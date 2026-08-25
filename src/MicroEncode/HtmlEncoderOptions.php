<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

/**
 * Options for {@see HtmlEncoder}.
 *
 * @author D. Bird <retran@gmail.com>
 */
final class HtmlEncoderOptions
{
    /**
     * @param string $parentElement parent element name
     * @param string $childElement name of any child elements
     */
    public function __construct(
        public readonly string $parentElement = 'ul',
        public readonly string $childElement = 'li',
    ) {
    }
}
