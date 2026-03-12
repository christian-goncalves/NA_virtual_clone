Perfeito. Com base no `Top 10 Gaps Prioritários` e no `progresso-etapas.md` atual, o que **ainda está no escopo e não foi abordado** é:

1. **Fallback com último snapshot válido**  
- Criar `virtual_meeting_snapshots` (migration + model).  
- Salvar snapshot por sync.  
- Em falha da origem, servir último snapshot no fluxo da homepage.

2. **API pública JSON (`/api/reunioes-virtuais`)**  
- Criar `VirtualMeetingApiController`.  
- Expor payload equivalente ao view model atual.  
- Adicionar teste de contrato do endpoint.

3. **Alerta operacional (observabilidade ativa)**  
- Além de log, criar alerta para:  
  - falhas consecutivas de sync  
  - queda brusca de volume  
- Definir canal (log dedicado/webhook/email).

4. **Testes de fallback/API (e complementos operacionais)**  
- Cobrir cenário de falha com snapshot.  
- Cobrir resposta da API.  
- Cobrir regras de alerta/threshold.

5. **Alinhamento da frequência do scheduler por ambiente**  
- Parametrizar frequência (`5/10/30`) via env/config.  
- Manter decisão operacional documentada.

6. **Refino de estrutura de partials (opcional, baixo impacto)**  
- Separar `section-running`, `section-starting-soon`, `section-upcoming` (se quiser aderência 100% ao guia).

7. **Higiene documental final**  
- Consolidar hand-off desta rodada na pasta `docs/progresso` (há evidência de atualização no `progresso-etapas.md`, mas faltam registros complementares de fechamento por data).
