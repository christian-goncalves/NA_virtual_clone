<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetricsSyncRunInstrumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_sync_creates_metric_sync_run_record(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response(
                $this->buildPayloadFromHtml($this->validMeetingsHtml()),
                200
            ),
        ]);

        $exitCode = Artisan::call('na:sync-virtual-meetings');

        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('metric_sync_runs', [
            'status' => 'success',
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);
    }

    public function test_failed_sync_creates_metric_sync_run_record(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response('falha', 500),
        ]);

        $exitCode = Artisan::call('na:sync-virtual-meetings');

        $this->assertSame(1, $exitCode);

        $this->assertDatabaseHas('metric_sync_runs', [
            'status' => 'failed',
        ]);
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
}
