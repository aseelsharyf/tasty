<?php

use App\Support\IngredientLinkFormatter;

it('renders safe markdown links and escapes surrounding text', function () {
    $html = IngredientLinkFormatter::toHtml('1 cup [rose petals](https://example.com/rose) <script>alert(1)</script>')->toHtml();

    expect($html)
        ->toContain('1 cup <a class="font-semibold underline decoration-1 underline-offset-3 hover:decoration-2" href="https://example.com/rose" target="_blank" rel="noopener noreferrer">rose petals</a>')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>');
});

it('leaves unsafe link markdown as escaped text', function () {
    $html = IngredientLinkFormatter::toHtml('[click](javascript:alert(1))')->toHtml();

    expect($html)->toBe('[click](javascript:alert(1))');
});

it('removes markdown destinations for structured recipe text', function () {
    expect(IngredientLinkFormatter::toPlainText('1 cup [rose petals](https://example.com/rose)'))
        ->toBe('1 cup rose petals');
});
