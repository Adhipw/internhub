<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Decode JSON strings if they are sent as part of multipart/form-data
        if (is_string($this->education)) {
            $this->merge([
                'education' => json_decode($this->education, true),
            ]);
        }

        if (is_string($this->skills)) {
            $this->merge([
                'skills' => json_decode($this->skills, true),
            ]);
        }

        if (is_string($this->ai_consent)) {
            $this->merge([
                'ai_consent' => filter_var($this->ai_consent, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'bio' => 'nullable|string|max:1000',
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'address' => 'nullable|string|max:500',
            'education' => 'nullable|array',
            'education.*.school' => 'required|string|max:255',
            'education.*.degree' => 'required|string|max:255',
            'education.*.field' => 'nullable|string|max:255',
            'education.*.start_year' => 'required|integer|min:1900|max:'.date('Y'),
            'education.*.end_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 10),
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:50',
            'cv' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if ($value && $value->isValid()) {
                        // Real MIME-type inspection via finfo
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $realMime = $finfo->file($value->getPathname());
                        if ($realMime !== 'application/pdf') {
                            $fail('CV wajib berupa dokumen PDF asli.');

                            return;
                        }

                        // Verify Magic Bytes (%PDF)
                        $handle = fopen($value->getPathname(), 'r');
                        $magic = fread($handle, 4);
                        fclose($handle);
                        if ($magic !== '%PDF') {
                            $fail('Keamanan Unggahan: Struktur dokumen PDF tidak valid.');
                        }
                    }
                },
            ],
            'portfolio' => [
                'nullable',
                'file',
                'mimes:pdf,zip,rar',
                'max:5120',
                function ($attribute, $value, $fail) {
                    if ($value && $value->isValid()) {
                        // Real MIME-type inspection via finfo
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $realMime = $finfo->file($value->getPathname());

                        $allowed = [
                            'application/pdf',
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/x-rar',
                            'application/x-rar-compressed',
                            'application/octet-stream',
                        ];

                        if (! in_array($realMime, $allowed)) {
                            $fail('Portofolio harus berupa file PDF, ZIP, atau RAR asli.');

                            return;
                        }

                        // Verify Magic Bytes
                        $handle = fopen($value->getPathname(), 'r');
                        $magic = fread($handle, 4);
                        fclose($handle);

                        $isPdf = ($magic === '%PDF');
                        $isZip = ($magic === "PK\x03\x04");
                        $isRar = (str_starts_with($magic, 'Rar!'));

                        if (! $isPdf && ! $isZip && ! $isRar) {
                            $fail('Keamanan Unggahan: Struktur file Portofolio tidak valid.');
                        }
                    }
                },
            ],
            'ai_consent' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => 'Nomor WhatsApp sudah digunakan oleh akun lain.',
            'cv.mimes' => 'CV harus berupa file PDF.',
            'cv.max' => 'Ukuran CV maksimal 2MB.',
            'portfolio.mimes' => 'Portofolio harus berupa file PDF, ZIP, atau RAR.',
            'portfolio.max' => 'Ukuran Portofolio maksimal 5MB.',
            'education.*.school.required' => 'Nama institusi pendidikan wajib diisi.',
            'education.*.degree.required' => 'Gelar/Jenjang pendidikan wajib diisi.',
        ];
    }
}
