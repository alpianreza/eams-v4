@php($selected = $selectedPages ?? [])
@foreach($pageCatalog as $group)
    <div class="mb-3">
        <div class="fw-semibold small text-uppercase text-body-secondary mb-2">{{ $group['group'] }}</div>
        <div class="row g-2">
            @foreach($group['items'] as $item)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="page_access[]" value="{{ $item['page'] }}" id="pg-{{ $item['page'] }}" @checked(in_array($item['page'], $selected))>
                        <label class="form-check-label" for="pg-{{ $item['page'] }}">{{ $item['label'] }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
