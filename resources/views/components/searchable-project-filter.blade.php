@props([
    'projects' => collect(),
    'name' => 'project_id',
    'selected' => '',
    'allLabel' => 'All Projects',
    'truncateSelected' => false,
    'enabled' => true,
    'rounded' => 'rounded-lg',
    'id' => 'project-filter',
])

@php
    $triggerId = $id.'-trigger';
    $searchId = $id.'-search';
    $optionsId = $id.'-options';
    $labelId = $id.'-label';
    $projectOptions = $projects->map(fn ($project) => [
        'id' => (string) $project->project_id,
        'name' => (string) $project->project_name,
        'code' => (string) (($project->project_code ?? null) ?: ($project->ref_no ?? '')),
    ])->values();
@endphp

<div class="min-w-0 space-y-1 text-xs font-semibold text-slate-600">
    <label id="{{ $labelId }}" for="{{ $triggerId }}">Project</label>
    <div x-data="searchableProjectDropdown({
        options: @js($projectOptions),
        selectedId: @js((string) $selected),
        enabled: @js((bool) $enabled),
        allLabel: @js($allLabel),
        idPrefix: @js($id),
    })"
         @click.outside="dismiss()"
         @change="selectedId = $refs.project.value"
         @keydown.escape.window="open && dismiss()"
         @keydown.tab="open = false; search = ''"
         @resize.window="open && positionDropdown()"
         @scroll.window.capture="open && positionDropdown()"
         class="relative">
        <input type="hidden" name="{{ $name }}" value="{{ $selected }}" x-ref="project" :disabled="!enabled">
        <button id="{{ $triggerId }}" type="button" x-ref="trigger"
            @click="open ? dismiss() : openDropdown()"
            @keydown.arrow-down.prevent="open ? moveActive(1) : openDropdown()"
            @keydown.arrow-up.prevent="open ? moveActive(-1) : openDropdown()"
            @keydown.enter.prevent="open ? selectActive() : openDropdown()"
            @keydown.space.prevent="open ? selectActive() : openDropdown()"
            @keydown.escape.stop="open && dismiss()"
            :disabled="!enabled" :aria-expanded="open.toString()"
            aria-haspopup="listbox" aria-controls="{{ $optionsId }}" aria-labelledby="{{ $labelId }}"
            class="flex {{ $truncateSelected ? 'h-[42px]' : 'min-h-[42px]' }} w-full min-w-0 items-center justify-between gap-3 {{ $rounded }} border border-slate-300 bg-white px-3 py-2 text-left text-sm font-normal text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
            <span class="{{ $truncateSelected ? 'min-w-0 flex-1 truncate whitespace-nowrap' : 'min-w-0 whitespace-normal break-words' }}" :title="selectedLabel()" x-text="selectedLabel()">{{ $projects->firstWhere('project_id', $selected)?->project_name ?? $allLabel }}</span>
            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
        </button>

        <div x-cloak x-show="open" x-transition.origin.top.left :style="dropdownStyle"
             class="fixed z-[100] flex flex-col overflow-hidden {{ $rounded }} border border-slate-200 bg-white text-slate-700 shadow-xl ring-1 ring-black/5">
            <div class="border-b border-slate-100 p-3">
                <label for="{{ $searchId }}" class="sr-only">Search projects by name or code</label>
                <input id="{{ $searchId }}" x-ref="search" x-model="search"
                    @input="activeIndex = 0"
                    @keydown.arrow-down.prevent="moveActive(1)"
                    @keydown.arrow-up.prevent="moveActive(-1)"
                    @keydown.enter.prevent="selectActive()"
                    @keydown.escape.stop="dismiss()"
                    type="search" role="combobox" aria-autocomplete="list"
                    aria-controls="{{ $optionsId }}" aria-labelledby="{{ $labelId }}"
                    :aria-activedescendant="activeOptionId()" :aria-expanded="open.toString()"
                    placeholder="Search projects by name or code..."
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-normal text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <ul id="{{ $optionsId }}" x-ref="options" role="listbox" aria-label="Projects"
                class="min-h-0 flex-1 overflow-y-auto p-1.5"
                :style="{ maxHeight: `${Math.max(0, parseInt(dropdownStyle.maxHeight ?? '350', 10) - 64)}px` }">
                <template x-for="(option, index) in filteredOptions()" :key="option.id || 'all-projects'">
                    <li :id="optionId(option)" :data-option-index="index" role="option"
                        :aria-selected="(option.id === selectedId).toString()"
                        @click="selectOption(option)" @mouseenter="activeIndex = index"
                        class="flex cursor-pointer items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-sm leading-5 transition"
                        :class="option.id === selectedId ? 'bg-blue-50 font-semibold text-blue-800' : (index === activeIndex ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50')">
                        <span class="min-w-0 whitespace-normal break-words">
                            <span class="block" x-text="option.name"></span>
                            <span x-show="option.code" class="mt-0.5 block text-xs font-normal text-slate-500" x-text="option.code"></span>
                        </span>
                        <svg x-show="option.id === selectedId" class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.415l-7.1 7.16a1 1 0 0 1-1.42.003l-3.9-3.9a1 1 0 1 1 1.414-1.414l3.19 3.19 6.393-6.448a1 1 0 0 1 1.417-.006Z" clip-rule="evenodd" /></svg>
                    </li>
                </template>
                <li x-show="filteredOptions().length === 0" class="px-3 py-4 text-center text-sm text-slate-500">No projects match your search.</li>
            </ul>
        </div>
    </div>
</div>
