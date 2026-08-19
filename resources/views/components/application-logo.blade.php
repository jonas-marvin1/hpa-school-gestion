<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <img src="{{ asset('images/logo.png') }}" alt="HPA Logo" class="h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
    <div style="display: none;" class="text-2xl font-extrabold tracking-tight">
        <span class="text-hpa-blue">HPA</span> <span class="text-hpa-orange">Academy</span>
    </div>
</div>
