@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Rechercher...',
    'required' => false,
])

@php
    $selectedLabel = $selected !== null && isset($options[$selected]) ? $options[$selected] : '';
@endphp

<div
    x-data="{
        open: false,
        search: @js($selectedLabel),
        selectedId: @js($selected ?? ''),
        options: @js($options),
        get filtered() {
            if (!this.search || this.search === this.selectedLabel()) return this.options;
            const q = this.search.toLowerCase();
            return Object.fromEntries(Object.entries(this.options).filter(([id, label]) => label.toLowerCase().includes(q)));
        },
        selectedLabel() {
            return this.options[this.selectedId] ?? '';
        },
        select(id, label) {
            this.selectedId = id;
            this.search = label;
            this.open = false;
        }
    }"
    @click.outside="open = false"
    class="relative"
>
    <input
        type="text"
        x-model="search"
        @focus="open = true"
        @input="open = true; selectedId = ''"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
    >
    <input type="hidden" name="{{ $name }}" :value="selectedId" @if($required) required @endif>

    <ul
        x-show="open"
        style="display: none;"
        class="absolute z-10 mt-1 max-h-56 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
    >
        <template x-for="[id, label] in Object.entries(filtered)" :key="id">
            <li
                @click="select(id, label)"
                class="cursor-pointer select-none px-3 py-2 hover:bg-indigo-50"
                :class="{ 'bg-indigo-50': selectedId == id }"
                x-text="label"
            ></li>
        </template>
        <li x-show="Object.keys(filtered).length === 0" class="px-3 py-2 text-gray-400 select-none">Aucun résultat</li>
    </ul>
</div>
