<?php

namespace App\Services;

use App\EstimateTicket;

class EstimatePdfService
{
    protected $commands = [];
    protected $logoJpeg;
    protected $logoWidth = 790;
    protected $logoHeight = 389;
    protected $isAlMassa = false;
    protected $primary = '87C112';
    protected $primaryDark = '4D7C0F';
    protected $brandName = 'MY BOX PRINTING';

    public function generate(EstimateTicket $ticket)
    {
        $this->commands = [];
        $ticket->loadMissing(['workspace', 'lead', 'estimator', 'options']);
        $workspace = $ticket->workspace ?: \App\CrmWorkspace::find($ticket->workspace_id);
        $this->isAlMassa = $workspace && $workspace->slug === 'mybox-packaging-app';
        $this->primary = $this->isAlMassa ? 'E9B21B' : '87C112';
        $this->primaryDark = $this->isAlMassa ? '06265E' : '23410A';
        $this->brandName = $this->isAlMassa
            ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC'
            : 'MY BOX PRINTING';
        $this->logoJpeg = $this->prepareLogo();

        $navy = $this->primaryDark;
        $gold = $this->primary;
        $lightBlue = $this->isAlMassa ? 'CBE7F5' : 'EAF4DF';
        $muted = '5E6673';
        $currency = $ticket->currency ?: 'AED';
        $vatPercentage = (float) (optional($ticket->lead)->vat_percentage ?? 5);

        // Header follows the supplied Al Massa invoice: logo and company identity
        // on the left, document title on the right, finished with navy accents.
        if ($this->logoJpeg) {
            if ($this->isAlMassa) {
                $this->image(42, 719, 96, 96);
            } else {
                $this->image(42, 745, 132, 65);
            }
        } else {
            $this->text(42, 738, 18, $this->brandName, true, $navy);
        }
        if ($this->isAlMassa) {
            $this->text(160, 799, 13, $this->brandName, true, $navy);
            $this->text(160, 779, 9, 'All Cosmetics & Perfumes Hard, Soft Boxes and Paper Bags', false, '27364B');
            $this->text(160, 759, 9, 'Al Diyar Building 33, 4th Industrial Street, Industrial Area 12,', false, '27364B');
            $this->text(160, 745, 9, 'Sharjah, United Arab Emirates', false, '27364B');
            $this->text(160, 725, 9, '+971 56 682 0097  |  +971 56 837 0097', false, '27364B');
            $this->text(160, 707, 9, 'info@almassapackaging.com', false, '27364B');
        } else {
            $this->text(190, 745, 14, $this->brandName, true, $navy);
            $this->text(190, 724, 9, 'Custom Packaging Solutions', false, '27364B');
            $this->text(190, 704, 9, 'www.myboxprinting.com  |  support@myboxprinting.com', false, '27364B');
        }
        $this->text(553, 727, 22, 'ESTIMATE', true, $gold, 'right');
        $this->line(42, 687, 420, 687, '9AA0A6', 1.1);
        $this->line(42, 687, 88, 687, $navy, 3);

        // Client and estimate metadata.
        $this->text(42, 650, 9, 'Estimate for:', false, '343A40');
        $this->fittedText(42, 624, 15, $ticket->client_name ?: 'Valued Client', true, '111827', 330);
        $this->fittedText(42, 604, 9, $ticket->client_email ?: '', false, $muted, 330);
        if (trim((string) optional($ticket->lead)->shipping_address) !== '') {
            $this->fittedText(42, 586, 9, optional($ticket->lead)->shipping_address, false, $muted, 330);
        }
        $metaValueX = 475;
        $this->text(410, 646, 9, 'Estimate No:', true, '111827');
        $this->text($metaValueX, 646, 9, $ticket->ticket_number, false, '111827');
        $this->text(410, 626, 9, 'Currency:', true, '111827');
        $this->text($metaValueX, 626, 9, $currency, false, '111827');
        $this->text(410, 606, 9, 'Dated:', true, '111827');
        $this->text($metaValueX, 606, 9, now()->format('d F Y'), false, '111827');
        $this->text(410, 586, 9, 'VAT:', true, '111827');
        $this->text($metaValueX, 586, 9, number_format($vatPercentage, 2).'% (included)', false, '111827');

        // Estimate content occupies the center while the invoice-style frame stays.
        $this->text(42, 550, 9, 'PROJECT DETAILS', true, $navy);
        $this->line(42, 540, 553, 540, $gold, 1.2);
        $specs = [
            ['Product', $ticket->product_style], ['Printing', $ticket->printing],
            ['Finish Size', trim(($ticket->finish_size ?: 'N/A').' '.(optional($ticket->lead)->unit ?: $ticket->unit))],
            ['Open / Flat Size', trim(($ticket->flat_size ?: 'N/A').' '.(optional($ticket->lead)->unit ?: $ticket->unit))],
            ['Stock', $ticket->stock], ['Finishing', $this->finishingSummary($ticket)],
        ];
        foreach ($specs as $i => $spec) {
            $col = $i % 2;
            $row = intdiv($i, 2);
            $x = 42 + ($col * 272);
            $y = 518 - ($row * 42);
            $this->text($x, $y, 7, strtoupper($spec[0]), true, '687386');
            if ($spec[0] === 'Finishing') {
                $this->wrappedText($x, $y - 17, 9, $spec[1] ?: 'Standard finish', false, '20252B', 244, 2, 12);
            } else {
                $this->fittedText($x, $y - 17, 9, $spec[1] ?: 'N/A', true, '20252B', 244);
            }
        }

        $tableTop = 354;
        $this->fillRect(42, $tableTop, 511, 28, $navy);
        $columnCenters = [93, 195, 297, 399, 501];
        $this->text($columnCenters[0], $tableTop + 9, 8, 'NO', true, 'FFFFFF', 'center');
        $this->text($columnCenters[1], $tableTop + 9, 8, 'QUANTITY', true, 'FFFFFF', 'center');
        $this->text($columnCenters[2], $tableTop + 9, 8, 'PRICE PER UNIT', true, 'FFFFFF', 'center');
        $this->text($columnCenters[3], $tableTop + 9, 8, 'VAT', true, 'FFFFFF', 'center');
        $this->text($columnCenters[4], $tableTop + 9, 8, 'TOTAL', true, 'FFFFFF', 'center');
        $y = $tableTop - 28;
        $salesOffers = collect((array) optional($ticket->lead)->estimate_quantity_options)
            ->keyBy(function ($offer) {
                return (int) ($offer['quantity'] ?? 0);
            });
        foreach ($ticket->options->take(5) as $index => $option) {
            $savedSalesOffer = $salesOffers->get((int) $option->quantity);
            $total = is_array($savedSalesOffer) && array_key_exists('price', $savedSalesOffer)
                ? (float) $savedSalesOffer['price']
                : ($option->offer_price !== null
                    ? (float) $option->offer_price
                    : ($option->discounted_price !== null ? (float) $option->discounted_price : (float) $option->total_price));
            $unit = $option->quantity > 0 ? $total / $option->quantity : 0;
            $vatAmount = $vatPercentage > 0
                ? $total - ($total / (1 + ($vatPercentage / 100)))
                : 0;
            $this->fillRect(42, $y, 511, 27, $lightBlue);
            $this->text($columnCenters[0], $y + 9, 9, $index + 1, false, '172033', 'center');
            $this->text($columnCenters[1], $y + 9, 9, number_format($option->quantity).' pcs', true, '172033', 'center');
            $this->text($columnCenters[2], $y + 9, 9, number_format($unit, 4), false, '172033', 'center');
            $this->text($columnCenters[3], $y + 9, 8, number_format($vatAmount, 2), false, '172033', 'center');
            $this->text($columnCenters[4], $y + 9, 9, number_format($total, 2), true, '172033', 'center');
            $this->line(42, $y, 553, $y, 'FFFFFF', .8);
            $y -= 28;
        }

        $termsY = max(152, $y - 18);
        $this->text(42, $termsY, 8, 'Estimate Notes', true, $navy);
        $this->fittedText(42, $termsY - 17, 8, $ticket->team_lead_notes ?: $ticket->estimator_notes ?: 'Prices are subject to final artwork and production review.', false, $muted, 330);

        // Invoice-style footer and sign-off.
        $preparedBy = optional($ticket->estimator)->name ?: 'Estimator';
        $this->text(553, 112, 12, strtoupper($preparedBy), true, '111827', 'right');
        $this->text(553, 94, 9, 'Estimator', false, '343A40', 'right');
        $this->text(306, 60, 10, 'Thank you for business with us!', true, '111827', 'center');
        $this->line(42, 40, 553, 40, 'A4A4A4', 1);
        $this->line(42, 40, 88, 40, $navy, 3);
        $this->line(509, 40, 553, 40, $navy, 3);

        return $this->buildPdf(implode("\n", $this->commands));
    }

    protected function finishingSummary(EstimateTicket $ticket)
    {
        $customSpecs = optional($ticket->lead)->custom_specs;
        $options = is_array($customSpecs) ? (array) ($customSpecs['Finishing Options'] ?? []) : [];
        return $options ? implode(', ', $options) : 'Standard finish';
    }

    protected function prepareLogo()
    {
        // Some deployments point public_path() at public_html while the Laravel
        // repository (and this PDF asset) lives in the application's public dir.
        // Check both layouts so the PDF does not silently fall back to plain text.
        $filename = $this->isAlMassa ? 'al-massa-packaging-logo-pdf.jpg' : 'my-box-printing-logo-pdf.jpg';
        $paths = array_unique([
            __DIR__.'/Assets/'.$filename,
            public_path($filename),
            base_path('public/'.$filename),
            base_path('public_html/'.$filename),
            base_path($filename),
            dirname(base_path()).'/public_html/'.$filename,
        ]);
        $path = null;
        foreach ($paths as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $path = $candidate;
                break;
            }
        }
        if (!$path) {
            if (!$this->isAlMassa) {
                $embeddedLogo = base64_decode(EstimateLogoAsset::JPEG_BASE64, true);
                if ($embeddedLogo !== false) {
                    $this->logoWidth = 790;
                    $this->logoHeight = 389;
                    return $embeddedLogo;
                }
            }
            // Keep PDF generation available with a text masthead when a
            // deployment is missing its optional workspace logo asset.
            return null;
        }

        $dimensions = @getimagesize($path);
        if ($dimensions) {
            $this->logoWidth = $dimensions[0];
            $this->logoHeight = $dimensions[1];
        }
        return file_get_contents($path);
    }

    protected function image($x, $y, $width, $height)
    {
        $this->commands[] = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /Im1 Do Q', $width, $height, $x, $y);
    }

    protected function text($x, $y, $size, $text, $bold = false, $color = '000000', $align = 'left')
    {
        $text = $this->safe($text); $width = strlen($text) * $size * .52;
        if ($align === 'right') $x -= $width;
        if ($align === 'center') $x -= $width / 2;
        [$r,$g,$b] = $this->rgb($color);
        $this->commands[] = sprintf('BT %.3F %.3F %.3F rg /%s %d Tf %.2F %.2F Td (%s) Tj ET', $r,$g,$b,$bold?'F2':'F1',$size,$x,$y,$text);
    }

    protected function fittedText($x, $y, $size, $text, $bold, $color, $maxWidth)
    {
        $text = (string) $text;
        while ($size > 7 && strlen($text) * $size * .52 > $maxWidth) {
            $size--;
        }
        if (strlen($text) * $size * .52 > $maxWidth) {
            $maxChars = max(4, (int) floor($maxWidth / ($size * .52)) - 3);
            $text = substr($text, 0, $maxChars) . '...';
        }
        $this->text($x, $y, $size, $text, $bold, $color);
    }

    protected function wrappedText($x, $y, $size, $text, $bold, $color, $maxWidth, $maxLines = 2, $lineHeight = 12)
    {
        $maxChars = max(8, (int) floor($maxWidth / ($size * .52)));
        $allLines = explode("\n", wordwrap(trim((string) $text), $maxChars, "\n", true));
        $lines = array_slice($allLines, 0, $maxLines);
        if (count($allLines) > $maxLines && $lines) {
            $last = count($lines) - 1;
            while (strlen($lines[$last].'...') * $size * .52 > $maxWidth && strlen($lines[$last]) > 3) {
                $lines[$last] = substr($lines[$last], 0, -1);
            }
            $lines[$last] = rtrim($lines[$last], " ,").'...';
        }

        foreach (array_slice($lines, 0, $maxLines) as $index => $value) {
            $this->text($x, $y - ($index * $lineHeight), $size, $value, $bold, $color);
        }
    }

    protected function fillRect($x, $y, $w, $h, $color)
    {
        [$r,$g,$b] = $this->rgb($color);
        $this->commands[] = sprintf('%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f', $r,$g,$b,$x,$y,$w,$h);
    }

    protected function line($x1,$y1,$x2,$y2,$color,$width)
    {
        [$r,$g,$b] = $this->rgb($color);
        $this->commands[] = sprintf('%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S',$r,$g,$b,$width,$x1,$y1,$x2,$y2);
    }

    protected function rgb($hex)
    {
        return [hexdec(substr($hex,0,2))/255,hexdec(substr($hex,2,2))/255,hexdec(substr($hex,4,2))/255];
    }

    protected function safe($text)
    {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string)$text);
        return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)',' ',' '], $text);
    }

    protected function buildPdf($stream)
    {
        $imageResource = $this->logoJpeg ? ' /XObject << /Im1 7 0 R >>' : '';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 5 0 R /F2 6 0 R >>'.$imageResource.' >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream).' >>'."\nstream\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        if ($this->logoJpeg) {
            $objects[] = '<< /Type /XObject /Subtype /Image /Width '.$this->logoWidth.' /Height '.$this->logoHeight.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($this->logoJpeg).' >>'."\nstream\n".$this->logoJpeg."\nendstream";
        }
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i+1)." 0 obj\n{$object}\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets,1) as $offset) $pdf .= sprintf("%010d 00000 n \n",$offset);
        return $pdf."trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
