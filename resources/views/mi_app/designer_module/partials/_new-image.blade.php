<div data-new-image class="rounded-lg border border-dashed border-slate-300 p-4">
    <div class="mb-3 flex items-center justify-between gap-3">
        <label class="text-sm font-semibold" for="image_mode_{{ $index }}">New image</label>
        <button type="button" data-remove-new class="text-sm font-semibold text-red-700">Remove new image</button>
    </div>
    <select id="image_mode_{{ $index }}" data-image-mode class="mb-3 w-full rounded-lg border-slate-300 text-sm">
        <option value="url" selected>Image URL</option>
        <option value="upload">Upload Image</option>
    </select>
    <label for="image_url_{{ $index }}" data-url-label class="mb-2 block text-sm">Image URL</label>
    <input id="image_url_{{ $index }}" type="url" name="image_links[{{ $index }}]" value="{{ $url }}" maxlength="1000" placeholder="https://example.com/photo.jpg" data-image-url class="w-full rounded-lg border-slate-300 text-sm" @error('image_links.'.$index) aria-invalid="true" aria-describedby="image_url_error_{{ $index }}" @enderror>
    <label for="image_upload_{{ $index }}" data-upload-label hidden class="mb-2 block text-sm">Upload Image</label>
    <input id="image_upload_{{ $index }}" type="file" name="product_images[{{ $index }}]" accept="image/jpeg,image/png,image/webp" data-image-upload hidden disabled class="w-full text-sm">
    <img data-new-preview hidden alt="New product image preview" referrerpolicy="no-referrer" class="mt-3 h-40 w-full rounded-lg bg-slate-50 object-contain">
    <p data-preview-message role="status" class="mt-2 text-xs text-slate-500"></p>
    @error('image_links.'.$index)<p id="image_url_error_{{ $index }}" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    @error('product_images.'.$index)<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
