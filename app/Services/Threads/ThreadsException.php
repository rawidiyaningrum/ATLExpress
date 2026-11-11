<?php

namespace App\Services\Threads;

use RuntimeException;

/**
 * Kegagalan saat mengambil data dari Threads (API resmi maupun scraping).
 */
class ThreadsException extends RuntimeException {}
