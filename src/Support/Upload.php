<?php
declare(strict_types=1);

namespace KD\Support;

/**
 * Image uploads. A file is accepted only if it decodes as a real JPEG, PNG or
 * WebP image. It is then re-encoded with GD (which strips anything appended or
 * embedded), saved under a random name, and stored outside the web root.
 */
final class Upload
{
    public const FOLDERS = ['products', 'slides', 'customers', 'admins', 'categories'];
    private const TYPES  = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    private const MAX_DIMENSION = 4000;

    /** @var callable(string, string): bool */
    private $mover;
    /** @var callable(string): bool */
    private $isUploaded;

    public function __construct(
        private readonly string $baseDir,
        ?callable $mover = null,
        ?callable $isUploaded = null,
        private readonly int $maxBytes = 2 * 1024 * 1024,
    ) {
        $this->mover      = $mover ?? 'move_uploaded_file';
        $this->isUploaded = $isUploaded ?? 'is_uploaded_file';
    }

    public function storeOptionalImage(?array $file, string $folder): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $this->storeImage($file, $folder);
    }

    /** @return string the stored file name */
    public function storeImage(array $file, string $folder): string
    {
        if (!in_array($folder, self::FOLDERS, true)) {
            throw new \InvalidArgumentException('Unknown upload folder.');
        }
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is too large.',
                UPLOAD_ERR_NO_FILE => 'Please choose an image.',
                default => 'The upload failed. Please try again.',
            });
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !($this->isUploaded)($tmp)) {
            throw new UploadException('The upload failed. Please try again.');
        }
        $size = filesize($tmp);
        if ($size === false || $size > $this->maxBytes) {
            throw new UploadException('The image must be ' . intdiv($this->maxBytes, 1024 * 1024) . ' MB or smaller.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset(self::TYPES[$mime])) {
            throw new UploadException('Only JPEG, PNG or WebP images are allowed.');
        }
        $info = @getimagesize($tmp);
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw new UploadException('That file is not a valid image (max ' . self::MAX_DIMENSION . 'px per side).');
        }

        $image = @imagecreatefromstring((string) file_get_contents($tmp));
        if ($image === false) {
            throw new UploadException('That file is not a valid image.');
        }

        $ext  = self::TYPES[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir  = rtrim($this->baseDir, '/') . '/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('Upload folder is not writable.');
        }

        // Re-encode into a temp file, then move it into place.
        $clean = tempnam(sys_get_temp_dir(), 'kdimg');
        if ($ext === 'png') {
            imagesavealpha($image, true);
        }
        $ok = match ($ext) {
            'jpg'  => imagejpeg($image, $clean, 88),
            'png'  => imagepng($image, $clean, 6),
            'webp' => imagewebp($image, $clean, 85),
        };
        imagedestroy($image);
        if (!$ok) {
            @unlink($clean);
            throw new UploadException('The image could not be processed.');
        }

        if (!rename($clean, $dir . '/' . $name)) {
            @unlink($clean);
            throw new \RuntimeException('Could not save the image.');
        }
        // The original tmp file is discarded; only the re-encoded copy is kept.
        @unlink($tmp);
        chmod($dir . '/' . $name, 0640);
        return $name;
    }

    public function delete(string $folder, string $name): void
    {
        if (!in_array($folder, self::FOLDERS, true) || !self::isValidName($name)) {
            return;
        }
        $path = rtrim($this->baseDir, '/') . '/' . $folder . '/' . $name;
        if (is_file($path)) {
            unlink($path);
        }
    }

    public static function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name);
    }
}
