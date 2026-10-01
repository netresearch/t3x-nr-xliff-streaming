<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\NrXliffStreaming\Exception;

use RuntimeException;

/**
 * Exception thrown when XLIFF content is malformed or invalid
 *
 * @author Netresearch DTT GmbH
 */
final class InvalidXliffException extends RuntimeException {}
