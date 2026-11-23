<?php
declare(strict_types=1);

namespace KD\Controllers;

use KD\App;
use KD\Support\Upload;

/**
 * Serves uploaded images from storage/ (outside the web root), so an uploaded
 * file can never be run as code. Customer and admin photos are private.
 */
final class MediaController
{
    private const PUBLIC_FOLDERS = ['products', 'slides', 'categories'];
    private const TYPES = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function show(string $folder, string $file): void
    {
        if (!in_array($folder, Upload::FOLDERS, true) || !Upload::isValidName($file)) {
            $this->notFound();
            return;
        }
        if (!in_array($folder, self::PUBLIC_FOLDERS, true) && !$this->canSeePrivate($folder, $file)) {
            $this->notFound();
            return;
        }
        $path = APP_ROOT . '/storage/uploads/' . $folder . '/' . $file;
        if (!is_file($path)) {
            $this->notFound();
            return;
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . self::TYPES[$ext]);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline');
        header('Cache-Control: ' . (in_array($folder, self::PUBLIC_FOLDERS, true) ? 'public, max-age=86400' : 'private, no-store'));
        readfile($path);
    }

    private function canSeePrivate(string $folder, string $file): bool
    {
        if (App::adminAuth()->current() !== null) {
            return true;
        }
        if ($folder === 'customers') {
            $id = App::customerAuth()->id();
            return $id !== null && App::db()->value('SELECT 1 FROM customers WHERE id = ? AND image = ?', [$id, $file]) !== null;
        }
        return false;
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found';
    }
}
