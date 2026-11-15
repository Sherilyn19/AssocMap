@props(['label'])
@php $infoId = 'area-info-'.\Illuminate\Support\Str::uuid(); @endphp
<span class="area-info">
    <button type="button" aria-label="{{ $label }}" aria-expanded="false" aria-controls="{{ $infoId }}" data-area-info><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.02M12 8.25h.008v.008H12V8.25ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></button>
    <span id="{{ $infoId }}" class="area-info-popover">{{ $slot }}</span>
</span>
