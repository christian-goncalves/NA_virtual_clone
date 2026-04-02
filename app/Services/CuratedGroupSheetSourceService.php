<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CuratedGroupSheetSourceService
{
    /**
     * @return array{pairs: list<array{meeting_id: string|null, group_name: string}>, total_rows_read: int, total_valid_pairs: int}
     *
     * @throws ValidationException
     */
    public function loadMeetingIdNamePairs(): array
    {
        $spreadsheetId = (string) config('na_virtual.curated_groups.sheets.spreadsheet_id', '');
        $gid = (string) config('na_virtual.curated_groups.sheets.gid', '0');

        if ($spreadsheetId === '') {
            throw ValidationException::withMessages([
                'spreadsheet_id' => ['Config de spreadsheet_id nao definida.'],
            ]);
        }

        $url = sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s', $spreadsheetId, $gid);

        try {
            $response = Http::timeout(20)->retry(2, 400)->get($url);
        } catch (Throwable $exception) {
            Log::error('sheet_read_failed', [
                'spreadsheet_id' => $spreadsheetId,
                'gid' => $gid,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Falha ao ler planilha remota de grupos.', previous: $exception);
        }

        if (! $response->successful()) {
            Log::error('sheet_read_failed', [
                'spreadsheet_id' => $spreadsheetId,
                'gid' => $gid,
                'status' => $response->status(),
            ]);

            throw ValidationException::withMessages([
                'sheet' => ['Planilha remota indisponivel para leitura.'],
            ]);
        }

        $csv = trim((string) $response->body());
        if ($csv === '') {
            throw ValidationException::withMessages([
                'sheet' => ['Planilha remota retornou vazia.'],
            ]);
        }

        $rows = $this->parseCsvRows($csv);
        if (count($rows) <= 1) {
            throw ValidationException::withMessages([
                'sheet' => ['Planilha sem linhas de dados para curadoria.'],
            ]);
        }

        $errors = [];
        $pairs = [];
        $seenPairKeys = [];
        $totalRowsRead = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $totalRowsRead++;
            $rowNumber = $index + 1;

            $name = trim((string) ($row[1] ?? ''));
            $id = trim((string) ($row[2] ?? ''));

            if ($name === '' && $id === '') {
                continue;
            }

            if ($name === '') {
                $errors["rows.{$rowNumber}.name"][] = 'Nome do grupo obrigatorio.';
                continue;
            }

            if ($id !== '' && ! preg_match('/^\d+$/', $id)) {
                $errors["rows.{$rowNumber}.id"][] = 'ID da reuniao deve ser numerico.';
                continue;
            }

            $normalizedName = $this->normalizeName($name);
            if ($normalizedName === '') {
                $errors["rows.{$rowNumber}.name"][] = 'Nome do grupo invalido apos normalizacao.';
                continue;
            }

            // Nome curado e a chave principal da fonte de verdade.
            $pairKey = $normalizedName;
            if (isset($seenPairKeys[$pairKey])) {
                continue;
            }

            $seenPairKeys[$pairKey] = true;
            $pairs[] = [
                'meeting_id' => $id !== '' ? $id : null,
                'group_name' => $name,
            ];
        }

        if ($errors !== []) {
            Log::warning('sheet_validation_failed', [
                'spreadsheet_id' => $spreadsheetId,
                'gid' => $gid,
                'error_fields' => array_keys($errors),
            ]);

            throw ValidationException::withMessages($errors);
        }

        if ($pairs === []) {
            throw ValidationException::withMessages([
                'sheet' => ['Planilha sem grupos validos (nome curado).'],
            ]);
        }

        return [
            'pairs' => $pairs,
            'total_rows_read' => $totalRowsRead,
            'total_valid_pairs' => count($pairs),
        ];
    }

    /**
     * @return list<list<string>>
     */
    private function parseCsvRows(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return [];
        }

        fwrite($handle, $csv);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = array_map(static fn ($value): string => trim((string) $value), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeName(string $value): string
    {
        $ascii = Str::ascii($value);
        $lower = mb_strtolower($ascii);
        $clean = preg_replace('/[^a-z0-9\s-]+/i', ' ', $lower);
        $collapsed = preg_replace('/\s+/', ' ', trim((string) $clean));

        return is_string($collapsed) ? $collapsed : '';
    }
}
