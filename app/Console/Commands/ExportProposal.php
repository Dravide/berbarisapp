<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportProposal extends Command
{
    protected $signature = 'proposal:export';
    protected $description = 'Export PROPOSAL.md to PDF';

    public function handle()
    {
        $markdown = file_get_contents(base_path('doc/PROPOSAL_PROMOSI.md'));

        // Simple markdown to HTML conversion
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; color: #1e293b; line-height: 1.6; padding: 40px; }
            h1 { font-size: 22pt; color: #0062ff; border-bottom: 3px solid #0062ff; padding-bottom: 8px; }
            h2 { font-size: 16pt; color: #1e293b; margin-top: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
            h3 { font-size: 13pt; color: #334155; margin-top: 20px; }
            h4 { font-size: 11pt; color: #475569; }
            table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 10pt; }
            th { background: #0062ff; color: white; padding: 8px 10px; text-align: left; }
            td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
            tr:nth-child(even) td { background: #f8fafc; }
            code { background: #f1f5f9; padding: 1px 5px; border-radius: 3px; font-size: 10pt; }
            pre { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 9pt; }
            .badge-green { color: #16a34a; font-weight: bold; }
            .badge-blue { color: #2563eb; font-weight: bold; }
            blockquote { border-left: 4px solid #0062ff; padding-left: 16px; margin-left: 0; color: #475569; }
            hr { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
            .cover { text-align: center; padding-top: 120px; padding-bottom: 80px; }
            .cover h1 { font-size: 28pt; border: none; }
            .cover .subtitle { font-size: 14pt; color: #64748b; margin-top: 12px; }
            .cover .meta { font-size: 10pt; color: #94a3b8; margin-top: 40px; }
        </style>';
        $html .= '</head><body>';

        // Cover page
        $html .= '<div class="cover">';
        $html .= '<h1>BARIS APP</h1>';
        $html .= '<div class="subtitle">Platform Manajemen Event & Kompetisi Terpadu</div>';
        $html .= '<div class="subtitle" style="font-size:11pt;margin-top:8px;">Proposal Aplikasi</div>';
        $html .= '<div class="meta">' . date('d F Y') . '</div>';
        $html .= '</div>';
        $html .= '<div style="page-break-before: always;"></div>';

        // Convert markdown lines
        $lines = explode("\n", $markdown);
        $inTable = false;
        $inCode = false;
        $codeContent = '';
        $tableRows = [];

        foreach ($lines as $line) {
            // Code block
            if (str_starts_with($line, '```')) {
                if ($inCode) {
                    $html .= '<pre>' . htmlspecialchars($codeContent) . '</pre>';
                    $codeContent = '';
                    $inCode = false;
                } else {
                    $inCode = true;
                }
                continue;
            }
            if ($inCode) {
                $codeContent .= $line . "\n";
                continue;
            }

            // Headers
            if (str_starts_with($line, '## ')) {
                if ($inTable) { $inTable = false; }
                $html .= '<h2>' . htmlspecialchars(trim(substr($line, 3))) . '</h2>';
            } elseif (str_starts_with($line, '### ')) {
                if ($inTable) { $inTable = false; }
                $html .= '<h3>' . htmlspecialchars(trim(substr($line, 4))) . '</h3>';
            } elseif (str_starts_with($line, '#### ')) {
                if ($inTable) { $inTable = false; }
                $html .= '<h4>' . htmlspecialchars(trim(substr($line, 5))) . '</h4>';
            } elseif (str_starts_with($line, '---')) {
                if ($inTable) { $inTable = false; }
                $html .= '<hr>';
            } elseif (str_starts_with($line, '| ') || str_starts_with($line, '|---') || str_starts_with($line, '|:')) {
                if (str_starts_with($line, '|---') || str_starts_with($line, '|:')) continue;
                $cells = array_map('trim', explode('|', trim($line, '| ')));
                $tableRows[] = $cells;
                $inTable = true;
            } else {
                if ($inTable && !empty($tableRows)) {
                    $html .= '<table><thead><tr>';
                    foreach ($tableRows[0] as $h) $html .= '<th>' . htmlspecialchars($h) . '</th>';
                    $html .= '</tr></thead><tbody>';
                    for ($i = 1; $i < count($tableRows); $i++) {
                        $html .= '<tr>';
                        foreach ($tableRows[$i] as $c) $html .= '<td>' . htmlspecialchars($c) . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table>';
                    $tableRows = [];
                    $inTable = false;
                }

                if (empty(trim($line))) {
                    $html .= '<p>&nbsp;</p>';
                } elseif (str_starts_with($line, '- ')) {
                    $html .= '<li>' . htmlspecialchars(trim(substr($line, 2))) . '</li>';
                } elseif (str_starts_with($line, '  - ')) {
                    $html .= '<li style="margin-left:20px;">' . htmlspecialchars(trim(substr($line, 4))) . '</li>';
                } else {
                    $html .= '<p>' . nl2br(htmlspecialchars($line)) . '</p>';
                }
            }
        }

        $html .= '</body></html>';

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4');
        $path = storage_path('app/public/proposal-barisapp.pdf');
        $pdf->save($path);

        $this->info("PDF exported: {$path}");
        return Command::SUCCESS;
    }
}
