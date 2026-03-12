<?php

namespace App\Services;

use App\Models\VirtualMeeting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class NaVirtualMeetingSyncService
{
    private const SOURCE_URL = 'https://www.na.org.br/virtual/';
    private const AJAX_URL = 'https://www.na.org.br/wp-admin/admin-ajax.php';

    public function __construct(
        private readonly NaVirtualMeetingGroupingService $groupingService,
        private readonly NaVirtualMeetingSnapshotService $snapshotService,
    ) {}

    /**
     * Sync meetings from official source into local database.
     *
     * @return array<string, int|string>
     */
    public function sync(): array
    {
        try {
            $syncedAt = now();
            $payload = $this->downloadMeetingsPayload();
            $meetings = $this->parseMeetings($payload);

            if ($meetings === []) {
                throw new RuntimeException('Nenhuma reunião válida foi extraída; sincronização abortada para proteger a base local.');
            }

            $result = $this->persist($meetings, $syncedAt);
            $this->saveHomepageSnapshot($syncedAt);
            $this->markSyncSuccess($syncedAt);
            $this->invalidateHomepageCache();

            return [
                ...$result,
                'source_url' => self::SOURCE_URL,
            ];
        } catch (Throwable $e) {
            $this->markSyncFailure(now(), $e->getMessage());

            throw $e;
        }
    }

    private function downloadMeetingsPayload(): string
    {
        $response = Http::timeout(20)
            ->retry(2, 500)
            ->get(self::AJAX_URL, [
                'action' => 'get_service_grupos',
                'estado' => '',
                'cidade' => '',
                'bairro' => '',
                'A' => '1',
                'B' => '1',
                'formatos' => '',
                'periodo' => 'all',
                'ic_formato' => 'virtual',
                'weekdays' => 'all',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao consultar a origem oficial. Status: '.$response->status());
        }

        $payload = $response->body();

        if (trim($payload) === '') {
            throw new RuntimeException('A resposta da origem veio vazia.');
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseMeetings(string $payload): array
    {
        [$mapJson, $html] = array_pad(explode('||', $payload, 2), 2, '');

        if (trim($html) === '') {
            throw new RuntimeException('A origem não retornou o bloco HTML das reuniões.');
        }

        $locationByGroupName = $this->extractLocationsFromMapJson($mapJson);
        $crawler = new Crawler('<div id="virtual-meetings-root">'.$html.'</div>');

        $meetings = [];
        $parseErrors = 0;

        $crawler->filter('table[id^="copy"]')->each(function (Crawler $table) use (&$meetings, &$parseErrors, $locationByGroupName): void {
            try {
                $groupName = $this->extractGroupName($table);
                if ($groupName === null) {
                    return;
                }

                $location = $locationByGroupName[Str::lower($groupName)] ?? [
                    'city' => null,
                    'state' => null,
                ];
                $tableLocation = $this->extractLocationFromTable($table);
                if ($tableLocation['city'] !== null || $tableLocation['state'] !== null) {
                    $location = $tableLocation;
                }

                $table->filter('tr')->each(function (Crawler $row) use (&$meetings, $groupName, $location): void {
                    $cells = $row->filter('td');
                    if ($cells->count() < 2) {
                        return;
                    }

                    $weekdayRaw = $this->normalizeText($cells->eq(0)->text('', true));
                    $weekday = $this->normalizeWeekday($weekdayRaw);
                    if ($weekday === null) {
                        return;
                    }

                    $detailsCell = $cells->eq(1);
                    $entries = $this->extractMeetingEntriesFromDetailsCell($detailsCell);

                    foreach ($entries as $entry) {
                        $detailsText = $entry['details_text'];
                        $startTime = $entry['start_time'];
                        $endTime = $entry['end_time'];

                        if ($startTime === null) {
                            continue;
                        }

                        $meetingUrl = $entry['meeting_url'];
                        $platform = $this->extractPlatform($detailsText, $meetingUrl, $detailsCell);
                        $meetingId = $entry['meeting_id'];
                        $meetingPassword = $entry['meeting_password'];
                        $formatLabels = $this->extractFormatLabelsFromDetails($detailsText);
                        $interestLabels = $this->extractInterestLabels($detailsText);
                        $durationMinutes = $this->durationFromTimes($startTime, $endTime);
                        $sourceHash = sha1($this->normalizeText(implode('|', [
                            $groupName,
                            $weekday,
                            $startTime,
                            $endTime ?? '',
                            $platform ?? '',
                            $meetingId ?? '',
                            $meetingPassword ?? '',
                            $meetingUrl ?? '',
                            $detailsText,
                        ])));
                        $externalId = $this->buildExternalId(
                            $groupName,
                            $weekday,
                            $startTime,
                            $endTime,
                            $platform,
                            $meetingId,
                            $meetingUrl,
                            $sourceHash
                        );

                        $meetings[] = [
                            'external_id' => $externalId,
                            'name' => $groupName,
                            'meeting_platform' => $platform,
                            'meeting_url' => $meetingUrl,
                            'meeting_id' => $meetingId,
                            'meeting_password' => $meetingPassword,
                            'phone' => $this->extractPhone($detailsText),
                            'region' => null,
                            'state' => $location['state'],
                            'city' => $location['city'],
                            'neighborhood' => null,
                            'format_labels' => $formatLabels !== [] ? $formatLabels : null,
                            'type_label' => $this->extractTypeLabel($detailsText, $formatLabels),
                            'interest_labels' => $interestLabels !== [] ? $interestLabels : null,
                            'weekday' => $weekday,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'duration_minutes' => $durationMinutes,
                            'timezone' => 'America/Sao_Paulo',
                            'is_open' => $this->containsAny($detailsText, ['aberta', 'aberto', 'visitantes']),
                            'is_study' => $this->containsAny($detailsText, ['estudo', 'literatura', 'guia de passos', 'texto básico']),
                            'is_lgbt' => $this->containsAny($detailsText, ['lgbt', 'lgbtq', 'lgbtqia', 'lgbtqiapn']),
                            'is_women' => $this->containsAny($detailsText, ['mulher', 'mulheres', 'feminino', 'só por elas']),
                            'is_hybrid' => $this->containsAny($detailsText, ['hibrida', 'híbrida', 'hibrido', 'híbrido']),
                            'source_url' => self::SOURCE_URL,
                            'source_hash' => $sourceHash,
                        ];
                    }
                });
            } catch (Throwable $e) {
                $parseErrors++;
                Log::warning('Falha ao parsear grupo de reunião virtual.', [
                    'message' => $e->getMessage(),
                ]);
            }
        });

        if ($parseErrors > 0) {
            Log::warning('Parsing finalizado com falhas parciais.', [
                'parse_errors' => $parseErrors,
            ]);
        }

        return array_values($this->deduplicateByExternalId($meetings));
    }

    /**
     * @return list<array{details_text: string, meeting_url: string|null, meeting_id: string|null, meeting_password: string|null, start_time: string|null, end_time: string|null}>
     */
    private function extractMeetingEntriesFromDetailsCell(Crawler $detailsCell): array
    {
        $entries = [];
        $anchorCount = $detailsCell->filter('a')->count();

        if ($anchorCount === 0) {
            $detailsText = $this->normalizeText($detailsCell->text('', true));
            [$startTime, $endTime] = $this->extractTimeRange($detailsText);

            return [[
                'details_text' => $detailsText,
                'meeting_url' => null,
                'meeting_id' => $this->extractMeetingId($detailsText),
                'meeting_password' => $this->extractMeetingPassword($detailsText),
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]];
        }

        $anchors = $detailsCell->filter('a')->each(fn (Crawler $anchor): Crawler => $anchor);

        foreach ($anchors as $index => $anchor) {
            $anchorText = $this->normalizeText($anchor->text('', true));
            $meetingUrl = $this->normalizeUrl($anchor->attr('href'));
            $afterText = $this->extractTextBetweenAnchors($detailsCell, $index);
            $combinedText = $this->normalizeText(trim($anchorText.' '.$afterText));
            [$startTime, $endTime] = $this->extractTimeRange($anchorText);

            $entries[] = [
                'details_text' => $combinedText,
                'meeting_url' => $meetingUrl,
                'meeting_id' => $this->extractMeetingId($afterText ?: $combinedText),
                'meeting_password' => $this->extractMeetingPassword($afterText ?: $combinedText),
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        return $entries;
    }

    private function extractTextBetweenAnchors(Crawler $detailsCell, int $anchorIndex): string
    {
        $html = $detailsCell->html('');
        if ($html === '') {
            return '';
        }

        preg_match_all('/<a\b[^>]*>.*?<\/a>/is', $html, $matches, PREG_OFFSET_CAPTURE);
        $anchors = $matches[0] ?? [];

        if (! isset($anchors[$anchorIndex])) {
            return '';
        }

        $startOffset = $anchors[$anchorIndex][1] + strlen($anchors[$anchorIndex][0]);
        $endOffset = isset($anchors[$anchorIndex + 1]) ? $anchors[$anchorIndex + 1][1] : strlen($html);
        $segment = substr($html, $startOffset, max(0, $endOffset - $startOffset));

        if ($segment === false) {
            return '';
        }

        return $this->normalizeText(strip_tags(str_replace('<br>', ' ', str_ireplace('<br/>', ' ', str_ireplace('<br />', ' ', $segment)))));
    }

    /**
     * @return array<string, array{city: string|null, state: string|null}>
     */
    private function extractLocationsFromMapJson(string $mapJson): array
    {
        $mapJson = trim($mapJson);
        if ($mapJson === '' || ! str_starts_with($mapJson, '{')) {
            return [];
        }

        $decoded = json_decode($mapJson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $locations = [];

        foreach ($decoded as $group) {
            if (! is_array($group) || ! isset($group[0]) || ! is_array($group[0])) {
                continue;
            }

            $item = $group[0];
            $name = $this->cleanGroupName((string) ($item['meeting_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $address = $this->normalizeText(strip_tags((string) ($item['endereco'] ?? '')));
            $cityState = $this->extractCityStateFromAddress($address);

            $locations[Str::lower($name)] = [
                'city' => $cityState['city'],
                'state' => $cityState['state'],
            ];
        }

        return $locations;
    }

    private function extractGroupName(Crawler $table): ?string
    {
        $headerCell = $table->filter('tr')->first()->filter('td')->first();
        if ($headerCell->count() === 0) {
            return null;
        }

        $html = $headerCell->html('');
        $name = $this->cleanGroupName($html);

        return $name !== '' ? $name : null;
    }

    /**
     * @return array{city: string|null, state: string|null}
     */
    private function extractLocationFromTable(Crawler $table): array
    {
        $locationText = null;

        $table->filter('tr td[colspan="2"]')->each(function (Crawler $cell) use (&$locationText): void {
            $text = $this->normalizeText(strip_tags($cell->html('')));
            if (Str::contains($text, '/')) {
                $locationText = $text;
            }
        });

        if ($locationText === null) {
            return ['city' => null, 'state' => null];
        }

        if (preg_match('/([^\n\/-]+)\s*\/\s*([^\n-]+)/u', $locationText, $match)) {
            return [
                'city' => $this->normalizeText($match[1]) ?: null,
                'state' => $this->normalizeText($match[2]) ?: null,
            ];
        }

        return $this->extractCityStateFromAddress($locationText);
    }

    /**
     * @return array{city: string|null, state: string|null}
     */
    private function extractCityStateFromAddress(string $address): array
    {
        if ($address === '') {
            return ['city' => null, 'state' => null];
        }

        $states = [
            'Acre',
            'Alagoas',
            'Amapá',
            'Amazonas',
            'Bahia',
            'Ceará',
            'Distrito Federal',
            'Espírito Santo',
            'Goiás',
            'Maranhão',
            'Mato Grosso',
            'Mato Grosso do Sul',
            'Minas Gerais',
            'Pará',
            'Paraíba',
            'Paraná',
            'Pernambuco',
            'Piauí',
            'Rio de Janeiro',
            'Rio Grande do Norte',
            'Rio Grande do Sul',
            'Rondônia',
            'Roraima',
            'Santa Catarina',
            'São Paulo',
            'Sergipe',
            'Tocantins',
        ];

        $normalizedAddress = Str::lower(Str::ascii($address));

        foreach ($states as $state) {
            $normalizedState = Str::lower(Str::ascii($state));
            if (! Str::contains($normalizedAddress, $normalizedState)) {
                continue;
            }

            $city = trim(preg_replace('/\b'.preg_quote($state, '/').'\b.*/iu', '', $address) ?? '');
            $city = trim(preg_replace('/\b\d{5,}\b/u', '', $city) ?? '');
            $city = trim(preg_replace('/\s{2,}/u', ' ', $city) ?? $city);
            $cityParts = preg_split('/\s+/u', $city) ?: [];
            $city = implode(' ', array_slice($cityParts, -3));

            return [
                'city' => $city !== '' ? $city : null,
                'state' => $state,
            ];
        }

        return ['city' => null, 'state' => null];
    }

    /**
     * @param list<array<string, mixed>> $meetings
     * @return array<string, int>
     */
    private function persist(array $meetings, Carbon $syncedAt): array
    {
        $activeBeforeCount = VirtualMeeting::query()
            ->where('is_active', true)
            ->count();
        $created = 0;
        $updated = 0;
        $seenExternalIds = [];

        foreach ($meetings as $meeting) {
            $externalId = (string) $meeting['external_id'];
            $seenExternalIds[] = $externalId;

            $model = VirtualMeeting::query()->firstOrNew([
                'external_id' => $externalId,
            ]);

            $wasNew = ! $model->exists;

            $model->fill($meeting);
            $model->is_active = true;
            $model->synced_at = $syncedAt;
            $model->last_seen_at = $syncedAt;
            $model->save();

            if ($wasNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $inactivated = 0;

        if ($seenExternalIds !== [] && $activeBeforeCount > 0) {
            $guardDecision = $this->evaluateInactivationGuard($activeBeforeCount, count($meetings));

            Log::info('Avaliacao de guard rail para inativacao de reunioes virtuais.', [
                'active_before_count' => $activeBeforeCount,
                'total_found' => count($meetings),
                'drop_percentage' => $guardDecision['drop_percentage'],
                'min_found_for_inactivation' => $guardDecision['min_found_for_inactivation'],
                'min_ratio_for_inactivation' => $guardDecision['min_ratio_for_inactivation'],
                'decision' => $guardDecision['allow_inactivation'] ? 'inactivation_allowed' : 'inactivation_blocked',
            ]);

            if ($guardDecision['allow_inactivation']) {
                $inactivated = VirtualMeeting::query()
                    ->where('is_active', true)
                    ->whereNotIn('external_id', $seenExternalIds)
                    ->update([
                        'is_active' => false,
                        'synced_at' => $syncedAt,
                    ]);
            }
        }

        return [
            'total_found' => count($meetings),
            'total_created' => $created,
            'total_updated' => $updated,
            'total_inactivated' => $inactivated,
            'active_before_count' => $activeBeforeCount,
        ];
    }

    /**
     * @return array{allow_inactivation: bool, drop_percentage: float, min_found_for_inactivation: int, min_ratio_for_inactivation: float}
     */
    private function evaluateInactivationGuard(int $activeBeforeCount, int $totalFound): array
    {
        $minFoundForInactivation = max(1, (int) config('na_virtual.sync_guard.min_found_for_inactivation', 5));
        $minRatioForInactivation = (float) config('na_virtual.sync_guard.min_ratio_for_inactivation', 0.20);
        $minRatioForInactivation = max(0.0, min(1.0, $minRatioForInactivation));
        $foundRatio = $activeBeforeCount > 0 ? ($totalFound / $activeBeforeCount) : 1.0;
        $dropPercentage = round(max(0, (1 - $foundRatio) * 100), 2);

        $allowInactivation = $totalFound >= $minFoundForInactivation
            && $foundRatio >= $minRatioForInactivation;

        return [
            'allow_inactivation' => $allowInactivation,
            'drop_percentage' => $dropPercentage,
            'min_found_for_inactivation' => $minFoundForInactivation,
            'min_ratio_for_inactivation' => $minRatioForInactivation,
        ];
    }

    /**
     * @param list<array<string, mixed>> $meetings
     * @return array<string, array<string, mixed>>
     */
    private function deduplicateByExternalId(array $meetings): array
    {
        $unique = [];

        foreach ($meetings as $meeting) {
            $externalId = (string) $meeting['external_id'];
            $unique[$externalId] = $meeting;
        }

        return $unique;
    }

    private function cleanGroupName(string $html): string
    {
        $name = $this->normalizeText(strip_tags($html));
        $name = str_replace('Reunião Verificada', '', $name);
        $name = $this->normalizeText($name);

        return Str::limit($name, 255, '');
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function extractTimeRange(string $text): array
    {
        preg_match('/\b([01]?\d|2[0-3])[:hH]([0-5]\d)\s*(?:às|as|a)\s*([01]?\d|2[0-3])[:hH]([0-5]\d)\b/iu', $text, $rangeMatch);
        if ($rangeMatch !== []) {
            return [
                sprintf('%02d:%02d:00', (int) $rangeMatch[1], (int) $rangeMatch[2]),
                sprintf('%02d:%02d:00', (int) $rangeMatch[3], (int) $rangeMatch[4]),
            ];
        }

        preg_match_all('/\b([01]?\d|2[0-3])[:hH]([0-5]\d)\b/u', $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return [null, null];
        }

        $start = sprintf('%02d:%02d:00', (int) $matches[0][1], (int) $matches[0][2]);
        $end = isset($matches[1]) ? sprintf('%02d:%02d:00', (int) $matches[1][1], (int) $matches[1][2]) : null;

        return [$start, $end];
    }

    private function normalizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || ! preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        return Str::limit($url, 65535, '');
    }

    private function extractMeetingId(string $text): ?string
    {
        if (preg_match('/(?:meeting\s*id|id(?:\s*da\s*reuni[aã]o)?|id)\s*[:\-]?\s*([0-9][0-9\s\-]{5,})/iu', $text, $match)) {
            return preg_replace('/\D+/', '', $match[1]) ?: null;
        }

        return null;
    }

    private function extractMeetingPassword(string $text): ?string
    {
        if (preg_match('/(?:senha|passcode|password)\s*[:\-]?\s*([a-z0-9@#\-\._]{3,})/iu', $text, $match)) {
            return Str::limit($match[1], 255, '');
        }

        return null;
    }

    private function extractPhone(string $text): ?string
    {
        if (preg_match('/(?:\+?55\s*)?(?:\(?\d{2}\)?\s*)?\d{4,5}[-\s]?\d{4}/u', $text, $match)) {
            return Str::limit($this->normalizeText($match[0]), 255, '');
        }

        return null;
    }

    private function extractPlatform(string $text, ?string $meetingUrl, Crawler $detailsCell): ?string
    {
        $iconText = '';
        if ($detailsCell->filter('img[src]')->count() > 0) {
            $iconText = implode(' ', $detailsCell->filter('img[src]')->each(fn (Crawler $img): string => (string) $img->attr('src')));
        }

        $combined = Str::lower($text.' '.($meetingUrl ?? '').' '.$iconText);

        return match (true) {
            Str::contains($combined, 'zoom') => 'zoom',
            Str::contains($combined, 'zello') => 'zello',
            Str::contains($combined, 'meet.google') || Str::contains($combined, 'google meet') => 'google-meet',
            Str::contains($combined, 'microsoft teams') || Str::contains($combined, 'teams.microsoft') => 'teams',
            Str::contains($combined, 'jitsi') => 'jitsi',
            Str::contains($combined, 'skype') => 'skype',
            default => null,
        };
    }

    private function normalizeWeekday(string $text): ?string
    {
        $weekdayMap = [
            'dom' => 'domingo',
            'domingo' => 'domingo',
            'seg' => 'segunda',
            'segunda' => 'segunda',
            'ter' => 'terca',
            'terca' => 'terca',
            'terça' => 'terca',
            'qua' => 'quarta',
            'quarta' => 'quarta',
            'qui' => 'quinta',
            'quinta' => 'quinta',
            'sex' => 'sexta',
            'sexta' => 'sexta',
            'sab' => 'sabado',
            'sabado' => 'sabado',
            'sábado' => 'sabado',
            'sáb' => 'sabado',
            'domingo' => 'domingo',
        ];

        $normalized = Str::lower(Str::ascii($this->normalizeText($text)));

        foreach ($weekdayMap as $needle => $weekday) {
            if (Str::contains($normalized, Str::ascii($needle))) {
                return $weekday;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extractFormatLabelsFromDetails(string $text): array
    {
        $labels = [];

        if (preg_match('/\(([^)]*)\)/u', $text, $match)) {
            $parts = preg_split('/\s*,\s*/u', $match[1]) ?: [];

            foreach ($parts as $part) {
                $label = $this->normalizeText($part);
                if ($label !== '' && ! $this->containsAny($label, ['reunião virtual', 'reuniao virtual'])) {
                    $labels[] = Str::limit($label, 255, '');
                }
            }
        }

        $candidates = [
            'aberta',
            'fechada',
            'estudo',
            'tematica',
            'temática',
            'hibrida',
            'híbrida',
            'presencial',
            'online',
            'virtual',
        ];

        return array_values(array_unique([
            ...$labels,
            ...$this->extractLabelsFromCandidates($text, $candidates),
        ]));
    }

    /**
     * @return list<string>
     */
    private function extractInterestLabels(string $text): array
    {
        $candidates = [
            'lgbt',
            'lgbtq',
            'lgbtqia',
            'lgbtqiapn+',
            'mulheres',
            'feminino',
            'jovens',
            'iniciantes',
        ];

        return $this->extractLabelsFromCandidates($text, $candidates);
    }

    /**
     * @param list<string> $candidates
     * @return list<string>
     */
    private function extractLabelsFromCandidates(string $text, array $candidates): array
    {
        $normalized = Str::lower(Str::ascii($text));
        $labels = [];

        foreach ($candidates as $candidate) {
            $needle = Str::lower(Str::ascii($candidate));

            if (Str::contains($normalized, $needle)) {
                $labels[] = $candidate;
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * @param list<string> $formatLabels
     */
    private function extractTypeLabel(string $text, array $formatLabels): ?string
    {
        if ($this->containsAny($text, ['aberta', 'aberto'])) {
            return 'aberta';
        }

        if ($this->containsAny($text, ['fechada', 'fechado'])) {
            return 'fechada';
        }

        if (in_array('estudo', $formatLabels, true)) {
            return 'estudo';
        }

        return null;
    }

    private function durationFromTimes(string $startTime, ?string $endTime): ?int
    {
        if ($endTime === null) {
            return null;
        }

        $start = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        // Reunioes que cruzam meia-noite (ex.: 23:00 as 01:00).
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end, true);
    }

    private function buildExternalId(
        string $name,
        string $weekday,
        string $startTime,
        ?string $endTime,
        ?string $platform,
        ?string $meetingId,
        ?string $meetingUrl,
        string $sourceHash
    ): string {
        $parts = [
            Str::lower(trim($name)),
            $weekday,
            $startTime,
            $endTime ?? '',
            $platform ?? '',
            $meetingId ?? '',
            $meetingUrl ?? '',
        ];

        $normalized = $this->normalizeText(implode('|', $parts));

        return sha1($normalized !== '' ? $normalized : $sourceHash);
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $text, array $needles): bool
    {
        $haystack = Str::lower(Str::ascii($text));

        foreach ($needles as $needle) {
            if (Str::contains($haystack, Str::lower(Str::ascii($needle)))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function invalidateHomepageCache(): void
    {
        $cacheKey = (string) config('na_virtual.homepage_cache.key', 'na.virtual.homepage');
        Cache::forget($cacheKey);

        Log::info('Cache da homepage de reunioes virtuais invalidado apos sync.', [
            'cache_key' => $cacheKey,
        ]);
    }

    private function saveHomepageSnapshot(Carbon $capturedAt): void
    {
        $dataset = $this->groupingService->buildHomePageData($capturedAt);
        $this->snapshotService->saveHomepageSnapshot($dataset, $capturedAt);

        Log::info('Snapshot da homepage de reunioes virtuais atualizado apos sync.', [
            'captured_at' => $capturedAt->toIso8601String(),
        ]);
    }

    private function markSyncSuccess(Carbon $timestamp): void
    {
        $successCacheKey = (string) config('na_virtual.sync_status.last_success_cache_key', 'na.virtual.sync.last_success_at');
        Cache::forever($successCacheKey, $timestamp->toIso8601String());
    }

    private function markSyncFailure(Carbon $timestamp, string $message): void
    {
        $failureCacheKey = (string) config('na_virtual.sync_status.last_failure_cache_key', 'na.virtual.sync.last_failure_at');
        Cache::forever($failureCacheKey, $timestamp->toIso8601String());

        Log::warning('Sincronizacao de reunioes virtuais falhou; status de falha registrado.', [
            'failed_at' => $timestamp->toIso8601String(),
            'message' => $message,
        ]);
    }
}
