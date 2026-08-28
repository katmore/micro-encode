<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

use RuntimeException;

/**
 * Thrown by {@see XmlEncoder} when a leaf value has no safe textual XML
 * representation and {@see XmlEncoderOptions::$dumpOk} is false (the
 * default). Pass `new XmlEncoderOptions(dumpOk: true)` to allow such data
 * to be represented instead as a checksummed, MIME-sniffed base64 dump.
 *
 * @author D. Bird <retran@gmail.com>
 */
final class UndumpableDataException extends RuntimeException
{
}
