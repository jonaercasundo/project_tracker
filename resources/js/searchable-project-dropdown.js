export function filterProjectOptions(options, search, allLabel = 'All Projects') {
    const normalizedSearch = search.trim().toLocaleLowerCase();
    const availableOptions = [{ id: '', name: allLabel, code: '' }, ...options];

    if (!normalizedSearch) {
        return availableOptions;
    }

    return availableOptions.filter((option) => `${option.name} ${option.code}`.toLocaleLowerCase().includes(normalizedSearch));
}

export function nextProjectOptionIndex(currentIndex, direction, optionCount) {
    if (optionCount < 1) {
        return 0;
    }

    return (currentIndex + direction + optionCount) % optionCount;
}

export function searchableProjectDropdown(config) {
    return {
        options: config.options,
        selectedId: config.selectedId,
        enabled: config.enabled,
        submitOnChange: config.submitOnChange ?? false,
        allLabel: config.allLabel,
        idPrefix: config.idPrefix,
        search: '',
        open: false,
        activeIndex: 0,
        dropdownStyle: {},
        filteredOptions() {
            return filterProjectOptions(this.options, this.search, this.allLabel);
        },
        selectedLabel() {
            const option = this.options.find((candidate) => candidate.id === this.selectedId);

            if (!option) {
                return this.allLabel;
            }

            return option.code ? `${option.name} · ${option.code}` : option.name;
        },
        optionId(option) {
            return `${this.idPrefix}-option-${option.id || 'all'}`;
        },
        activeOptionId() {
            const option = this.filteredOptions()[this.activeIndex];

            return option ? this.optionId(option) : null;
        },
        positionDropdown() {
            const trigger = this.$refs.trigger.getBoundingClientRect();
            const margin = 8;
            const width = Math.min(Math.max(trigger.width, 360), 480, window.innerWidth - margin * 2);
            const left = Math.max(margin, Math.min(trigger.left, window.innerWidth - width - margin));
            const below = window.innerHeight - trigger.bottom - margin;
            const above = trigger.top - margin;
            const placeBelow = below >= 220 || below >= above;
            const maxHeight = Math.min(350, Math.max(0, placeBelow ? below : above));
            const top = placeBelow ? trigger.bottom + 4 : trigger.top - maxHeight - 4;

            this.dropdownStyle = { top: `${top}px`, left: `${left}px`, width: `${width}px`, maxHeight: `${maxHeight}px` };
        },
        openDropdown() {
            if (!this.enabled) {
                return;
            }

            this.open = true;
            const selectedIndex = this.filteredOptions().findIndex((option) => option.id === this.selectedId);
            this.activeIndex = Math.max(0, selectedIndex);
            this.positionDropdown();
            this.$nextTick(() => this.$refs.search.focus());
        },
        dismiss() {
            this.open = false;
            this.search = '';
        },
        moveActive(direction) {
            const options = this.filteredOptions();

            this.activeIndex = nextProjectOptionIndex(this.activeIndex, direction, options.length);
            this.$nextTick(() => this.$refs.options?.querySelector(`[data-option-index='${this.activeIndex}']`)?.scrollIntoView({ block: 'nearest' }));
        },
        selectActive() {
            const option = this.filteredOptions()[this.activeIndex];

            if (option) {
                this.selectOption(option);
            }
        },
        selectOption(option) {
            this.selectedId = option.id;
            this.$refs.project.value = option.id;
            this.$refs.project.dispatchEvent(new Event('change', { bubbles: true }));
            if (this.submitOnChange) {
                this.$refs.project.form?.requestSubmit();
            }
            this.dismiss();
            this.$refs.trigger.focus();
        },
    };
}

if (typeof window !== 'undefined') {
    window.searchableProjectDropdown = searchableProjectDropdown;
}
