<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Geração de PDF (DomPDF) e planilhas XLSX (PhpSpreadsheet). */
class Exportador
{
    public static function pdf(string $view, array $dados, string $arquivo, string $orientacao = 'portrait'): Response
    {
        $dados['geradoEm'] = now();
        $dados['geradoPor'] = auth()->user()?->name;

        return Pdf::loadView($view, $dados)->setPaper('a4', $orientacao)->download($arquivo);
    }

    /**
     * @param  array<int, string>  $cabecalho
     * @param  iterable<array<int, mixed>>  $linhas
     */
    public static function xlsx(string $titulo, array $cabecalho, iterable $linhas, string $arquivo): StreamedResponse
    {
        $planilha = new Spreadsheet;
        $aba = $planilha->getActiveSheet();
        $aba->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $titulo), 0, 31));

        $aba->setCellValue('A1', $titulo);
        $aba->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $aba->setCellValue('A2', config('sdgt.orgao.nome').' — gerado em '.now()->format('d/m/Y H:i'));

        $col = 1;
        foreach ($cabecalho as $c) {
            $aba->setCellValue([$col++, 4], $c);
        }
        $ultima = Coordinate::stringFromColumnIndex(max(count($cabecalho), 1));
        $aba->getStyle("A4:{$ultima}4")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $aba->getStyle("A4:{$ultima}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0066B0');

        $linha = 5;
        foreach ($linhas as $l) {
            $col = 1;
            foreach ($l as $v) {
                $aba->setCellValue([$col++, $linha], $v);
            }
            $linha++;
        }
        for ($i = 1; $i <= count($cabecalho); $i++) {
            $aba->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        $aba->setAutoFilter("A4:{$ultima}".max(4, $linha - 1));
        $aba->freezePane('A5');

        return response()->streamDownload(function () use ($planilha) {
            (new Xlsx($planilha))->save('php://output');
        }, $arquivo, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
