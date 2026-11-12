<?php

namespace Tests\Unit;

use App\Builder\Style\StyleTokens;
use PHPUnit\Framework\TestCase;

class StyleTokensTest extends TestCase
{
    public function test_unknown_tokens_never_produce_raw_css(): void
    {
        $this->assertSame('', StyleTokens::background('red; background:url(x)'));
        $this->assertStringNotContainsString('<', StyleTokens::container('"><script>'));
        $this->assertNull(StyleTokens::hex('red'));
        $this->assertNull(StyleTokens::hex('#fff;color:red'));
        $this->assertSame('#102C57', StyleTokens::hex('#102c57'));
    }

    public function test_responsive_columns_are_clamped_and_mapped(): void
    {
        $classes = StyleTokens::columns(['desktop' => ['columns' => 3], 'tablet' => ['columns' => 2], 'mobile' => ['columns' => 1]]);
        $this->assertSame('grid-cols-1 md:grid-cols-2 lg:grid-cols-3', $classes);

        $clamped = StyleTokens::columns(['desktop' => ['columns' => 99], 'mobile' => ['columns' => -4]]);
        $this->assertStringContainsString('lg:grid-cols-6', $clamped);
        $this->assertStringContainsString('grid-cols-1', $clamped);
    }

    public function test_visibility_per_device(): void
    {
        $this->assertSame('', StyleTokens::visibility([]));
        $this->assertSame('hidden md:block lg:block', StyleTokens::visibility(['mobile' => ['visible' => false]]));
    }

    public function test_padding_inherits_from_larger_device(): void
    {
        $this->assertSame('py-6 md:py-8 lg:py-32', StyleTokens::paddingY(['padding_y' => 'xl'], ['tablet' => ['padding_y' => 'sm']]));
    }
}
