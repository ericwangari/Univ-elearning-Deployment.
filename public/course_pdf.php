<?php
$title = $_GET['title'] ?? 'Course Handout';
$body = $_GET['body'] ?? 'This handout contains the key learning notes for this course.';

function pdf_escape($text) {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function wrap_text($text, $limit = 82) {
    $lines = [];
    foreach (explode("\n", wordwrap($text, $limit, "\n")) as $line) {
        $lines[] = trim($line);
    }
    return array_filter($lines, fn($line) => $line !== '');
}

$lines = array_merge([$title, ''], wrap_text($body));
$content = "BT\n/F1 18 Tf\n50 780 Td\n(" . pdf_escape($title) . ") Tj\n/F1 11 Tf\n0 -34 Td\n";

foreach (array_slice($lines, 2) as $line) {
    $content .= "(" . pdf_escape($line) . ") Tj\n0 -18 Td\n";
}

$content .= "ET";

$objects = [];
$objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
$objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
$objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
$objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
$objects[] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";

$pdf = "%PDF-1.4\n";
$offsets = [0];

foreach ($objects as $index => $object) {
    $offsets[] = strlen($pdf);
    $number = $index + 1;
    $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
}

$xrefOffset = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";

for ($i = 1; $i <= count($objects); $i++) {
    $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}

$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n{$xrefOffset}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $title) . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
