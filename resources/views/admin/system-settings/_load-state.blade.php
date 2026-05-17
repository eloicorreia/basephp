@if (($tenants ?? collect())->isEmpty())
    <div class="alert alert-warning">Nenhum tenant ativo disponível para configuração.</div>
@elseif (! empty($loadError))
    <div class="alert alert-warning">{{ $loadError }}</div>
@endif
