# Contrato oficial de logging e observabilidade

## 1. Correlação
Toda requisição deve possuir:
- request_id
- trace_id

Esses campos devem:
- ser lidos dos headers quando enviados
- ser gerados quando ausentes
- ser armazenados nos atributos da request
- ser devolvidos nos headers da resposta
- ser persistidos nos logs

## 2. Tipos oficiais de log
- log técnico
- log de autenticação
- log de auditoria
- log de integração
- log de sistema

## 3. Campos mínimos obrigatórios
Sempre que aplicável:
- request_id
- trace_id
- tenant_id
- tenant_code
- user_id
- oauth_client_id
- categoria
- operação
- status
- mensagem
- payload de entrada
- payload de saída
- código HTTP
- data/hora

## 4. Persistência
A persistência oficial deve ocorrer por meio do `LogPersistenceService`.

## 5. Segurança
Nunca persistir:
- senhas
- tokens
- authorization header
- client_secret
- credenciais sensíveis

Esses dados devem ser mascarados antes da gravação.

## 6. Consulta futura
Os logs devem permitir filtros por:
- período
- request_id
- trace_id
- tenant
- usuário
- categoria
- status
- operação