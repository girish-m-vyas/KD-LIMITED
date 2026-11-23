<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Http\Request;

final class AuditController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): string
    {
        $actions = App::audit()->actions();
        $action  = Request::query('action');
        if (!in_array($action, $actions, true)) {
            $action = '';
        }
        $total = App::audit()->count($action);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(Request::queryInt('page') ?? 1, $pages);

        return $this->view('admin/audit', [
            'pageTitle' => 'Audit log',
            'entries'   => App::audit()->recent(self::PER_PAGE, ($page - 1) * self::PER_PAGE, $action),
            'actions'   => $actions,
            'action'    => $action,
            'page'      => $page,
            'pages'     => $pages,
            'total'     => $total,
        ], 'admin');
    }
}
