<?php

namespace Tests\Unit;

use App\Support\ApplicationReason;
use PHPUnit\Framework\TestCase;

final class ApplicationReasonTest extends TestCase
{
    // Verify whitespace handling and the exact application word limit.
    public function test_it_counts_words_separated_by_whitespace(): void
    {
        self::assertSame(0, ApplicationReason::wordCount(" \n "));
        self::assertSame(3, ApplicationReason::wordCount("Fisherfolk  community\nmember"));
        self::assertSame(500, ApplicationReason::wordCount(implode(' ', array_fill(0, 500, 'word'))));
        self::assertSame(501, ApplicationReason::wordCount(implode(' ', array_fill(0, 501, 'word'))));
    }
}
