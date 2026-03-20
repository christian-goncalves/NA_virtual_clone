# QA GO-NO-GO - Dashboard

## Uso
Checklist de aprovacao para cada ciclo antes de liberar em producao.

## GO
1. `php -l` nos arquivos alterados sem erro.
2. Testes impactados passando.
3. `php artisan route:list` sem regressao de rotas admin.
4. `php artisan schedule:list` sem regressao dos jobs esperados.
5. Hardening ativo em rotas admin (`auth.basic`, `is_admin`, `harden.metrics.admin`).
6. Payloads da API admin estaveis (sucesso e 422).
7. Dashboard renderiza sem query de banco em Blade.
8. Contagens do resumo batem com filtros aplicados.

## NO-GO
1. Quebra de contrato de API.
2. Falha de autenticacao/autorizacao em rotas admin.
3. Divergencia de contagens (summary vs tabela).
4. Erros de sintaxe, testes falhando ou regressao em scheduler.
5. Regressao visual bloqueante no dashboard.
