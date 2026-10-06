const form = document.getElementById('edit_product_form');

if (form) {
    let dirty = false;
    let submitting = false;
    const status = document.getElementById('save_status');
    const markDirty = () => {
        dirty = true;
        status.textContent = 'You have unsaved changes.';
    };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    window.addEventListener('beforeunload', (event) => {
        if (dirty && !submitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
    form.addEventListener('submit', (event) => {
        if (submitting) {
            event.preventDefault();
            return;
        }
        submitting = true;
        form.setAttribute('aria-busy', 'true');
        document.querySelectorAll('[data-save]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Saving…';
        });
        status.textContent = 'Saving your changes…';
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        form.removeAttribute('aria-busy');
        document.querySelectorAll('[data-save]').forEach((button) => {
            button.disabled = false;
            button.textContent = 'Save Changes';
        });
    });

    const taxonomy = ['category_id', 'sub_category_id', 'product_type_id', 'collection_id'].map((name) => form.elements.namedItem(name));
    const filterTaxonomy = (clearInvalid) => {
        taxonomy.slice(1).forEach((select, index) => {
            const parent = taxonomy[index];
            [...select.options].forEach((option) => {
                const invalid = option.value !== '' && option.dataset.parentId !== parent.value;
                option.hidden = invalid && !option.selected;
                option.disabled = invalid && (clearInvalid || !option.selected);
                if (clearInvalid && invalid && option.selected) select.value = '';
            });
        });
    };
    taxonomy.forEach((select) => select.addEventListener('change', () => filterTaxonomy(true)));
    filterTaxonomy(false);

    form.querySelectorAll('select.tx-multi-select').forEach((select) => {
        const wrapper = select.closest('.tx-multi-select-wrap');
        const chips = wrapper.querySelector('.tx-multi-chips');
        const clearButton = wrapper.querySelector('.tx-multi-clear');
        const selectionError = wrapper.querySelector('[data-multi-error]');

        if (typeof window.TomSelect === 'function') {
            new window.TomSelect(select, {
                plugins: ['remove_button'],
                create: false,
                maxItems: 100,
                hideSelected: true,
                closeAfterSelect: false,
                copyClassesToDropdown: false,
                placeholder: `Select one or more ${select.id === 'materials' ? 'materials' : 'colors'}...`,
                searchField: ['text'],
                render: {
                    no_results: () => `<div class="no-results">No ${select.id === 'materials' ? 'material' : 'color'} found</div>`,
                },
            });
            wrapper.querySelector('[data-native-multi-hint]').hidden = true;
            if (select.hasAttribute('aria-describedby')) {
                select.tomselect.control_input.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
            }
            if (select.hasAttribute('aria-invalid')) {
                select.tomselect.control_input.setAttribute('aria-invalid', select.getAttribute('aria-invalid'));
            }
        }

        const updateChips = () => {
            chips.replaceChildren();
            [...select.selectedOptions].filter((option) => option.value).forEach((option) => {
                const chip = document.createElement('span');
                chip.className = 'tx-multi-chip';
                const label = document.createElement('span');
                label.textContent = option.text;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '\u00d7';
                remove.setAttribute('aria-label', `Remove ${option.text}`);
                remove.addEventListener('click', () => {
                    if (select.tomselect) {
                        select.tomselect.removeItem(option.value);
                    } else {
                        option.selected = false;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                chip.append(label, remove);
                chips.append(chip);
            });
            clearButton.hidden = chips.childElementCount === 0;
        };

        select.addEventListener('change', () => {
            updateChips();
            if (select.validity.valid) {
                selectionError.hidden = true;
                select.classList.remove('field-invalid');
                select.removeAttribute('aria-invalid');
                if (select.tomselect) {
                    select.tomselect.wrapper.classList.remove('field-invalid');
                    select.tomselect.control_input.removeAttribute('aria-invalid');
                    if (select.hasAttribute('aria-describedby')) {
                        select.tomselect.control_input.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
                    } else {
                        select.tomselect.control_input.removeAttribute('aria-describedby');
                    }
                }
            }
        });
        select.addEventListener('invalid', (event) => {
            if (!select.tomselect) return;
            event.preventDefault();
            selectionError.hidden = false;
            select.tomselect.wrapper.classList.add('field-invalid');
            select.tomselect.control_input.setAttribute('aria-invalid', 'true');
            const descriptions = [select.getAttribute('aria-describedby'), selectionError.id].filter(Boolean);
            select.tomselect.control_input.setAttribute('aria-describedby', descriptions.join(' '));
            select.tomselect.focus();
        });
        clearButton.addEventListener('click', () => {
            if (select.tomselect) {
                select.tomselect.clear();
            } else {
                [...select.options].forEach((option) => { option.selected = false; });
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        updateChips();
    });

    form.querySelectorAll('[data-add-value]').forEach((button) => {
        const input = form.querySelector(`[data-custom-value="${button.dataset.addValue}"]`);
        const addValue = () => {
            const value = input.value.trim();
            if (!value) return;
            const select = document.getElementById(button.dataset.addValue);
            if (select.tomselect) {
                select.tomselect.addOption({ value, text: value });
                select.tomselect.addItem(value);
            } else {
                const existing = [...select.options].find((option) => option.value === value);
                if (existing) existing.selected = true;
                else select.add(new Option(value, value, true, true));
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            input.value = '';
            markDirty();
        };
        button.addEventListener('click', addValue);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addValue();
            }
        });
    });

    const existingImages = document.getElementById('existing_images');
    const updatePrimary = () => {
        existingImages.querySelectorAll('[data-image-id]').forEach((card) => {
            card.querySelector('[data-primary-badge]').hidden = !card.querySelector('[type="radio"]').checked;
        });
    };
    existingImages.addEventListener('change', (event) => {
        if (event.target.type === 'checkbox') {
            const checkbox = event.target;
            if (checkbox.checked && !window.confirm('Remove this image when you save changes?')) {
                checkbox.checked = false;
                return;
            }
            const card = checkbox.closest('[data-image-id]');
            const radio = card.querySelector('[type="radio"]');
            radio.disabled = checkbox.checked;
            if (checkbox.checked && radio.checked) {
                radio.checked = false;
                const replacement = existingImages.querySelector('[type="radio"]:not(:disabled)');
                if (replacement) replacement.checked = true;
            }
            card.querySelector('[data-removal-note]').hidden = !checkbox.checked;
        }
        updatePrimary();
    });
    existingImages.addEventListener('click', (event) => {
        const button = event.target.closest('[data-move]');
        if (!button) return;
        const card = button.closest('[data-image-id]');
        if (button.dataset.move === 'up' && card.previousElementSibling) {
            card.previousElementSibling.before(card);
            markDirty();
        } else if (button.dataset.move === 'down' && card.nextElementSibling) {
            card.nextElementSibling.after(card);
            markDirty();
        }
    });
    form.querySelectorAll('[data-image-preview]').forEach((preview) => {
        const unavailable = () => {
            preview.hidden = true;
            preview.parentElement.querySelector('[data-preview-error]').hidden = false;
        };
        preview.addEventListener('error', unavailable);
        if (preview.complete && preview.naturalWidth === 0) unavailable();
    });

    const newImages = document.getElementById('new_images');
    let nextImageIndex = Math.max(-1, ...[...newImages.querySelectorAll('[data-image-url]')].map((input) => Number(input.name.match(/\[(\d+)\]/)[1]))) + 1;
    const initializeImage = (row) => {
        const mode = row.querySelector('[data-image-mode]');
        const url = row.querySelector('[data-image-url]');
        const upload = row.querySelector('[data-image-upload]');
        const preview = row.querySelector('[data-new-preview]');
        const message = row.querySelector('[data-preview-message]');
        let objectUrl;
        let debounce;
        const releaseUrl = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = undefined;
        };
        const updatePreview = () => {
            releaseUrl();
            preview.hidden = true;
            preview.removeAttribute('src');
            message.textContent = '';
            if (mode.value === 'upload') {
                const file = upload.files[0];
                upload.setCustomValidity('');
                if (!file) return;
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 20 * 1024 * 1024) {
                    message.textContent = 'Choose a JPEG, PNG or WebP image no larger than 20 MB.';
                    upload.setCustomValidity(message.textContent);
                    return;
                }
                objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
            } else {
                url.setCustomValidity('');
                if (!url.value.trim()) return;
                try {
                    const parsed = new URL(url.value.trim());
                    if (!['http:', 'https:'].includes(parsed.protocol)) throw new Error('Invalid protocol');
                    preview.src = parsed.href;
                } catch {
                    message.textContent = 'Enter a complete HTTP or HTTPS image URL.';
                    url.setCustomValidity(message.textContent);
                    return;
                }
            }
            preview.hidden = false;
        };
        preview.addEventListener('error', () => {
            preview.hidden = true;
            message.textContent = 'Preview unavailable. Check that the link points directly to an accessible image.';
        });
        preview.addEventListener('load', () => { message.textContent = 'Image preview ready.'; });
        mode.addEventListener('change', () => {
            const isUpload = mode.value === 'upload';
            url.disabled = isUpload;
            url.hidden = isUpload;
            upload.disabled = !isUpload;
            upload.hidden = !isUpload;
            row.querySelector('[data-url-label]').hidden = isUpload;
            row.querySelector('[data-upload-label]').hidden = !isUpload;
            updatePreview();
        });
        url.addEventListener('input', () => {
            url.setCustomValidity('');
            clearTimeout(debounce);
            debounce = setTimeout(updatePreview, 350);
        });
        upload.addEventListener('change', updatePreview);
        row.querySelector('[data-remove-new]').addEventListener('click', () => {
            clearTimeout(debounce);
            releaseUrl();
            row.remove();
            markDirty();
        });
        updatePreview();
    };
    newImages.querySelectorAll('[data-new-image]').forEach(initializeImage);
    document.getElementById('add_image').addEventListener('click', () => {
        const template = document.getElementById('new_image_template');
        const holder = document.createElement('template');
        holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextImageIndex++));
        const row = holder.content.firstElementChild;
        newImages.append(row);
        initializeImage(row);
        row.querySelector('[data-image-mode]').focus();
        markDirty();
    });
}
