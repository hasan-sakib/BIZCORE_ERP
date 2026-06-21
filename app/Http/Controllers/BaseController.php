<?php

declare(strict_types=1);

namespace App\Http\Controllers;

abstract class BaseController extends Controller
{
    protected function success(string $message): void
    {
        session()->flash('success', $message);
    }

    protected function error(string $message): void
    {
        session()->flash('error', $message);
    }

    protected function info(string $message): void
    {
        session()->flash('info', $message);
    }
}
