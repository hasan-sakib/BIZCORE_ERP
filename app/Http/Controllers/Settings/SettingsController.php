<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseController;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends BaseController
{
    public function index(): View
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('settings.index', compact('settings'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'     => ['required', 'string', 'max:150'],
            'company_email'    => ['required', 'email'],
            'company_phone'    => ['nullable', 'string', 'max:30'],
            'company_address'  => ['nullable', 'string', 'max:500'],
            'default_currency' => ['required', 'string', 'size:3'],
            'date_format'      => ['required', 'string'],
            'timezone'         => ['required', 'string'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->success('General settings saved.');
        return back();
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vat_number'  => ['nullable', 'string', 'max:50'],
            'trade_no'    => ['nullable', 'string', 'max:50'],
            'fiscal_year' => ['required', 'string'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->success('Company settings saved.');
        return back();
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_from_address' => ['required', 'email'],
            'mail_from_name'    => ['required', 'string'],
            'mail_host'         => ['required', 'string'],
            'mail_port'         => ['required', 'integer'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->success('Email settings saved.');
        return back();
    }

    public function updateNumbering(Request $request): RedirectResponse
    {
        $keys = ['invoice_prefix', 'po_prefix', 'so_prefix', 'payment_prefix'];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }
        $this->success('Numbering settings saved.');
        return back();
    }

    public function updateTax(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vat_enabled'      => ['boolean'],
            'default_vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->success('Tax settings saved.');
        return back();
    }

    public function updateModules(Request $request): RedirectResponse
    {
        $modules = $request->validate(['modules' => ['array'], 'modules.*' => ['string']]);
        Setting::updateOrCreate(['key' => 'enabled_modules'], ['value' => json_encode($modules['modules'] ?? [])]);
        $this->success('Module settings saved.');
        return back();
    }

    public function auditLog(Request $request): View
    {
        $logs = AuditLog::with('user')->latest()->paginate(50);
        return view('settings.audit-log', compact('logs'));
    }
}
