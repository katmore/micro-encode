<?php
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
