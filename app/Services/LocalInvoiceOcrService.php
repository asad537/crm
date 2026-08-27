<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Extracts invoice text without sending the supplier document to a third party.
 *
 * This uses the server's Tesseract installation. Text PDFs are read directly
 * with pdftotext; scanned PDFs are rendered with pdftoppm and then OCR'd.
 */
class LocalInvoiceOcrService
{
    public function extract(UploadedFile $file)
    {
        $text = $this->readText($file);

        if (mb_strlen(trim($text)) < 12) {
            throw new \RuntimeException('No readable invoice text was found. Please upload a clearer PDF or image.');
        }

        return $this->mapInvoice($text);
    }

    private function readText(UploadedFile $file)
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            $this->requireCommand('pdftotext', 'PDF text reader');
            $text = $this->run('pdftotext -layout '.escapeshellarg($path).' -');
            if (mb_strlen(trim($text)) >= 12) return $text;

            $this->requireCommand('pdftoppm', 'scanned-PDF renderer');
            $this->requireCommand('tesseract', 'local OCR engine');
            $directory = storage_path('app/tmp-invoice-ocr-'.uniqid('', true));
            if (!@mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new \RuntimeException('Unable to prepare the local OCR workspace.');
            }
            try {
                $prefix = $directory.'/page';
                $this->run('pdftoppm -f 1 -l 3 -r 200 -jpeg '.escapeshellarg($path).' '.escapeshellarg($prefix));
                $pages = glob($prefix.'-*.jpg') ?: [];
                foreach ($pages as $page) {
                    $text .= "\n".$this->run('tesseract '.escapeshellarg($page).' stdout -l eng 2>/dev/null');
                }
            } finally {
                foreach (glob($directory.'/*') ?: [] as $temporaryFile) @unlink($temporaryFile);
                @rmdir($directory);
            }
            return $text;
        }

        $this->requireCommand('tesseract', 'local OCR engine');
        return $this->run('tesseract '.escapeshellarg($path).' stdout -l eng 2>/dev/null');
    }

    private function requireCommand($command, $label)
    {
        $result = trim($this->run('command -v '.escapeshellarg($command).' 2>/dev/null', true));
        if ($result === '') {
            throw new \RuntimeException('Local OCR is not available because the '.$label.' ('.$command.') is not installed on this server. Ask hosting to enable Tesseract, pdftotext and pdftoppm.');
        }
    }

    private function run($command, $allowFailure = false)
    {
        if (!function_exists('exec')) {
            throw new \RuntimeException('Local OCR is disabled because PHP command execution is not available on this server.');
        }
        $output = [];
        $status = 0;
        exec($command, $output, $status);
        if ($status !== 0 && !$allowFailure) {
            throw new \RuntimeException('Local OCR could not process this document.');
        }
        return implode("\n", $output);
    }

    private function mapInvoice($text)
    {
        $invoice = ['items' => []];
        $currency = $this->firstMatch('/\b(AED|USD|EUR|GBP|PKR)\b/i', $text);
        if ($currency) $invoice['currency'] = strtoupper($currency);

        $invoiceNumber = $this->firstMatch('/(?:invoice\s*(?:no\.?|number|#)?|inv\s*(?:no\.?|#))\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/-]{2,})/i', $text, 1);
        if ($invoiceNumber) $invoice['invoice_number'] = $invoiceNumber;

        $date = $this->firstMatch('/(?:invoice\s*)?date\s*[:#-]?\s*([0-3]?\d[\/.\-][01]?\d[\/.\-](?:20)?\d{2})/i', $text, 1);
        if ($date && ($parsed = $this->normaliseDate($date))) $invoice['purchase_date'] = $parsed;
        $dueDate = $this->firstMatch('/due\s*date\s*[:#-]?\s*([0-3]?\d[\/.\-][01]?\d[\/.\-](?:20)?\d{2})/i', $text, 1);
        if ($dueDate && ($parsed = $this->normaliseDate($dueDate))) $invoice['due_date'] = $parsed;

        $total = $this->lastMoneyMatch('/(?:grand\s*)?total(?:\s*(?:amount|due|payable))?\s*[:\-]?\s*(?:AED|USD|EUR|GBP|PKR)?\s*([0-9][0-9,]*(?:\.\d{1,2})?)/i', $text);
        if ($total === null) $total = $this->lastMoneyMatch('/(?:amount\s*due|net\s*payable)\s*[:\-]?\s*(?:AED|USD|EUR|GBP|PKR)?\s*([0-9][0-9,]*(?:\.\d{1,2})?)/i', $text);
        if ($total !== null) {
            $invoice['document_total'] = $total;
            $invoice['items'][] = [
                'category' => 'Other',
                'item_name' => 'Invoice total (review)',
                'quantity' => 1,
                'unit' => 'Pieces',
                'line_total' => $total,
            ];
        }

        $tax = $this->lastMoneyMatch('/(?:vat|tax)\s*(?:amount)?\s*[:\-]?\s*(?:AED|USD|EUR|GBP|PKR)?\s*([0-9][0-9,]*(?:\.\d{1,2})?)/i', $text);
        $subtotal = $this->lastMoneyMatch('/(?:sub\s*total|subtotal)\s*[:\-]?\s*(?:AED|USD|EUR|GBP|PKR)?\s*([0-9][0-9,]*(?:\.\d{1,2})?)/i', $text);
        if ($subtotal !== null && $tax !== null && $subtotal > 0) $invoice['vat_percentage'] = round(($tax * 100) / $subtotal, 2);

        return $invoice;
    }

    private function firstMatch($pattern, $text, $group = 0)
    {
        return preg_match($pattern, $text, $matches) ? trim($matches[$group]) : null;
    }

    private function lastMoneyMatch($pattern, $text)
    {
        preg_match_all($pattern, $text, $matches);
        if (empty($matches[1])) return null;
        return (float) str_replace(',', '', end($matches[1]));
    }

    private function normaliseDate($value)
    {
        $value = str_replace(['.', '-'], '/', $value);
        foreach (['d/m/Y', 'd/m/y', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
        }
        return null;
    }
}
