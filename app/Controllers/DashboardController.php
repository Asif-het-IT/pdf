<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Services\JobService;

final class DashboardController
{
    public function __construct(private readonly array $config, private readonly Auth $auth, private readonly JobService $jobs)
    {
    }

    public function index(): void
    {
        $user = $this->auth->user();
        $recentJobs = $this->jobs->recent(10);
        $view = dirname(__DIR__) . '/Views/dashboard/index.php';
        require $view;
    }
}
