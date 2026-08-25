<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

/**
 * Options for {@see MarkdownEncoder}.
 *
 * @author D. Bird <retran@gmail.com>
 */
final class MarkdownEncoderOptions
{
    /**
     * @param bool $orderedLists whether sequential indexed arrays render as an
     *   ordered list ("1. foo") instead of an unordered one ("- foo"); default false.
     *   This only affects a list when every one of its elements is a plain scalar
     *   value. A list containing an array, object, or multiline string always
     *   renders with ordered markers regardless of this option - CommonMark
     *   cannot reliably nest a content-less "-" marker's own contents versus a
     *   new sibling item using the same character, so ordered numbers (which are
     *   self-disambiguating) are used instead as a structural necessity.
     */
    public function __construct(
        public readonly bool $orderedLists = false,
    ) {
    }
}
