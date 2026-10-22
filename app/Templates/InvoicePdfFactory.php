<?php
declare(strict_types=1);
namespace App\Templates;

use App\Database\Invoice;
use App\Database\InvoiceItem;
use App\Database\Company;

use App\Templates\InvoicePdf;

final class InvoicePdfFactory{

    CONST FONT = InvoicePdf::FONT;
    CONST MARGIN = 15;
    CONST CONTENT_WIDTH = 180;
    CONST BOTTOM_LIMIT = 25;

    // colors - slate palette + app blue
    CONST COLOR_TEXT = [30, 41, 59];
    CONST COLOR_MUTED = [100, 116, 139];
    CONST COLOR_LIGHT = [148, 163, 184];
    CONST COLOR_BORDER = [226, 232, 240];
    CONST COLOR_BACKGROUND = [248, 250, 252];
    CONST COLOR_ACCENT = [37, 99, 235];
    CONST COLOR_WHITE = [255, 255, 255];

    // items table columns: title, width, align
    CONST ITEM_COLUMNS = [
	['Code', 22, 'L'],
	['Description', 46, 'L'],
	['Qty', 12, 'R'],
	['Unit price', 24, 'R'],
	['Discount', 16, 'R'],
	['VAT', 12, 'R'],
	['Excl. VAT', 24, 'R'],
	['Incl. VAT', 24, 'R'],
    ];

    public function createInvoice(Invoice $invoice, ?Company $company): InvoicePdf {

	$pdf = new InvoicePdf('P', 'mm', 'A4');
	// unicode fonts (tFPDF core fonts do not support UTF-8)
	$pdf->AddFont(self::FONT, '', 'DejaVuSansCondensed.ttf', true);
	$pdf->AddFont(self::FONT, 'B', 'DejaVuSansCondensed-Bold.ttf', true);

	$pdf->SetTitle('Invoice ' . $invoice->getNumber(), true);
	$pdf->SetAuthor($company?->getName() ?? '', true);
	$pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
	$pdf->SetAutoPageBreak(true, self::BOTTOM_LIMIT);
	$pdf->AliasNbPages();
	$pdf->setFooterText(implode('  ·  ', array_filter([$company?->getName(), $company?->getWebDomain(), $company?->getEmail()])));
	$pdf->AddPage();

	$currency = $invoice->getPriceList()?->getCurrency() ?? '';

	$this->drawHeader($pdf, $invoice, $company);
	$this->drawMeta($pdf, $invoice);
	$this->drawParties($pdf, $invoice, $company);
	$this->drawItems($pdf, $invoice, $currency);
	$this->drawSummary($pdf, $invoice, $currency);
	$this->drawPaymentDetails($pdf, $invoice, $company, $currency);

	return $pdf;
    }

    private function drawHeader(InvoicePdf $pdf, Invoice $invoice, ?Company $company): void{
	// accent stripe
	$this->setFillColor($pdf, self::COLOR_ACCENT);
	$pdf->Rect(0, 0, $pdf->GetPageWidth(), 3, 'F');

	$yStart = self::MARGIN;

	// supplier name
	$pdf->SetXY(self::MARGIN, $yStart);
	$pdf->SetFont(self::FONT, 'B', 15);
	$this->setTextColor($pdf, self::COLOR_TEXT);
	$pdf->Cell(110, 8, $company?->getName() ?? '', 0, 2);
	$pdf->SetFont(self::FONT, '', 9);
	$this->setTextColor($pdf, self::COLOR_MUTED);
	$pdf->Cell(110, 5, $company?->getWebDomain() ?? '', 0, 0);

	// title + number
	$pdf->SetXY(self::MARGIN + 110, $yStart);
	$pdf->SetFont(self::FONT, 'B', 22);
	$this->setTextColor($pdf, self::COLOR_ACCENT);
	$pdf->Cell(70, 10, 'INVOICE', 0, 2, 'R');
	$pdf->SetFont(self::FONT, '', 10);
	$this->setTextColor($pdf, self::COLOR_MUTED);
	$pdf->Cell(70, 5, 'No. ' . $invoice->getNumber(), 0, 0, 'R');

	$pdf->SetY($yStart + 22);
    }

    private function drawMeta(InvoicePdf $pdf, Invoice $invoice): void{
	$meta = [
	    'Issue date' => $invoice->getDocumentDate()->format('d. m. Y'),
	    'Due date' => $invoice->getDueDate()?->format('d. m. Y') ?? '–',
	    'Payment method' => $invoice->getPaymentMethod() ?: '–',
	    'Variable symbol' => $this->getVariableSymbol($invoice),
	];

	$gap = 4;
	$boxWidth = (self::CONTENT_WIDTH - $gap * (count($meta) - 1)) / count($meta);
	$yStart = $pdf->GetY();
	$x = self::MARGIN;

	foreach($meta as $label => $value){
	    $this->setFillColor($pdf, self::COLOR_BACKGROUND);
	    $pdf->Rect($x, $yStart, $boxWidth, 15, 'F');

	    $pdf->SetXY($x + 4, $yStart + 2.5);
	    $pdf->SetFont(self::FONT, '', 7);
	    $this->setTextColor($pdf, self::COLOR_MUTED);
	    $pdf->Cell($boxWidth - 8, 4, mb_strtoupper($label), 0, 2);
	    $pdf->SetFont(self::FONT, 'B', 10);
	    $this->setTextColor($pdf, self::COLOR_TEXT);
	    $pdf->Cell($boxWidth - 8, 6, $value, 0, 0);

	    $x += $boxWidth + $gap;
	}

	$pdf->SetY($yStart + 15 + 8);
    }

    private function drawParties(InvoicePdf $pdf, Invoice $invoice, ?Company $company): void{
	$invoiceCustomer = $invoice->getInvoiceCustomer();
	$billingAddress = $invoiceCustomer->getInvoiceCustomerBillingAddress();
	$deliveryAddress = $invoiceCustomer->getInvoiceCustomerDeliveryAddress();

	$parties = [];
	$parties[] = [
	    'title' => 'Supplier',
	    'name' => $company?->getName() ?? '',
	    'address' => [$company?->getStreet(), trim(($company?->getZip() ?? '') . ' ' . ($company?->getCity() ?? '')), $company?->getCountryISO()],
	    'details' => [
		'Company no.' => $company?->getCompanyNumber(),
		'VAT no.' => $company?->getVatNumber(),
		'Phone' => $company?->getPhone(),
		'Email' => $company?->getEmail(),
	    ],
	];
	$parties[] = [
	    'title' => 'Customer',
	    'name' => $invoiceCustomer->getName(),
	    'address' => [$billingAddress?->getStreet(), trim($billingAddress?->getZip() . ' ' . $billingAddress?->getCity()), $billingAddress?->getCountry()],
	    'details' => [
		'Company no.' => $invoiceCustomer->getCompanyNumber(),
		'VAT no.' => $invoiceCustomer->getVatNumber(),
		'Phone' => $invoiceCustomer->getPhone(),
		'Email' => $invoiceCustomer->getEmail(),
	    ],
	];
	if(!empty($deliveryAddress)){
	    $parties[] = [
		'title' => 'Delivery address',
		'name' => $invoiceCustomer->getName(),
		'address' => [$deliveryAddress->getStreet(), trim($deliveryAddress->getZip() . ' ' . $deliveryAddress->getCity()), $deliveryAddress->getCountry()],
		'details' => [],
	    ];
	}

	$gap = 8;
	$columnWidth = (self::CONTENT_WIDTH - $gap * (count($parties) - 1)) / count($parties);
	$yStart = $pdf->GetY();
	$yEnd = $yStart;
	$x = self::MARGIN;

	foreach($parties as $party){
	    $pdf->SetXY($x, $yStart);

	    // title with underline
	    $pdf->SetFont(self::FONT, 'B', 7);
	    $this->setTextColor($pdf, self::COLOR_ACCENT);
	    $pdf->Cell($columnWidth, 5, mb_strtoupper($party['title']), 0, 2);
	    $this->setDrawColor($pdf, self::COLOR_BORDER);
	    $pdf->Line($x, $pdf->GetY(), $x + $columnWidth, $pdf->GetY());
	    $pdf->SetXY($x, $pdf->GetY() + 2);

	    $pdf->SetFont(self::FONT, 'B', 10);
	    $this->setTextColor($pdf, self::COLOR_TEXT);
	    $pdf->MultiCell($columnWidth, 5, $party['name'], 0, 'L');

	    $pdf->SetFont(self::FONT, '', 9);
	    foreach(array_filter($party['address']) as $addressLine){
		$pdf->SetX($x);
		$pdf->MultiCell($columnWidth, 4.5, $addressLine, 0, 'L');
	    }

	    $details = array_filter($party['details']);
	    if(!empty($details)){
		$pdf->SetY($pdf->GetY() + 2);
		$pdf->SetFont(self::FONT, '', 8);
		foreach($details as $label => $value){
		    $pdf->SetX($x);
		    $this->setTextColor($pdf, self::COLOR_MUTED);
		    $pdf->Cell(20, 4, $label, 0, 0);
		    $this->setTextColor($pdf, self::COLOR_TEXT);
		    $pdf->Cell($columnWidth - 20, 4, $value, 0, 1);
		}
	    }

	    $yEnd = max($yEnd, $pdf->GetY());
	    $x += $columnWidth + $gap;
	}

	$pdf->SetY($yEnd + 10);
    }

    private function drawItems(InvoicePdf $pdf, Invoice $invoice, string $currency): void{
	$pdf->SetFont(self::FONT, '', 7);
	$this->setTextColor($pdf, self::COLOR_LIGHT);
	$pdf->Cell(self::CONTENT_WIDTH, 4, 'All amounts in ' . $currency, 0, 1, 'R');

	$this->drawItemsHeader($pdf);

	$lineHeight = 4.5;
	$padding = 2;
	$rowIndex = 0;
	/** @var InvoiceItem $invoiceItem */
	foreach($invoice->getInvoiceItems() as $invoiceItem){
	    $pdf->SetFont(self::FONT, '', 8);

	    $values = [
		$invoiceItem->getCatalogueCode(),
		$invoiceItem->getName(),
		(string)$invoiceItem->getQuantity(),
		$this->formatMoney((float)$invoiceItem->getPrice()),
		(float)$invoiceItem->getDiscount() > 0 ? $this->formatNumber((float)$invoiceItem->getDiscount()) . ' %' : '–',
		$this->formatNumber((float)$invoiceItem->getVATRateValue()) . ' %',
		$this->formatMoney((float)$invoiceItem->getTotalPrice()),
		$this->formatMoney((float)$invoiceItem->getTotalPriceWithVAT()),
	    ];

	    // row height according to the longest wrapped text
	    $linesCount = 1;
	    foreach(self::ITEM_COLUMNS as $index => [$title, $width, $align]){
		$linesCount = max($linesCount, $this->getLinesCount($pdf, $width, $values[$index]));
	    }
	    $rowHeight = $linesCount * $lineHeight + $padding * 2;

	    // page break with repeated header
	    if($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - self::BOTTOM_LIMIT){
		$pdf->AddPage();
		$this->drawItemsHeader($pdf);
		$pdf->SetFont(self::FONT, '', 8);
	    }

	    $yStart = $pdf->GetY();

	    // zebra background + bottom border
	    if($rowIndex % 2 === 1){
		$this->setFillColor($pdf, self::COLOR_BACKGROUND);
		$pdf->Rect(self::MARGIN, $yStart, self::CONTENT_WIDTH, $rowHeight, 'F');
	    }
	    $this->setDrawColor($pdf, self::COLOR_BORDER);
	    $pdf->Line(self::MARGIN, $yStart + $rowHeight, self::MARGIN + self::CONTENT_WIDTH, $yStart + $rowHeight);

	    $x = self::MARGIN;
	    foreach(self::ITEM_COLUMNS as $index => [$title, $width, $align]){
		$this->setTextColor($pdf, $index === 1 || $index === 7 ? self::COLOR_TEXT : self::COLOR_MUTED);
		$pdf->SetFont(self::FONT, $index === 7 ? 'B' : '', 8);
		$pdf->SetXY($x, $yStart + $padding);
		$pdf->MultiCell($width, $lineHeight, $values[$index], 0, $align);
		$x += $width;
	    }

	    $pdf->SetY($yStart + $rowHeight);
	    $rowIndex++;
	}

	$pdf->SetY($pdf->GetY() + 8);
    }

    private function drawItemsHeader(InvoicePdf $pdf): void{
	$this->setFillColor($pdf, self::COLOR_TEXT);
	$this->setTextColor($pdf, self::COLOR_WHITE);
	$pdf->SetFont(self::FONT, 'B', 7.5);

	foreach(self::ITEM_COLUMNS as [$title, $width, $align]){
	    $pdf->Cell($width, 8, $title, 0, 0, $align, true);
	}
	$pdf->Ln();
    }

    private function drawSummary(InvoicePdf $pdf, Invoice $invoice, string $currency): void{
	// VAT recapitulation from items
	$vatRates = [];
	/** @var InvoiceItem $invoiceItem */
	foreach($invoice->getInvoiceItems() as $invoiceItem){
	    $rate = $this->formatNumber((float)$invoiceItem->getVATRateValue());
	    $vatRates[$rate] ??= ['base' => 0.0, 'total' => 0.0];
	    $vatRates[$rate]['base'] += (float)$invoiceItem->getTotalPrice();
	    $vatRates[$rate]['total'] += (float)$invoiceItem->getTotalPriceWithVAT();
	}
	ksort($vatRates, SORT_NUMERIC);

	$recapHeight = 6 + 6 * count($vatRates);
	$totalsHeight = 6 + 6 + 14;
	if($pdf->GetY() + max($recapHeight, $totalsHeight) > $pdf->GetPageHeight() - self::BOTTOM_LIMIT){
	    $pdf->AddPage();
	}

	$yStart = $pdf->GetY();

	// left: VAT recapitulation
	$recapColumns = [['VAT rate', 18, 'L'], ['Base', 24, 'R'], ['VAT', 22, 'R'], ['Total', 24, 'R']];
	$pdf->SetFont(self::FONT, 'B', 7);
	$this->setTextColor($pdf, self::COLOR_MUTED);
	foreach($recapColumns as [$title, $width, $align]){
	    $pdf->Cell($width, 6, mb_strtoupper($title), 'B', 0, $align);
	}
	$pdf->Ln();

	$pdf->SetFont(self::FONT, '', 8);
	$this->setTextColor($pdf, self::COLOR_TEXT);
	foreach($vatRates as $rate => $sums){
	    $pdf->Cell(18, 6, $rate . ' %', 0, 0, 'L');
	    $pdf->Cell(24, 6, $this->formatMoney($sums['base']), 0, 0, 'R');
	    $pdf->Cell(22, 6, $this->formatMoney($sums['total'] - $sums['base']), 0, 0, 'R');
	    $pdf->Cell(24, 6, $this->formatMoney($sums['total']), 0, 1, 'R');
	}
	$yLeftEnd = $pdf->GetY();

	// right: totals
	$total = (float)$invoice->getTotal();
	$totalWithVAT = (float)$invoice->getTotalWithVAT();
	$xTotals = self::MARGIN + self::CONTENT_WIDTH - 80;

	$pdf->SetXY($xTotals, $yStart);
	$pdf->SetFont(self::FONT, '', 9);
	$this->setTextColor($pdf, self::COLOR_MUTED);
	$pdf->Cell(45, 6, 'Total excl. VAT', 0, 0);
	$this->setTextColor($pdf, self::COLOR_TEXT);
	$pdf->Cell(35, 6, $this->formatMoney($total) . ' ' . $currency, 0, 2, 'R');

	$pdf->SetX($xTotals);
	$this->setTextColor($pdf, self::COLOR_MUTED);
	$pdf->Cell(45, 6, 'VAT', 0, 0);
	$this->setTextColor($pdf, self::COLOR_TEXT);
	$pdf->Cell(35, 6, $this->formatMoney($totalWithVAT - $total) . ' ' . $currency, 0, 2, 'R');

	// total due box
	$yBox = $pdf->GetY() + 2;
	$this->setFillColor($pdf, self::COLOR_ACCENT);
	$pdf->Rect($xTotals, $yBox, 80, 12, 'F');
	$pdf->SetXY($xTotals + 4, $yBox);
	$this->setTextColor($pdf, self::COLOR_WHITE);
	$pdf->SetFont(self::FONT, '', 9);
	$pdf->Cell(30, 12, 'Total due', 0, 0);
	$pdf->SetFont(self::FONT, 'B', 13);
	$pdf->Cell(42, 12, $this->formatMoney($totalWithVAT) . ' ' . $currency, 0, 0, 'R');

	$pdf->SetY(max($yLeftEnd, $yBox + 12) + 10);
    }

    private function drawPaymentDetails(InvoicePdf $pdf, Invoice $invoice, ?Company $company, string $currency): void{
	$details = array_filter([
	    'Bank account' => $company?->getBankNumber(),
	    'Variable symbol' => $this->getVariableSymbol($invoice),
	    'Due date' => $invoice->getDueDate()?->format('d. m. Y'),
	    'Amount' => $this->formatMoney((float)$invoice->getTotalWithVAT()) . ' ' . $currency,
	]);

	$boxHeight = 8 + 5 * count($details);
	if($pdf->GetY() + $boxHeight > $pdf->GetPageHeight() - self::BOTTOM_LIMIT){
	    $pdf->AddPage();
	}

	$yStart = $pdf->GetY();
	$this->setFillColor($pdf, self::COLOR_BACKGROUND);
	$pdf->Rect(self::MARGIN, $yStart, 90, $boxHeight + 4, 'F');
	$this->setFillColor($pdf, self::COLOR_ACCENT);
	$pdf->Rect(self::MARGIN, $yStart, 1.2, $boxHeight + 4, 'F');

	$pdf->SetXY(self::MARGIN + 5, $yStart + 3);
	$pdf->SetFont(self::FONT, 'B', 7);
	$this->setTextColor($pdf, self::COLOR_ACCENT);
	$pdf->Cell(80, 5, 'PAYMENT DETAILS', 0, 2);

	$pdf->SetFont(self::FONT, '', 8.5);
	foreach($details as $label => $value){
	    $pdf->SetX(self::MARGIN + 5);
	    $this->setTextColor($pdf, self::COLOR_MUTED);
	    $pdf->Cell(28, 5, $label, 0, 0);
	    $this->setTextColor($pdf, self::COLOR_TEXT);
	    $pdf->Cell(52, 5, $value, 0, 2);
	}

	$pdf->SetXY(self::MARGIN + 100, $yStart + 3);
	$pdf->SetFont(self::FONT, '', 8.5);
	$this->setTextColor($pdf, self::COLOR_MUTED);
	$pdf->MultiCell(80, 5, 'Thank you for your business. Please use the variable symbol when paying.', 0, 'L');
    }

    private function getVariableSymbol(Invoice $invoice): string{
	// numeric part of the invoice number
	return preg_replace('/\D/', '', $invoice->getNumber()) ?: $invoice->getNumber();
    }

    private function getLinesCount(InvoicePdf $pdf, float $width, string $text): int{
	// MultiCell keeps 1 mm inner margin on both sides
	$maxWidth = $width - 2;
	$linesCount = 0;

	foreach(explode("\n", $text) as $paragraph){
	    $linesCount++;
	    $line = '';
	    foreach(explode(' ', $paragraph) as $word){
		$candidate = $line === '' ? $word : $line . ' ' . $word;
		if($line !== '' && $pdf->GetStringWidth($candidate) > $maxWidth){
		    $linesCount++;
		    $line = $word;
		} else {
		    $line = $candidate;
		}
	    }
	}

	return $linesCount;
    }

    private function formatMoney(float $value): string{
	return number_format($value, 2, '.', ' ');
    }

    private function formatNumber(float $value): string{
	return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function setTextColor(InvoicePdf $pdf, array $color): void{
	$pdf->SetTextColor(...$color);
    }

    private function setFillColor(InvoicePdf $pdf, array $color): void{
	$pdf->SetFillColor(...$color);
    }

    private function setDrawColor(InvoicePdf $pdf, array $color): void{
	$pdf->SetDrawColor(...$color);
    }
}
