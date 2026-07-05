<?php

declare(strict_types=1);

namespace App\Product;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Stores an uploaded product image under the public images directory and
 * returns the stored filename.
 */
class ImageUploader
{
    public function __construct(
        private readonly string $targetDirectory,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($original)->lower();
        $filename = sprintf('%s-%s.%s', $safeName, bin2hex(random_bytes(4)), $file->guessExtension());

        $file->move($this->targetDirectory, $filename);

        return $filename;
    }
}
