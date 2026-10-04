@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 4, 'help' => null])
<div class="mb-3" id="wrap-{{ $name }}">
    <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'form-control '.($errors->has($name) ? 'is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>
    @if($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
