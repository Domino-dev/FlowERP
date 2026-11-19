<?php
/**
 * Test: invoice PDF generation.
 */
declare(strict_types=1);

use Tester\Assert;

use App\Templates\InvoicePdfFactory;

use Tests\Support\TestData;

require __DIR__ . '/../bootstrap.php';

$invoicePdfFactory = new InvoicePdfFactory();

test('PDF is generated with company and delivery address', function () use ($invoicePdfFactory) {
    // regression: delivery address used to call non-existing getName()
    $pdf = $invoicePdfFactory->createInvoice(TestData::createInvoice(2, true), TestData::createCompany());
    $content = $pdf->Output('S');

    Assert::true(str_starts_with($content, '%PDF-'));
    Assert::same(1, $pdf->PageNo());
});

test('PDF is generated without company and delivery address', function () use ($invoicePdfFactory) {
    $pdf = $invoicePdfFactory->createInvoice(TestData::createInvoice(2, false), null);

    Assert::true(str_starts_with($pdf->Output('S'), '%PDF-'));
});

test('PDF embeds unicode font (czech characters)', function () use ($invoicePdfFactory) {
    // regression: core Helvetica font broke czech characters
    $content = $invoicePdfFactory->createInvoice(TestData::createInvoice(1), TestData::createCompany())->Output('S');

    Assert::contains('DejaVuSansCondensed', $content);
    Assert::notContains('/BaseFont /Helvetica', $content);
});

test('long invoice continues on more pages', function () use ($invoicePdfFactory) {
    $pdf = $invoicePdfFactory->createInvoice(TestData::createInvoice(40), TestData::createCompany());
    $pdf->Output('S');

    Assert::true($pdf->PageNo() > 1);
});
