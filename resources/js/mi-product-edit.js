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

    form.querySelectorAll('[data-add-value]').forEach((button) => {
        const input = form.querySelector(`[data-custom-value="${button.dataset.addValue}"]`);
        const addValue = () => {
            const value = input.value.trim();
            if (!value) return;
            const select = document.getElementById(button.dataset.addValue);
            const existing = [...select.options].find((option) => option.value === value);
            if (existing) existing.selected = true;
            else select.add(new Option(value, value, true, true));
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
