@props(['name', 'label', 'options' => [], 'selected' => null, 'required' => false, 'placeholder' => null, 'help' => null])
<div class="mb-3" id="wrap-{{ $name }}">
    <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}"
        {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'form-select '.($errors->has($name) ? 'is-invalid' : '')]) }}>
        @if($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $key => $text)
            <option value="{{ $key }}" @selected((string) old($name, $selected) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @if($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
