<?php
declare(strict_types=1);
namespace App\Templates;

class InvoicePdf extends \tFPDF {

    CONST FONT = 'DejaVu';

    private string $footerText = '';

    public function setFooterText(string $footerText): void {
	$this->footerText = $footerText;
    }

    public function Footer(): void {
	$this->SetY(-15);

	$this->SetDrawColor(226, 232, 240);
	$this->SetLineWidth(0.2);
	$this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());

	$this->SetY($this->GetY() + 2);
	$this->SetFont(self::FONT, '', 7);
	$this->SetTextColor(148, 163, 184);
	$this->Cell(0, 4, $this->footerText, 0, 0, 'L');
	$this->Cell(0, 4, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');
    }
}
