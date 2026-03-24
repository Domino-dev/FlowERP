<?php
declare(strict_types=1);
namespace App\Templates;

use App\Database\Invoice;
use App\Database\Company;

final class InvoicePdfFactory{
    
    
    public function createInvoice(Invoice $invoice, ?Company $company): \tFPDF {
	/** @var \App\Database\Invoice $invoice */
        $pdf = new \tFPDF;
        $pdf->AddPage();
        //$pdf->Image('logo',10,12,25,0);
        $pdf->SetFont('Helvetica', '', 14);
	$pdf->SetTextColor(100,100,100);

	$pdf->SetFont('Helvetica', 'B', 24);
	$pdf->SetTextColor(50,50,50);
	$pdf->Cell(0,16,'INVOICE - '.$invoice->getNumber(),0,1);

	/* dates */

	$pdf->SetFont('Helvetica','B',12);
	$pdf->SetTextColor(50,50,50);
	$pdf->Cell(50,5,'Invoice date:',0,0);

	$pdf->SetFont('Helvetica','',12);
	$pdf->SetTextColor(75,75,75);
	$pdf->Cell(40,5,$invoice->getDocumentDate()->format('d. m. Y'),0,0);

	$pdf->Cell(10,5,'',0,0);

	$pdf->SetFont('Helvetica','B',12);
	$pdf->SetTextColor(50,50,50);

	$pdf->SetFont('Helvetica','',12);
	$pdf->SetTextColor(75,75,75);
	$pdf->Cell(40,5,'',0,1,'R');


	$pdf->SetFont('Helvetica','B',12);
	$pdf->SetTextColor(50,50,50);
	$pdf->Cell(50,5,$invoice->getDueDate() instanceof \DateTimeImmutable
		? 'Due date: '
		: '',0,0);

	$pdf->SetFont('Helvetica','',12);
	$pdf->SetTextColor(75,75,75);
	$pdf->Cell(40,5,
	    $invoice->getDueDate() instanceof \DateTimeImmutable
		? $invoice->getDueDate()->format('d. m. Y')
		: null,
	0,0);

	$pdf->Cell(10,5,'',0,0);

	$pdf->SetFont('Helvetica','B',12);
	$pdf->SetTextColor(50,50,50);

	$pdf->SetFont('Helvetica','',12);
	$pdf->SetTextColor(75,75,75);
	$pdf->Cell(40,5,'',0,1,'R');

	$pdf->Cell(0,5,'',0,1);

	/* ADDRESS BLOCK */

	$colWidth = 60;
	$gap = 5;
	$lineHeight = 4; // výška řádku pro MultiCell

	$pdf->SetFont('Helvetica','B',10);
	$pdf->SetTextColor(50,50,50);

	// Nadpisy
	$pdf->Cell($colWidth,5,'From:',0,0);
	$pdf->Cell($gap,5,'',0,0);
	$pdf->Cell($colWidth,5,'Invoice address:',0,0);
	$pdf->Cell($gap,5,'',0,0);
	$pdf->Cell($colWidth,5, $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress() ? 'Delivery address:' : '',0,1);

	$pdf->SetFont('Helvetica','',10);
	$pdf->SetTextColor(125,125,125);

	/* --- ROW 1: Name --- */
	$yStart = $pdf->GetY();
	$xFrom = $pdf->GetX();
	$xInvoice = $xFrom + $colWidth + $gap;
	$xDelivery = $xInvoice + $colWidth + $gap;

	// From
	$pdf->MultiCell($colWidth, $lineHeight, $company?->getName() ?? '', 0, 'L');

	// Invoice
	$pdf->SetXY($xInvoice, $yStart);
	$pdf->MultiCell($colWidth, $lineHeight, $invoice->getInvoiceCustomer()->getName(), 0, 'L');

	// Delivery
	$pdf->SetXY($xDelivery, $yStart);
	$pdf->MultiCell(
	    $colWidth,
	    $lineHeight,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()
		? $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getName()
		: '',
	0, 'L');

	// posun pod nejvyšší MultiCell
	$pdf->SetY($yStart + max(
	    ceil($pdf->GetStringWidth($company?->getName() ?? '') / $colWidth) * $lineHeight,
	    ceil($pdf->GetStringWidth($invoice->getInvoiceCustomer()->getName()) / $colWidth) * $lineHeight,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()
		? ceil($pdf->GetStringWidth($invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getName()) / $colWidth) * $lineHeight
		: 0
	));

	/* --- ROW 2: Street --- */
	$yStart = $pdf->GetY();

	// From street
	$pdf->SetXY($xFrom, $yStart);
	$pdf->MultiCell($colWidth, $lineHeight, $company?->getStreet() ?? '', 0, 'L');

	// Invoice street
	$pdf->SetXY($xInvoice, $yStart);
	$pdf->MultiCell(
	    $colWidth,
	    $lineHeight,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerBillingAddress()->getStreet(),
	0, 'L');

	// Delivery street
	$pdf->SetXY($xDelivery, $yStart);
	$pdf->MultiCell(
	    $colWidth,
	    $lineHeight,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()
		? $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getStreet()
		: '',
	0, 'L');

	// posun pod nejvyšší blok
	$pdf->SetY($yStart + max(
	    ceil($pdf->GetStringWidth($company?->getStreet() ?? '') / $colWidth) * $lineHeight,
	    ceil($pdf->GetStringWidth($invoice->getInvoiceCustomer()->getInvoiceCustomerBillingAddress()->getStreet()) / $colWidth) * $lineHeight,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()
		? ceil($pdf->GetStringWidth($invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getStreet()) / $colWidth) * $lineHeight
		: 0
	));

	/* --- ROW 3: City --- */
	$pdf->Cell($colWidth,4,$company?->getCity() ?? '',0,0);
	$pdf->Cell($gap,4,'',0,0);
	$pdf->Cell($colWidth,4,$invoice->getInvoiceCustomer()->getInvoiceCustomerBillingAddress()->getCity(),0,0);
	$pdf->Cell($gap,4,'',0,0);
	$pdf->Cell(
	    $colWidth,
	    4,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()
		? $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getCity()
		: '',
	0,1);

	/* --- ROW 4: ZIP --- */
	$pdf->Cell($colWidth,4,$company?->getZip() ?? '',0,0);
	$pdf->Cell($gap,4,'',0,0);
	$pdf->Cell($colWidth,4,$invoice->getInvoiceCustomer()->getInvoiceCustomerBillingAddress()->getZip(),0,0);
	$pdf->Cell($gap,4,'',0,0);
	$pdf->Cell(
	    $colWidth,
	    4,
	    $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()
		? $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getZip()
		: '',
	0,1);

	$pdf->Cell(0,5,'',0,1);

	/* CONTACT BLOCK */
	$pdf->SetFont('Helvetica','',8);

	$leftCol = 60;
	$middleGap = 5;
	$rightCol = 60;

	// Company number row
	$pdf->Cell($leftCol,3,'Company no: '.$company?->getCompanyNumber() ?? '',0,0);
	$pdf->Cell($middleGap,3,'',0,0);
	$pdf->Cell($rightCol,3,!empty($invoice->getInvoiceCustomer()->getCompanyNumber()) ? 'Company no: ' . $invoice->getInvoiceCustomer()->getCompanyNumber() : '',0,1);
	
	// VAT row
	$pdf->Cell($leftCol,3,'VAT no: '.$company?->getVatNumber() ?? '',0,0);
	$pdf->Cell($middleGap,3,'',0,0);
	$pdf->Cell($rightCol,3,!empty($invoice->getInvoiceCustomer()->getVatNumber()) ? 'VAT no: ' . $invoice->getInvoiceCustomer()->getVatNumber() : '',0,1);

	// Tel row
	$pdf->Cell($leftCol,3,'Tel: '.$company?->getPhone() ?? '',0,0);
	$pdf->Cell($middleGap,3,'',0,0);
	$pdf->Cell($rightCol,3,'Tel: ' . $invoice->getInvoiceCustomer()->getPhone(),0,1);

	// Email row
	$pdf->Cell($leftCol,3,'mail: '.$company?->getEmail() ?? '',0,0);
	$pdf->Cell($middleGap,3,'',0,0);
	$pdf->Cell($rightCol,3,'mail: ' . $invoice->getInvoiceCustomer()->getEmail(),0,1);

	$pdf->Cell(0,6,'',0,1);
	
	$pdf->Cell($leftCol,3,'',0,0);
	$pdf->Cell($middleGap,3,'',0,0);
	$pdf->Cell($rightCol,3,'Payment method: ' . $invoice->getPaymentMethod(),0,1);

	$pdf->Cell(0,6,'',0,1);
        
        /*if(!empty($invoice['formNote']['invoiceNote'])) {
            $pdf->Cell(0,5,'Note: ',0,1);
            $pdf->Cell(0,3,'',0,1);
        }
        
        $pdf->SetFont('Helvetica','B',10);
        $pdf->SetTextColor(50,50,50);
        $pdf->SetFillColor(200,200,200);
        if(empty($invoice['title'])) {
            $pdf->Cell(0,10,' Invoice articles',0,1,'',true);
        } else {
            $pdf->Cell(0,10,' ' . $invoice['title'],0,1,'',true);
        }*/
        $pdf->Cell(0,3,'',0,1);
        
        $pdf->SetFillColor(255,255,255);
	$pdf->SetTextColor(125,125,125);
	$pdf->SetFont('Helvetica','B',8);

	/* Header */
	$pdf->Cell(20,8,'Cat. code','B',0);
	$pdf->Cell(40,8,'Name','B',0);
	$pdf->Cell(15,8,'Price','B',0,'R');
	$pdf->Cell(10,8,'VAT','B',0,'R');
	$pdf->Cell(15,8,'Discount','B',0,'R');
	$pdf->Cell(15,8,'Quantity','B',0,'R');
	$pdf->Cell(37,8,'Total w/o VAT','B',0,'R');
	$pdf->Cell(37,8,'Total with VAT','B',1,'R');

	/* Items */
	foreach ($invoice->getInvoiceItems() as $invoiceItem) {

	    $pdf->SetFont('Helvetica','',7);

	    $xStart = $pdf->GetX();
	    $yStart = $pdf->GetY();

	    // výška řádku podle názvu
	    $nameWidth = 40;
	    $lineHeight = 4;

	    $name = $invoiceItem->getName();
	    $nbLines = max(1, ceil($pdf->GetStringWidth($name) / $nameWidth));
	    $rowHeight = $nbLines * $lineHeight;

	    /* Cat code */
	    $pdf->Cell(20,$rowHeight,$invoiceItem->getCatalogueCode(),0,0);

	    /* Name */
	    $xCurrent = $pdf->GetX();
	    $yCurrent = $pdf->GetY();

	    $pdf->MultiCell($nameWidth,$lineHeight,$name,0,'L');

	    $pdf->SetXY($xCurrent + $nameWidth,$yCurrent);

	    /* Price */
	    $pdf->Cell(15,$rowHeight,
		number_format($invoiceItem->getPrice(),2,'.',' ') . ' ' .
		$invoice->getPriceList()->getCurrency(),
		0,0,'R'
	    );

	    /* VAT */
	    $pdf->Cell(10,$rowHeight,$invoiceItem->getVATRateValue(),0,0,'R');

	    /* Discount */
	    $pdf->Cell(15,$rowHeight,$invoiceItem->getDiscount(),0,0,'R');

	    /* Quantity */
	    $pdf->Cell(15,$rowHeight,$invoiceItem->getQuantity(),0,0,'R');

	    /* Total without VAT */
	    $pdf->Cell(37,$rowHeight,number_format($invoiceItem->getTotalPrice(),2,'.',' ') . ' ' .
		$invoice->getPriceList()->getCurrency(),0,0,'R');

	    /* Total with VAT */
	    $pdf->Cell(37,$rowHeight,number_format($invoiceItem->getTotalPriceWithVAT(),2,'.',' ') . ' ' .
		$invoice->getPriceList()->getCurrency(),0,1,'R');
	}
        
        
        /* Summary */
        $pdf->Cell(0,14,'',0,1);
        $pdf->SetTextColor(125,125,125);
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(90,5,'Account details:',0,1);
        
        
        $pdf->SetTextColor(125,125,125);
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(90,5,$company?->getName() ?? '',0,0);
        $pdf->Cell(10,5,'',0,0);
        $pdf->SetTextColor(50,50,50);
        $pdf->SetFont('Helvetica','B',10);
        $pdf->Cell(40,5,'TOTAL ex VAT',0,0);
        $pdf->Cell(10,5,'',0,0);
	
        $pdf->Cell(40,5,number_format(floatval($invoice->getTotal()),2,"."," ").' '.$invoice->getPriceList()->getCurrency(),0,1,'R');
        
        
        $pdf->SetTextColor(125,125,125);
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(90,5,'Account no: '.$company?->getBankNumber() ?? '',0,0);
        
        $pdf->SetTextColor(125,125,125);
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(10,5,'',0,0);
        $pdf->SetTextColor(50,50,50);
        $pdf->SetFont('Helvetica','B',10);
        $pdf->Cell(40,5,'TOTAL inc. VAT',0,0);
        $pdf->Cell(10,5,'',0,0);
        $pdf->Cell(40,5,number_format(floatval($invoice->getTotalWithVAT()),2,"."," ").' '.$invoice->getPriceList()->getCurrency(),0,1,'R');
	
	$pdf->SetTextColor(125,125,125);
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(90,5,'Variable symbol or memo: '.$invoice->getNumber(),0,1);
	
        return $pdf;
    }
}