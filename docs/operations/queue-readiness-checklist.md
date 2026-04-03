# Checklist final de prontidão da infraestrutura de fila

## Objetivo

Este documento define os critérios obrigatórios para considerar a infraestrutura de fila apta para uso em funcionalidades reais do projeto.

Nenhum módulo de negócio deve começar a depender de fila sem que este checklist esteja integralmente atendido.

---

## 1. Configuração base

### Obrigatório
- [ ] `QUEUE_CONNECTION=database` no ambiente correto
- [ ] `config/queue.php` com conexão `database` ativa
- [ ] `after_commit=true` na conexão oficial
- [ ] `retry_after` coerente com os `timeout` dos jobs
- [ ] `.env.testing` apontando para banco de teste separado
- [ ] `phpunit.xml` alinhado com o ambiente de teste

### Evidência esperada
- configuração revisada
- teste de infraestrutura passando

---

## 2. Estrutura de banco

### Obrigatório
- [ ] tabela `jobs` existente
- [ ] tabela `failed_jobs` existente
- [ ] tabela `job_batches` existente
- [ ] tabela `queue_job_logs` existente
- [ ] tabela `queue_worker_logs` existente
- [ ] índices essenciais presentes
- [ ] migrations funcionando em desenvolvimento e teste

### Evidência esperada
- `php artisan migrate:status`
- testes estruturais verdes

---

## 3. Contrato de jobs

### Obrigatório
- [ ] jobs tenant-aware herdam da base oficial
- [ ] jobs usam payload mínimo
- [ ] jobs não contêm regra de negócio complexa
- [ ] jobs definem explicitamente fila e connection
- [ ] jobs não acessam request HTTP
- [ ] jobs não trocam schema manualmente

### Evidência esperada
- revisão de código
- testes unitários da base tenant-aware

---

## 4. Tenancy

### Obrigatório
- [ ] `tenant_id` é transportado em jobs tenant-aware
- [ ] contexto tenant é restaurado antes da regra de negócio
- [ ] `TenantExecutionManager` é o único orquestrador oficial
- [ ] contexto anterior é restaurado ao final
- [ ] schema volta para `public` após execução fora de escopo tenant
- [ ] tenant inativo ou inexistente gera falha controlada

### Evidência esperada
- testes de execução tenant-aware
- testes de restauração de contexto

---

## 5. Dispatch

### Obrigatório
- [ ] dispatch centralizado em service
- [ ] uso padrão de `afterCommit`
- [ ] `beforeCommit` só por exceção explícita
- [ ] `queueName` e `connectionName` controlados
- [ ] metadados técnicos propagados corretamente

### Evidência esperada
- testes de dispatch
- testes de after commit

---

## 6. Worker

### Obrigatório
- [ ] workers executados com `queue:work`
- [ ] filas lógicas separadas
- [ ] política de reinício definida
- [ ] processo supervisionado em produção
- [ ] parâmetros de `sleep`, `tries`, `timeout`, `max-jobs` e `max-time` definidos

### Evidência esperada
- documentação operacional
- execução manual validada
- unit file / supervisor config pronta

---

## 7. Retry, timeout e concorrência

### Obrigatório
- [ ] todo job relevante define `tries`
- [ ] todo job relevante define `timeout`
- [ ] jobs com falha transitória definem `backoff`
- [ ] jobs com risco de duplicidade usam `ShouldBeUnique` quando necessário
- [ ] jobs com risco de concorrência usam `WithoutOverlapping` quando necessário
- [ ] `retry_after` maior que o tempo real máximo esperado do job

### Evidência esperada
- revisão de jobs
- testes dos contratos operacionais

---

## 8. Logging da fila

### Obrigatório
- [ ] eventos `dispatched`, `started`, `succeeded`, `failed` são persistidos
- [ ] payload é sanitizado
- [ ] `job_class` é corretamente resolvido
- [ ] `request_id`, `trace_id`, `tenant_id`, `user_id` e `oauth_client_id` são persistidos quando aplicáveis
- [ ] mensagens de erro são úteis e seguras
- [ ] logs não dependem de implementação manual dentro dos jobs

### Evidência esperada
- testes do `QueueExecutionLogService`
- testes do sanitizer
- inspeção nas tabelas de log

---

## 9. Segurança

### Obrigatório
- [ ] senha não é persistida em logs
- [ ] token não é persistido em logs
- [ ] `Authorization` não é persistido
- [ ] `client_secret` não é persistido
- [ ] payload sensível é mascarado
- [ ] jobs não serializam credenciais desnecessárias

### Evidência esperada
- testes de sanitização
- revisão dos payloads reais dos jobs

---

## 10. Testes mínimos obrigatórios

### Obrigatório
- [ ] teste de configuração da fila
- [ ] teste de existência das tabelas nativas
- [ ] teste de existência das tabelas de log
- [ ] teste do contrato do job tenant-aware
- [ ] teste de dispatch
- [ ] teste de after commit
- [ ] teste de sanitização
- [ ] teste de logging do evento `dispatched`
- [ ] teste tenant-aware de restauração de contexto
- [ ] teste de falha real ou critério equivalente explicitamente documentado

### Evidência esperada
- suíte de testes verde

---

## 11. Operação

### Obrigatório
- [ ] worker manual validado localmente
- [ ] job simples testado ponta a ponta
- [ ] job falho testado ponta a ponta
- [ ] `failed_jobs` validada
- [ ] reinício via `queue:restart` validado
- [ ] procedimento de deploy documentado

### Evidência esperada
- execução manual comprovada
- documentação operacional pronta

---

## 12. Critério de liberação para uso real

A infraestrutura de fila só pode ser considerada pronta para módulos reais quando:

- [ ] todos os itens anteriores estiverem atendidos
- [ ] não houver teste vermelho de infraestrutura de fila
- [ ] pelo menos um job real simples tiver sido integrado com sucesso
- [ ] pelo menos um fluxo com tenant tiver sido validado ponta a ponta
- [ ] a equipe souber como subir, reiniciar e observar workers

---

## Resultado final

### Situação permitida para uso real
- todos os itens obrigatórios atendidos
- testes verdes
- operação mínima validada

### Situação NÃO permitida para uso real
- tabelas de log inexistentes
- dispatch sem política transacional
- jobs tenant-aware sem base oficial
- logging improvisado
- worker sem documentação
- falhas não observáveis
- testes de infraestrutura quebrados