<div data-new-image class="tx-edit-new-image">
    <div class="tx-edit-new-image-head">
        <label class="tx-label" for="image_mode_{{ $index }}">New image</label>
        <button type="button" data-remove-new>Remove new image</button>
    </div>
    <select id="image_mode_{{ $index }}" data-image-mode class="tx-field">
        <option value="url" selected>Image URL</option>
        <option value="upload">Upload Image</option>
    </select>
    <label for="image_url_{{ $index }}" data-url-label class="tx-label">Image URL</label>
    <input id="image_url_{{ $index }}" type="url" name="image_links[{{ $index }}]" value="{{ $url }}" maxlength="1000" placeholder="https://example.com/photo.jpg" data-image-url class="tx-field @error('image_links.'.$index) field-invalid @enderror" @error('image_links.'.$index) aria-invalid="true" aria-describedby="image_url_error_{{ $index }}" @enderror>
    <label for="image_upload_{{ $index }}" data-upload-label hidden class="tx-label">Upload Image</label>
    <input id="image_upload_{{ $index }}" type="file" name="product_images[{{ $index }}]" accept="image/jpeg,image/png,image/webp" data-image-upload hidden disabled class="tx-field">
    <img data-new-preview hidden alt="New product image preview" referrerpolicy="no-referrer">
    <p data-preview-message role="status" class="tx-hint"></p>
    @error('image_links.'.$index)<p id="image_url_error_{{ $index }}" class="tx-error">{{ $message }}</p>@enderror
    @error('product_images.'.$index)<p class="tx-error">{{ $message }}</p>@enderror
</div>
