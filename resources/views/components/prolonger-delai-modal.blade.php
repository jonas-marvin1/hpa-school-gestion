@props(['action', 'label', 'studentId' => null, 'triggerLabel' => 'Prolonger le délai', 'triggerClass' => 'text-indigo-600 hover:underline text-sm'])

{{--
    Suppose un x-data="{ openProlonger: false }" pose sur un ancetre (la
    ligne du tableau) : composant reutilise pour la prolongation collective
    (assignments/index) et individuelle (evaluations/index), fiche du
    14/09/2026, point 3.
--}}
<button type="button" @click="openProlonger = true" class="{{ $triggerClass }}">{{ $triggerLabel }}</button>

<div x-show="openProlonger" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="openProlonger" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openProlonger = false" aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="openProlonger" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full whitespace-normal">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if($studentId)
                    <input type="hidden" name="student_id" value="{{ $studentId }}">
                @endif
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Prolonger le délai</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ $label }}</p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouvelle date limite *</label>
                        <input type="datetime-local" name="new_due_date" required min="{{ now()->format('Y-m-d\TH:i') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Motif *</label>
                        <textarea name="motif" rows="3" required maxlength="1000" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                        Confirmer la prolongation
                    </button>
                    <button type="button" @click="openProlonger = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
