@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null])
<div class="mb-3" id="wrap-{{ $name }}">
    <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
           @if(! in_array($type, ['password', 'file'], true)) value="{{ old($name, $value) }}" @endif
           {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'form-control '.($errors->has($name) ? 'is-invalid' : '')]) }}>
    @if($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
