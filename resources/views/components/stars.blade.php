@props(['rating' => 0])
<span class="star" title="{{ $rating }}/5">
    @for ($i = 1; $i <= 5; $i++)
        <i class="bi {{ $i <= round($rating) ? 'bi-star-fill' : 'bi-star' }}"></i>
    @endfor
</span>
