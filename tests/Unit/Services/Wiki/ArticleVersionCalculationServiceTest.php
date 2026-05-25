<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Wiki;

use Polis\Services\Wiki\ArticleVersionCalculationService;
use Polis\Tests\TestCase;

/**
 * Class ArticleVersionCalculationServiceTest
 */
final class ArticleVersionCalculationServiceTest extends TestCase
{
    public function test_calculate_text_diff_percentage(): void
    {
        $service = new ArticleVersionCalculationService;

        $this->assertSame(0.33, (float) number_format($service->calculateTextDiffPercentage('hi', 'hi '), 2));
        $this->assertSame(1.20, (float) number_format($service->calculateTextDiffPercentage('hello steve', 'hello'), 2));
    }

    public function test_determine_if_major_returns_true_when_header_completely_changed(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\n## let's change this header\n\nHopefully it will work";
        $oldContent = "# header\n\nSomething for a test\n\n## Now it's different\n\nHopefully it will work";

        $this->assertTrue($service->determineIfMajor($newContent, $oldContent));
    }

    public function test_determine_if_major_returns_false_when_header_punctuation_changed(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\n## lets change this header\n\nHopefully it will work";
        $oldContent = "# header\n\nSomething for a test\n\n## let's change this header\n\nHopefully it will work";

        $this->assertFalse($service->determineIfMajor($newContent, $oldContent));
    }

    public function test_determine_if_major_returns_true_when_header_removed(): void
    {
        $service = new ArticleVersionCalculationService;

        $oldContent = "# header\n\nSomething for a test\n\n## let's remove this header\n\nHopefully it will work";
        $newContent = "# header\n\nSomething for a test\n\nHopefully it will work";

        $this->assertTrue($service->determineIfMajor($newContent, $oldContent));
    }

    public function test_determine_if_major_returns_true_when_content_changes_a_lot(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\nHopefully it will work";
        $oldContent = "# header\n\nSomething for a test. I am so happy that this is working.\n\nHopefully it will work";

        $this->assertTrue($service->determineIfMajor($newContent, $oldContent));
    }

    public function test_determine_if_major_returns_false_when_content_changes_little(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\nHopefully it will work";
        $oldContent = "# header\n\nSomething for a test.\n\nHopefully it will work";

        $this->assertFalse($service->determineIfMajor($newContent, $oldContent));
    }

    public function test_determine_if_minor_returns_true_when_a_paragraph_was_added(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\nHopefully it will work\n\nHere's a new paragraph";
        $oldContent = "# header\n\nSomething for a test\n\nHopefully it will work";

        $this->assertTrue($service->determineIfMinor($newContent, $oldContent));
    }

    public function test_determine_if_minor_returns_false_when_a_line_break_was_added(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\nHopefully it will work\n\n";
        $oldContent = "# header\n\nSomething for a test\n\nHopefully it will work";

        $this->assertFalse($service->determineIfMinor($newContent, $oldContent));
    }

    public function test_determine_if_minor_returns_true_when_a_new_sentence_was_added(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test\n\nHopefully it will work. Here's another sentence";
        $oldContent = "# header\n\nSomething for a test\n\nHopefully it will work";

        $this->assertTrue($service->determineIfMinor($newContent, $oldContent));
    }

    public function test_determine_if_minor_returns_false_when_punctuation_was_changed(): void
    {
        $service = new ArticleVersionCalculationService;

        $newContent = "# header\n\nSomething for a test.\n\nHopefully it will work.";
        $oldContent = "# header\n\nSomething for a test\n\nHopefully it will work";

        $this->assertFalse($service->determineIfMinor($newContent, $oldContent));
    }
}
