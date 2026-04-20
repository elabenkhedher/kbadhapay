<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploaderService
{
    // Allowed MIME types for identity documents
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    // Max file size: 5MB
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public function __construct(
        private readonly string $idUploadsDirectory,
        private readonly string $passportUploadsDirectory,
        private readonly SluggerInterface $slugger,
    ) {}

    /**
     * Uploads a national ID document and returns the stored filename.
     */
    public function uploadIdDocument(UploadedFile $file): string
    {
        return $this->upload($file, $this->idUploadsDirectory);
    }

    /**
     * Uploads a passport document and returns the stored filename.
     */
    public function uploadPassport(UploadedFile $file): string
    {
        return $this->upload($file, $this->passportUploadsDirectory);
    }

    /**
     * Removes an existing file by its stored path (relative to public/).
     * Safe to call with null — does nothing.
     */
    public function remove(?string $relativePath, string $publicDir): void
    {
        if (!$relativePath) {
            return;
        }

        $absolutePath = rtrim($publicDir, '/') . '/' . ltrim($relativePath, '/');

        if (file_exists($absolutePath)) {
            unlink($absolutePath); //Deletes file
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function upload(UploadedFile $file, string $targetDirectory): string
    {
        $this->validate($file);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename)->lower();
        $newFilename      = $safeFilename . '_' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($targetDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException(
                sprintf('Could not save uploaded file: %s', $e->getMessage()),
                0,
                $e
            );
        }

        return $newFilename;
    }

    private function validate(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('File is too large. Maximum allowed size is %d MB.', self::MAX_FILE_SIZE / 1024 / 1024)
            );
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid file type "%s". Allowed types: %s.',
                    $file->getMimeType(),
                    implode(', ', self::ALLOWED_MIME_TYPES)
                )
            );
        }
    }
}