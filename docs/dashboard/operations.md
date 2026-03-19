# Operations - Dashboard de Metricas

## Objetivo
Padronizar seguranca operacional, hardening e rotina de verificacao do dashboard admin.

## Acesso admin
- Rota web: `/admin/metricas`
- API interna: `/api/admin/metricas`
- Protecao minima: `auth.basic`, `is_admin`, `harden.metrics.admin`
- Cabecalho recomendado no dashboard: `X-Robots-Tag: noindex, nofollow`

## Hardening de aplicacao
- Manter allowlist de admins por email em `NA_VIRTUAL_METRICS_ADMIN_EMAILS`.
- Manter allowlist de IP em `NA_VIRTUAL_METRICS_ADMIN_IP_ALLOWLIST` (aceitar IP exato e CIDR).
- Em producao, manter `NA_VIRTUAL_METRICS_ADMIN_REQUIRE_HTTPS=true`.

## Hardening de infraestrutura (Nginx)
Usar allowlist de rede para `/admin/*` como defesa adicional.

```nginx
location ^~ /admin/ {
    allow 127.0.0.1;
    allow 10.0.0.0/8;
    allow 172.16.0.0/12;
    allow 192.168.0.0/16;
    deny all;

    proxy_pass http://php_app_upstream;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## Rotina minima por entrega
1. `php -l` nos arquivos alterados.
2. Rodar testes impactados (feature/unit).
3. `php artisan route:list`.
4. `php artisan schedule:list`.

## Scheduler esperado (operacional)
- `na:sync-virtual-meetings`
- `capture-na-virtual-metrics-snapshot`
- `consolidate-na-virtual-hourly-metrics`
- `evaluate-na-virtual-metrics-health-alerts`
- `prune-na-virtual-metrics-retention`

## Retencao (referencia)
- Bruto (`page_views`, `request_metrics`): 30 dias.
- `sync_runs` e `meeting_snapshots`: 90 dias.
- Agregados horarios: 180 dias.
