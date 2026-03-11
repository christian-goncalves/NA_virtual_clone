<?php

namespace Tests\Feature;

use App\Models\VirtualMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NaVirtualMeetingSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_with_valid_fixture_payload(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml($this->validMeetingsHtml()),
                200
            ),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Sincronização concluída.')
            ->assertSuccessful();

        $this->assertDatabaseCount('virtual_meetings', 2);
        $this->assertDatabaseHas('virtual_meetings', [
            'name' => 'Grupo Teste Madrugada',
            'weekday' => 'domingo',
            'meeting_platform' => 'zoom',
            'is_active' => 1,
        ]);
    }

    public function test_command_fails_when_payload_has_no_separator(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response('{"grupo0":[]}', 200),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Falha na sincronização:')
            ->assertFailed();

        $this->assertDatabaseCount('virtual_meetings', 0);
    }

    public function test_command_fails_when_html_has_no_meeting_tables(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml('<div>sem tabela de reuniões</div>'),
                200
            ),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Falha na sincronização:')
            ->assertFailed();

        $this->assertDatabaseCount('virtual_meetings', 0);
    }

    public function test_command_fails_when_all_time_ranges_are_malformed(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml($this->malformedTimeHtml()),
                200
            ),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Falha na sincronização:')
            ->assertFailed();

        $this->assertDatabaseCount('virtual_meetings', 0);
    }

    public function test_zero_results_abort_without_inactivating_existing_records(): void
    {
        $this->seedActiveMeetings(3);

        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml('<div>sem tabela</div>'),
                200
            ),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Falha na sincronização:')
            ->assertFailed();

        $this->assertSame(3, VirtualMeeting::query()->where('is_active', true)->count());
    }

    public function test_abrupt_volume_drop_currently_inactivates_many_records(): void
    {
        $this->seedActiveMeetings(10);

        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml($this->singleMeetingHtml()),
                200
            ),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Sincronização concluída.')
            ->assertSuccessful();

        // Este teste documenta o comportamento atual e evidencia a necessidade de guard rail.
        $this->assertSame(10, VirtualMeeting::query()->where('is_active', false)->count());
        $this->assertSame(1, VirtualMeeting::query()->where('is_active', true)->count());
    }

    public function test_parses_all_usl_weekly_entries_with_correct_schedule_and_credentials(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml($this->uslGroupHtml()),
                200
            ),
        ]);

        $this->artisan('na:sync-virtual-meetings')
            ->expectsOutputToContain('Sincronização concluída.')
            ->assertSuccessful();

        $query = VirtualMeeting::query()->where('name', 'Grupo USL');

        $this->assertSame(25, $query->count());

        $expected = [
            ['domingo', '10:00:00', '12:00:00'],
            ['domingo', '18:00:00', '20:00:00'],
            ['domingo', '21:30:00', '23:30:00'],
            ['segunda', '07:00:00', '09:00:00'],
            ['segunda', '10:00:00', '12:00:00'],
            ['segunda', '15:00:00', '17:00:00'],
            ['segunda', '21:30:00', '23:30:00'],
            ['terca', '10:00:00', '12:00:00'],
            ['terca', '15:00:00', '17:00:00'],
            ['terca', '21:30:00', '23:30:00'],
            ['quarta', '07:00:00', '09:00:00'],
            ['quarta', '10:00:00', '12:00:00'],
            ['quarta', '15:00:00', '17:00:00'],
            ['quarta', '21:30:00', '23:30:00'],
            ['quinta', '07:00:00', '09:00:00'],
            ['quinta', '10:00:00', '12:00:00'],
            ['quinta', '15:00:00', '17:00:00'],
            ['quinta', '21:30:00', '23:30:00'],
            ['sexta', '10:00:00', '12:00:00'],
            ['sexta', '15:00:00', '17:00:00'],
            ['sexta', '21:30:00', '23:30:00'],
            ['sabado', '07:00:00', '09:00:00'],
            ['sabado', '10:00:00', '12:00:00'],
            ['sabado', '15:00:00', '17:00:00'],
            ['sabado', '21:30:00', '23:30:00'],
        ];

        foreach ($expected as [$weekday, $start, $end]) {
            $this->assertDatabaseHas('virtual_meetings', [
                'name' => 'Grupo USL',
                'weekday' => $weekday,
                'start_time' => $start,
                'end_time' => $end,
                'meeting_id' => '2203202053',
                'meeting_password' => '000000',
                'meeting_url' => 'https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1',
            ]);
        }
    }

    private function buildPayloadFromHtml(string $html): string
    {
        $mapJson = <<<'JSON'
{"grupo0":[{"meeting_name":"<b>Grupo Teste Madrugada</b>","longitude":"-46.6395571","endereco":"<br>São Paulo São Paulo","latitude":"-23.5557714"}],"grupo1":[{"meeting_name":"<b>Grupo Teste Tarde</b>","longitude":"-43.1728965","endereco":"<br>Rio de Janeiro Rio de Janeiro","latitude":"-22.9068467"}]}
JSON;

        return $mapJson.'||'.$html;
    }

    private function validMeetingsHtml(): string
    {
        return <<<'HTML'
<table id="copy0">
  <tr>
    <td colspan="2" align="center">Grupo Teste Madrugada</td>
  </tr>
  <tr>
    <td>Dom</td>
    <td>
      <a href="https://us06web.zoom.us/j/12345678901">00:10 às 02:10 ( Reunião Virtual, Estudo de Literatura )</a><br>
      ID: 123 4567 8901 | Senha: 999999<br>
    </td>
  </tr>
  <tr>
    <td colspan="2">São Paulo / São Paulo</td>
  </tr>
</table>
<table id="copy1">
  <tr>
    <td colspan="2" align="center">Grupo Teste Tarde</td>
  </tr>
  <tr>
    <td>Seg</td>
    <td>
      <a href="https://zello.com/canal-teste">14:00 às 16:00 ( Reunião Virtual, Mulheres )</a><br>
      ID: 222 3333 4444 | Senha: 111111<br>
    </td>
  </tr>
  <tr>
    <td colspan="2">Rio de Janeiro / Rio de Janeiro</td>
  </tr>
</table>
HTML;
    }

    private function malformedTimeHtml(): string
    {
        return <<<'HTML'
<table id="copy0">
  <tr>
    <td colspan="2" align="center">Grupo Horario Invalido</td>
  </tr>
  <tr>
    <td>Dom</td>
    <td>
      <a href="https://us06web.zoom.us/j/12345678901">horário inválido ( Reunião Virtual )</a><br>
      ID: 123 4567 8901 | Senha: 999999<br>
    </td>
  </tr>
</table>
HTML;
    }

    private function singleMeetingHtml(): string
    {
        return <<<'HTML'
<table id="copy0">
  <tr>
    <td colspan="2" align="center">Grupo Queda Brusca</td>
  </tr>
  <tr>
    <td>Ter</td>
    <td>
      <a href="https://us06web.zoom.us/j/99999999999">09:00 às 10:00 ( Reunião Virtual )</a><br>
      ID: 999 9999 9999 | Senha: 121212<br>
    </td>
  </tr>
  <tr>
    <td colspan="2">Belo Horizonte / Minas Gerais</td>
  </tr>
</table>
HTML;
    }

    private function uslGroupHtml(): string
    {
        return <<<'HTML'
<table id="copy24">
  <tr>
    <td colspan="2" align="center">Grupo USL</td>
  </tr>
  <tr>
    <td>Dom</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">18:00 às 20:00 ( Aberta para visitantes, Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td>Seg</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">07:00 às 09:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">15:00 às 17:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td>Ter</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">15:00 às 17:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Estudo de Literatura, Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td>Qua</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">07:00 às 09:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">15:00 às 17:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Estudo de Passos, Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td>Qui</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">07:00 às 09:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">15:00 às 17:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td>Sex</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">15:00 às 17:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Temática, Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td>Sáb</td>
    <td>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">07:00 às 09:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">10:00 às 12:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">15:00 às 17:00 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
      <a href="https://us06web.zoom.us/j/2203202053?pwd=fOHk17Hlvma7ZuaFQsewReXFKcfGM4.1">21:30 às 23:30 ( Reunião Virtual)</a><br>
      ID: 220 320 2053 | Senha: 000000<br>
    </td>
  </tr>
  <tr>
    <td colspan="2">São Paulo / São Paulo</td>
  </tr>
</table>
HTML;
    }

    private function seedActiveMeetings(int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            VirtualMeeting::query()->create([
                'external_id' => 'seed-'.$index,
                'name' => 'Seed Meeting '.$index,
                'weekday' => 'segunda',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'duration_minutes' => 60,
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
                'source_url' => 'https://www.na.org.br/virtual/',
            ]);
        }
    }
}
