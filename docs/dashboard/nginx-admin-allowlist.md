# Nginx Allowlist para /admin/* (Dashboard de Metricas)

## Objetivo
Restringir o acesso a rotas administrativas (`/admin/*`) por IP na camada de infraestrutura, como defesa adicional ao `auth.basic` + `is_admin` + `harden.metrics.admin`.

## Snippet sugerido
Ajuste os IPs para o seu ambiente antes de publicar.

```nginx
# Permite apenas IPs internos/confiaveis em /admin/*
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

## Observacoes de ambiente
- Em homologacao, comece com um subconjunto pequeno de IPs para validar bloqueios.
- Em producao, mantenha a allowlist alinhada com `NA_VIRTUAL_METRICS_ADMIN_IP_ALLOWLIST` para coerencia entre Nginx e Laravel.
- Se houver CDN/WAF/reverse proxy, garanta que o IP de origem real chegue corretamente ao Nginx/Laravel.
- Sempre validar com `nginx -t` antes de recarregar a configuracao.
