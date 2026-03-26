# New Project Checklist

## Preparacao da foundation

- [ ] Copiar `cg-laravel-foundation/` para o novo projeto como `.ai-foundation/`
- [ ] Validar leitura inicial de `codex/PROJECT_BOOT_INSTRUCTIONS.md`
- [ ] Confirmar ordem de prioridade em `codex/FILE_PRIORITY_ORDER.md`

## Base arquitetural

- [ ] Definir organizacao por dominio/feature
- [ ] Estabelecer convencao para controllers e services
- [ ] Criar configs de dominio iniciais em `config/*.php`
- [ ] Definir padrao de respostas HTTP e tratamento de erro

## Qualidade e governanca

- [ ] Aplicar `standards/CODE_STYLE_RULES.md`
- [ ] Revisar `standards/ANTI_PATTERNS.md` com o time
- [ ] Alinhar limites de camada com `standards/LAYER_BOUNDARIES.md`

## Observabilidade minima

- [ ] Definir metricas-chave por feature critica
- [ ] Configurar logs com contexto minimo util
- [ ] Definir dashboard inicial e alertas basicos

## Entrega inicial

- [ ] Criar primeira feature usando templates da foundation
- [ ] Executar testes de feature essenciais
- [ ] Revisar PR com `checklists/PR_CHECKLIST.md`
