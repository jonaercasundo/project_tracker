<?php

namespace App\Http\Requests;

use App\Models\ProjectInformation;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use ZipArchive;

abstract class BiddingDocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['files' => 'max_files', 'document_ids' => 'max_zip_files'] as $field => $limit) {
            $values = $this->all()[$field] ?? null;
            if (is_array($values) && count($values) > config('bidding.documents.'.$limit)) {
                throw ValidationException::withMessages([$field => 'This request exceeds the allowed number of files.']);
            }
        }
    }

    public function authorize(): bool
    {
        $bidding = $this->route('bidding');
        if (! $bidding instanceof ProjectInformation) {
            return false;
        }
        Gate::authorize($this->ability(), $bidding);
        foreach (['document', 'folder'] as $parameter) {
            $resource = $this->route($parameter);
            if ($resource && (int) $resource->project_information_id !== (int) $bidding->id) {
                abort(404);
            }
        }

        return true;
    }

    protected function ability(): string
    {
        return 'update';
    }

    /** @return array<int, mixed> */
    protected function fileRules(): array
    {
        return ['bail', 'required', 'file', 'max:'.config('bidding.documents.max_file_size_kb'),
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }
                $extension = strtolower($value->getClientOriginalExtension());
                $mime = $value->getMimeType();
                $allowed = config('bidding.documents.mime_types.'.$extension, []);
                if (! in_array($extension, config('bidding.documents.extensions'), true) || ! in_array($mime, $allowed, true)) {
                    $fail('The '.$attribute.' file content must match an allowed file extension.');

                    return;
                }
                if (in_array($extension, ['docx', 'xlsx'], true) && $mime === 'application/zip') {
                    $archive = new ZipArchive;
                    if ($archive->open($value->getRealPath(), ZipArchive::RDONLY) !== true) {
                        $fail('The '.$attribute.' Office document is not a valid archive.');

                        return;
                    }
                    $expected = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
                    $isOfficeDocument = $archive->locateName('[Content_Types].xml') !== false && $archive->locateName($expected) !== false;
                    $archive->close();
                    if (! $isOfficeDocument) {
                        $fail('The '.$attribute.' file is not a valid '.$extension.' document.');
                    }
                }
            },
        ];
    }

    /** @return array<int, mixed> */
    protected function displayNameRules(): array
    {
        return ['string', 'min:1', 'max:255',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && (str_contains($value, '/') || str_contains($value, chr(92)) || preg_match('/[\x00-\x1f\x7f]/u', $value))) {
                    $fail('The '.$attribute.' cannot contain slashes or control characters.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'files.required' => 'Choose at least one file to upload.',
            'files.max' => 'Too many files were selected for one upload.',
            'files.*.max' => 'Each file must be no larger than '.config('bidding.documents.max_file_size_kb').' KB.',
            'file.max' => 'The replacement file must be no larger than '.config('bidding.documents.max_file_size_kb').' KB.',
        ];
    }
}
