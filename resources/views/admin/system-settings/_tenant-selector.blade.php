<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route($routeName) }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="tenant">Tenant</label>
                <select class="form-select" id="tenant" name="tenant" required>
                    @forelse ($tenants as $tenant)
                        <option value="{{ $tenant->code }}" @selected($selectedTenant?->id === $tenant->id)>
                            {{ $tenant->name }} ({{ $tenant->code }})
                        </option>
                    @empty
                        <option value="">Nenhum tenant disponível</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit">Carregar configurações</button>
            </div>
        </form>
    </div>
</div>
