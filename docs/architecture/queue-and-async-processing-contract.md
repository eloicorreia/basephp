# Contrato oficial de filas e processamento assíncrono

## 1. Objetivo

Este documento define o padrão oficial de filas e processamento assíncrono do projeto.

As regras deste contrato são obrigatórias para:
- jobs
- listeners assíncronos
- comandos administrativos com processamento em lote
- batchs
- rotinas de reprocessamento
- integrações executadas fora do ciclo síncrono da requisição HTTP

Este contrato complementa:
- o contrato oficial de autenticação e multi-tenant
- o contrato oficial de logging e observabilidade

Nenhuma implementação assíncrona pode ignorar este documento.

---

## 2. Driver oficial de fila

### 2.1. Driver padrão
O driver oficial inicial de filas do projeto é `database`.

### 2.2. Motivação técnica
O uso de `database` como padrão inicial é obrigatório porque:
- a aplicação já utiliza PostgreSQL como banco oficial
- a base precisa nascer simples, auditável e previsível
- a operação inicial deve reduzir dependências externas
- a fila precisa ser facilmente inspecionável em ambiente de desenvolvimento, homologação e produção

### 2.3. Evolução futura
A adoção futura de Redis ou outro backend de fila não altera este contrato funcional.
Caso isso ocorra:
- a arquitetura funcional deve permanecer a mesma
- os nomes das filas devem permanecer estáveis
- os jobs não podem depender do backend específico
- a mudança deve ser explicitamente documentada como evolução estrutural controlada

---

## 3. Estrutura oficial de processamento assíncrono

### 3.1. Tipos oficiais de execução assíncrona
O projeto reconhece os seguintes tipos oficiais:
- job síncrono de infraestrutura (`sync`) apenas quando explicitamente necessário
- job enfileirado
- batch de jobs
- listener assíncrono
- comando administrativo com execução por tenant ou em massa

### 3.2. Regras obrigatórias
- Nenhuma regra de negócio pesada deve ficar em controller.
- Controller apenas orquestra entrada, validação, chamada de service e response.
- O disparo de job deve ocorrer por service ou fluxo de aplicação apropriado.
- Jobs não devem conter lógica de resolução manual de tenant.
- Jobs não devem acessar headers HTTP diretamente.
- Jobs não devem depender de estado implícito de request.

---

## 4. Filas oficiais

### 4.1. Conceito
A conexão oficial inicial é `database`.
As filas lógicas oficiais do projeto são:

- `high`
- `default`
- `integrations`
- `imports`
- `notifications`
- `maintenance`

### 4.2. Regra obrigatória
Todo job deve declarar explicitamente a fila lógica adequada.
Não é permitido deixar escolha de fila implícita por conveniência sem decisão técnica.

### 4.3. Critérios de uso
- `high`: tarefas críticas e curtas
- `default`: tarefas comuns da aplicação
- `integrations`: comunicação com sistemas externos
- `imports`: importações, processamento pesado e lote
- `notifications`: notificações e rotinas não críticas
- `maintenance`: rotinas administrativas, técnicas e reprocessamentos controlados

---

## 5. Tabelas oficiais de fila

### 5.1. Tabelas nativas obrigatórias
A infraestrutura oficial mínima deve possuir:
- `jobs`
- `failed_jobs`
- `job_batches`

### 5.2. Escopo
Essas tabelas devem existir no schema global da aplicação.
A fila não deve ser segregada por schema de tenant.

### 5.3. Motivação técnica
A fila é uma infraestrutura transversal da plataforma.
O contexto tenant deve existir no payload e no fluxo de execução, não na fragmentação estrutural da fila por schema.

---

## 6. Multi-tenant em jobs e execuções assíncronas

### 6.1. Regra geral
Toda execução assíncrona tenant-aware deve restaurar o contexto tenant antes da regra de negócio.

### 6.2. Regras obrigatórias
- Jobs tenant-aware devem transportar `tenant_id`.
- Listeners tenant-aware devem receber ou inferir `tenant_id` a partir do evento.
- Commands tenant-aware devem receber `tenant_id` explícito ou operar em modo controlado com `--all`.
- Nenhum job, listener ou command tenant-aware pode trocar schema manualmente.
- Nenhuma regra de negócio tenant-aware pode executar sem contexto tenant restaurado.

### 6.3. Fonte oficial do contexto
- O tenant corrente da execução deve ser obtido via `TenantContext`.
- A troca de `search_path` deve ocorrer via `TenantSearchPathService`.
- A orquestração do contexto tenant-aware fora do HTTP deve ocorrer via `TenantExecutionManager`.

### 6.4. Tenant ativo
Somente tenants ativos podem ser processados em jobs tenant-aware.

### 6.5. Falha de tenant
Quando um tenant obrigatório não puder ser resolvido:
- o job deve falhar de forma controlada
- a falha deve ser registrada em log técnico
- a falha não deve ser mascarada
- a mensagem de erro deve ser segura e sem expor detalhes internos desnecessários

---

## 7. Contrato oficial de construção de jobs

### 7.1. Regras obrigatórias
- Todo job deve possuir responsabilidade única.
- Todo job deve ter nome claro e específico.
- Todo job deve operar com payload mínimo necessário.
- Não é permitido serializar payload excessivo sem justificativa técnica.
- Sempre que possível, transportar IDs e DTOs simples em vez de objetos pesados.
- Jobs devem ser idempotentes sempre que a natureza da operação permitir.

### 7.2. O que não é permitido
- colocar regra de negócio difusa no construtor do job
- resolver tenant manualmente dentro do `handle()`
- fazer logging improvisado ou inconsistente
- depender de request atual
- acessar diretamente dados sensíveis desnecessários
- serializar credenciais, tokens ou secrets

### 7.3. Configurações obrigatórias por job
Cada job relevante deve definir explicitamente, conforme necessidade:
- fila lógica
- quantidade de tentativas
- timeout
- backoff
- política de unicidade, quando aplicável
- política de concorrência, quando aplicável

---

## 8. Retry, timeout e concorrência

### 8.1. Política obrigatória
Todo job deve ter comportamento previsível em caso de falha.

### 8.2. Regras
- Jobs críticos devem declarar `tries`.
- Jobs com risco de travamento devem declarar `timeout`.
- Jobs com falha transitória devem declarar `backoff`.
- Jobs idempotentes e sensíveis a duplicidade devem considerar unicidade.
- Jobs que atuam sobre o mesmo recurso devem considerar proteção contra sobreposição de execução.

### 8.3. Proibição
Não é permitido deixar jobs operando indefinidamente sem política explícita quando houver risco operacional.

---

## 9. Dispatch e transações

### 9.1. Regra geral
O disparo de jobs que dependem de persistência anterior deve respeitar o commit da transação.

### 9.2. Regra obrigatória
Quando o job depender de dados gravados na mesma operação transacional:
- o dispatch deve ocorrer com estratégia compatível com execução após commit

### 9.3. Motivação técnica
Isso evita jobs executando com leitura inconsistente, dados ainda não persistidos ou estado parcial de operação.

---

## 10. Logging oficial de filas

### 10.1. Regra geral
Toda execução assíncrona relevante deve gerar logs persistidos em banco.

### 10.2. Tipos mínimos de eventos
Devem existir logs para, quando aplicável:
- dispatch do job
- início de execução
- sucesso
- falha
- retry
- release
- cancelamento de batch
- início e parada de worker
- erro operacional de worker

### 10.3. Campos mínimos
Sempre que aplicável:
- identificador do log
- data/hora do evento
- categoria
- operação
- status
- job_class
- queue_connection
- queue_name
- tenant_id
- tenant_code
- user_id
- oauth_client_id
- request_id
- trace_id
- payload sanitizado de entrada
- payload sanitizado de saída, quando aplicável
- attempt
- max_tries
- duração em milissegundos
- mensagem
- exception_class
- exception_message sanitizada

### 10.4. Persistência oficial
A persistência de logs de fila deve seguir o mesmo padrão centralizado oficial do projeto.
Não é permitido gravar logs de forma improvisada e descentralizada.

---

## 11. Tipos oficiais de log em processamento assíncrono

### 11.1. Log técnico
Usado para:
- ciclo de vida do job
- falhas técnicas
- tempo de execução
- timeout
- retries
- falhas de worker

### 11.2. Log de integração
Usado para:
- chamadas a APIs externas
- envio e recebimento de payload
- códigos de retorno
- falhas de comunicação
- reprocessamentos

### 11.3. Log de auditoria
Usado quando o job:
- alterar estado de negócio relevante
- executar reprocessamento administrativo
- operar com impacto rastreável por usuário, tenant ou entidade

### 11.4. Log de sistema
Usado para:
- eventos internos de infraestrutura
- inicialização/parada de workers
- limpeza
- prune
- falhas globais de fila

---

## 12. Segurança

### 12.1. Proibições obrigatórias
Nunca persistir em payload ou log:
- senhas
- tokens
- authorization header
- client_secret
- credenciais
- segredos de integração

### 12.2. Regras obrigatórias
- Dados sensíveis devem ser mascarados antes da persistência.
- O payload do job deve ser mínimo.
- O erro persistido deve ser útil tecnicamente, mas sem vazar informação sensível.
- O job não deve expor detalhes internos em responses externos.

---

## 13. Observabilidade e correlação

### 13.1. Regra geral
Toda execução assíncrona deve preservar rastreabilidade fim a fim sempre que possível.

### 13.2. Campos oficiais de correlação
- `request_id`
- `trace_id`

### 13.3. Regras obrigatórias
- Se o job nascer de uma requisição HTTP, ele deve herdar os identificadores de correlação relevantes.
- Se o job nascer fora do HTTP, os identificadores devem ser gerados pela infraestrutura.
- Os identificadores devem estar presentes nos logs persistidos de fila sempre que aplicável.

---

## 14. Workers

### 14.1. Regra operacional
A execução contínua em produção deve ser baseada em workers controlados e supervisionados.

### 14.2. Diretriz
O modo de operação preferencial é worker dedicado por fila ou grupo de filas, com política explícita de:
- reinício controlado
- limites de tempo
- limites de quantidade de jobs
- tratamento de memória
- supervisão de processo

### 14.3. Proibição
Não tratar worker como execução informal sem controle operacional.

---

## 15. Batchs e processamento em lote

### 15.1. Regra geral
Batchs devem ser usados apenas quando houver necessidade real de coordenação de múltiplos jobs relacionados.

### 15.2. Regras obrigatórias
- Batch deve possuir objetivo claro.
- Batch deve registrar início, progresso relevante, cancelamento e conclusão.
- Batch não deve ser usado para mascarar ausência de modelagem correta do fluxo.

---

## 16. Reprocessamento

### 16.1. Regra geral
Toda estratégia de reprocessamento deve ser explícita e auditável.

### 16.2. Regras obrigatórias
- O reprocessamento deve informar origem do erro e motivo.
- O reprocessamento deve respeitar contexto tenant quando aplicável.
- O reprocessamento deve gerar log técnico e, quando necessário, log de auditoria.
- O reprocessamento não pode gerar duplicidade indevida de efeitos colaterais.

---

## 17. Testes obrigatórios

### 17.1. A base de filas deve possuir testes cobrindo, no mínimo:
- dispatch de jobs
- execução de jobs
- falha e retry
- restauração de tenant em jobs tenant-aware
- persistência de logs de fila
- comportamento com tenant inválido ou ausente
- proteção básica contra execução fora do contexto correto

### 17.2. Regra obrigatória
Nenhuma funcionalidade crítica baseada em fila deve ser considerada pronta sem cobertura de teste adequada.

---

## 18. Consumo externo futuro

### 18.1. Regra geral
Os logs de fila devem ser estruturados para futura consulta por outras aplicações.

### 18.2. Requisitos
A modelagem de logs deve permitir filtros por:
- período
- job
- fila
- tenant
- request_id
- trace_id
- status
- categoria
- operação

### 18.3. Motivação técnica
Isso permite:
- auditoria operacional
- troubleshooting
- monitoramento
- integração com ferramentas externas
- exposição futura por API administrativa segura

---

## 19. Exceções

### 19.1. Exceção administrativa
Rotinas estritamente administrativas de infraestrutura podem operar de forma diferenciada apenas quando:
- houver justificativa técnica clara
- isso estiver explicitamente documentado
- não se tornar padrão para regra de negócio

### 19.2. Regra obrigatória
Exceção documentada não altera o padrão oficial do projeto.

---

## 20. Regra final

Este contrato é obrigatório para toda implementação atual e futura de processamento assíncrono no projeto.

Sem autorização explícita, não é permitido:
- alterar o driver oficial inicial
- alterar o padrão de filas lógicas
- alterar o modelo de tenancy em jobs
- alterar o padrão de logging de fila
- criar implementação assíncrona fora deste contrato