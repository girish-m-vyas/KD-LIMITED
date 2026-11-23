<?php
declare(strict_types=1);

namespace KD\Controllers;

use KD\App;
use KD\Database;
use KD\Http\HttpException;
use KD\Http\Request;
use KD\Http\Response;
use KD\Support\UploadException;
use KD\Support\View;

abstract class Controller
{
    protected function db(): Database
    {
        return App::db();
    }

    protected function view(string $template, array $data = [], ?string $layout = 'store'): string
    {
        return View::render($template, $data, $layout);
    }

    /** Fetch a row by id or stop with a 404. */
    protected function findOr404(string $table, int $id): array
    {
        // $table is always a literal from our own code, never user input.
        if (!preg_match('/^[a-z_]+$/', $table)) {
            throw new \LogicException('Bad table name.');
        }
        $row = $this->db()->fetch("SELECT * FROM {$table} WHERE id = ?", [$id]);
        if ($row === null) {
            throw HttpException::notFound();
        }
        return $row;
    }

    /** Store an optional upload; on a bad file, send the user back with the error. */
    protected function optionalImage(string $field, string $folder, string $backTo, array $old): ?string
    {
        try {
            return App::uploads()->storeOptionalImage(Request::file($field), $folder);
        } catch (UploadException $e) {
            Response::back($backTo, [$field => $e->getMessage()], $old);
        }
    }

    protected function adminId(): int
    {
        return (int) App::adminAuth()->id();
    }

    protected function audit(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        App::audit()->record($this->adminId(), $action, $entity, $entityId, $details, Request::ip());
    }
}
