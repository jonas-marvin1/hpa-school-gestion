@if(count($alertes) > 0)
    <div class="mb-6 space-y-3">
        @foreach($alertes as $alerte)
            @php
                $styles = $alerte['niveau'] === 'danger'
                    ? ['bg-red-50 border-red-300', 'text-red-800', 'text-red-700', 'text-red-600 hover:text-red-800']
                    : ['bg-amber-50 border-amber-300', 'text-amber-800', 'text-amber-700', 'text-amber-700 hover:text-amber-900'];
            @endphp
            <div class="border rounded-lg p-4 flex items-start gap-3 {{ $styles[0] }}" role="alert">
                <svg class="h-5 w-5 flex-shrink-0 mt-0.5 {{ $styles[1] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm {{ $styles[1] }}">{{ $alerte['titre'] }}</p>
                    <p class="text-sm mt-0.5 {{ $styles[2] }}">{{ $alerte['message'] }}</p>
                </div>
                @if(!empty($alerte['lien']))
                    <a href="{{ $alerte['lien'] }}" class="text-sm font-medium whitespace-nowrap underline {{ $styles[3] }}">
                        {{ $alerte['lienLabel'] }}
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
