<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Base\AbstractTenantAwareJob;
use App\Services\Billing\InvoiceSyncService;

/**
 * Job responsável por orquestrar a sincronização assíncrona de faturas
 * dentro do contexto de um tenant específico.
 *
 * Objetivo arquitetural:
 * - representar o padrão oficial de job tenant-aware da aplicação;
 * - demonstrar como um job deve carregar apenas o contexto técnico mínimo;
 * - demonstrar como restaurar corretamente o contexto do tenant;
 * - demonstrar que a regra de negócio não deve ficar no job.
 *
 * Responsabilidades deste job:
 * - receber o identificador do tenant;
 * - receber o payload mínimo necessário para a sincronização;
 * - carregar metadados técnicos de correlação, quando existirem;
 * - definir explicitamente a connection e a queue do processamento;
 * - executar a regra principal dentro do contexto correto do tenant;
 * - delegar a lógica de negócio ao service apropriado.
 *
 * O que este job NÃO deve fazer:
 * - não deve conter regra de negócio complexa;
 * - não deve trocar schema manualmente;
 * - não deve acessar dados da requisição HTTP;
 * - não deve resolver tenant por header ou por contexto implícito;
 * - não deve executar integração externa diretamente se isso pertencer ao service;
 * - não deve persistir logs manualmente de forma improvisada.
 *
 * Fluxo esperado:
 * 1. o job é despachado com tenant_id + payload mínimo;
 * 2. o worker consome o job da fila configurada;
 * 3. o método handle() é executado;
 * 4. o job restaura o contexto do tenant via runInTenantContext(...);
 * 5. o service de sincronização é chamado já dentro do tenant correto;
 * 6. o service executa a regra de negócio real.
 *
 * Este job deve ser usado como referência para novos jobs tenant-aware,
 * especialmente os ligados a integrações, sincronismos, importações
 * e processamento assíncrono por tenant.
 */
final class SyncInvoicesJob extends AbstractTenantAwareJob
{
    /**
     * Payload mínimo necessário para a operação de sincronização.
     *
     * Regras para este payload:
     * - deve conter apenas os dados estritamente necessários;
     * - deve evitar objetos pesados ou estruturas excessivas;
     * - deve ser serializável com segurança;
     * - não deve conter credenciais, tokens, secrets ou dados sensíveis
     *   sem necessidade técnica explícita.
     *
     * @var array<string, mixed>
     */
    private readonly array $payload;

    /**
     * Cria uma nova instância do job.
     *
     * Parâmetros técnicos:
     * - $tenantId: tenant ao qual a execução pertence;
     * - $payload: dados mínimos para o service executar a sincronização;
     * - $requestId: identificador de correlação da requisição original, quando houver;
     * - $traceId: identificador distribuído de rastreabilidade, quando houver;
     * - $userId: usuário responsável pela origem da operação, quando aplicável;
     * - $oauthClientId: cliente OAuth associado à origem da chamada, quando aplicável.
     *
     * Decisões arquiteturais deste construtor:
     * - delega a inicialização do contexto técnico ao pai;
     * - define explicitamente a conexão de fila;
     * - define explicitamente a fila lógica;
     * - não executa regra de negócio;
     * - não valida regra de domínio;
     * - não resolve tenant aqui.
     *
     * Por que definir connection e queue no construtor?
     * - para deixar o comportamento do job explícito;
     * - para impedir despacho implícito em fila errada;
     * - para manter previsibilidade operacional.
     *
     * Neste exemplo:
     * - connection = database
     * - queue = integrations
     *
     * @param int $tenantId
     * @param array<string, mixed> $payload
     * @param string|null $requestId
     * @param string|null $traceId
     * @param int|null $userId
     * @param int|null $oauthClientId
     */
    public function __construct(
        int $tenantId,
        array $payload,
        ?string $requestId = null,
        ?string $traceId = null,
        ?int $userId = null,
        ?int $oauthClientId = null
    ) {
        parent::__construct(
            tenantId: $tenantId,
            requestId: $requestId,
            traceId: $traceId,
            userId: $userId,
            oauthClientId: $oauthClientId
        );

        $this->payload = $payload;

        /**
         * Define explicitamente a conexão da fila.
         *
         * Neste projeto, a base oficial inicial de filas utiliza o driver
         * "database". Deixar isso explícito ajuda a evitar ambiguidade
         * operacional e facilita a leitura do comportamento do job.
         */
        $this->onConnection('database');

        /**
         * Define explicitamente a fila lógica.
         *
         * Como este job representa sincronização com contexto de integração,
         * a fila "integrations" é a mais adequada para:
         * - separar esse fluxo de jobs genéricos;
         * - permitir tuning operacional específico;
         * - facilitar monitoramento e troubleshooting.
         */
        $this->onQueue('integrations');
    }

    /**
     * Executa o job.
     *
     * Este método não contém a regra de negócio de sincronização.
     * Ele apenas:
     * - restaura o contexto tenant-aware correto;
     * - delega a execução ao service de domínio/aplicação.
     *
     * Motivação arquitetural:
     * - o job é um orquestrador assíncrono;
     * - o service é o centro da regra de negócio;
     * - o contexto tenant precisa estar correto antes da regra principal.
     *
     * Por que usar runInTenantContext(...)?
     * - porque a aplicação é multi-tenant por schema;
     * - porque o job pode estar sendo executado fora do ciclo HTTP;
     * - porque o worker não pode depender de contexto implícito;
     * - porque o schema correto precisa ser restaurado formalmente.
     *
     * O método runInTenantContext(...) deve:
     * - localizar o tenant;
     * - validar que ele está apto para execução;
     * - ajustar o contexto corrente;
     * - ajustar o search_path;
     * - executar o callback;
     * - restaurar o contexto anterior ao final.
     *
     * @param InvoiceSyncService $service
     * @return void
     */
    public function handle(InvoiceSyncService $service): void
    {
        $this->runInTenantContext(function () use ($service): void {
            /**
             * Toda a regra de sincronização permanece no service.
             *
             * Isso mantém:
             * - separação de responsabilidades;
             * - previsibilidade arquitetural;
             * - facilidade de teste;
             * - reutilização da regra em outros fluxos.
             */
            $service->sync($this->payload);
        });
    }
}