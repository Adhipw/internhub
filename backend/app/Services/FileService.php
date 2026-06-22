<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Upload a file to a private directory.
     */
    public function uploadPrivate(UploadedFile $file, string $folder, string $prefix = ''): string
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);

        // Fail-safe protection against executing malicious uploads
        if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'htaccess', 'pl', 'py', 'sh', 'asp', 'aspx', 'exe', 'bat', 'cmd'])) {
            throw new \Exception('Tipe file berbahaya terdeteksi dan diblokir.');
        }

        $secureHash = hash('sha256', $prefix.Str::uuid().microtime());
        $filename = "{$prefix}{$secureHash}.{$extension}";

        return $file->storeAs("private/{$folder}", $filename);
    }

    /**
     * Upload a file to a public directory.
     */
    public function uploadPublic(UploadedFile $file, string $folder, string $prefix = ''): string
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);

        if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'htaccess', 'pl', 'py', 'sh', 'asp', 'aspx', 'exe', 'bat', 'cmd'])) {
            throw new \Exception('Tipe file berbahaya terdeteksi dan diblokir.');
        }

        $secureHash = hash('sha256', $prefix.Str::uuid().microtime());
        $filename = "{$prefix}{$secureHash}.{$extension}";
        $path = $file->storeAs($folder, $filename, 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete a private file.
     */
    public function deletePrivate(?string $path): void
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }
}
