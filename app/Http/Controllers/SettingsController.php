<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Settings module (Settings): personal notification contact (all users) plus
 * company / email / WhatsApp system settings (admin or compliance).
 */
class SettingsController extends Controller
{
    public const DEFAULT_EMAIL_SUBJECT = '{{company}} — {{title}}';

    public const DEFAULT_EMAIL_BODY = "Halo {{name}},\n\n{{message}}\n\n— {{company}}";

    public const DEFAULT_WHATSAPP_BODY = "*{{company}}*\n\n{{message}}\n\n_{{date}}_";

    public function index(Request $request): View
    {
        $section = $request->query('section', 'user');
        $section = in_array($section, ['user', 'company', 'email', 'whatsapp'], true) ? $section : 'user';
        if ($section !== 'user' && ! Gate::allows('manage-settings')) {
            $section = 'user';
        }

        $settings = $this->settingsMap();

        $emailConfig = [
            'host' => $settings['email_smtp_host'] ?? 'smtp.gmail.com',
            'user' => $settings['email_smtp_user'] ?? '',
            'port' => $settings['email_smtp_port'] ?? 587,
            'crypto' => $settings['email_smtp_crypto'] ?? 'tls',
            'from_address' => $settings['email_from_address'] ?? '',
            'from_name' => $settings['email_from_name'] ?? ($settings['company_name'] ?? config('eams.company_name', 'EAMS')),
            'password_saved' => trim((string) ($settings['email_smtp_password'] ?? '')) !== '',
            'subject_template' => $settings['email_subject_template'] ?? self::DEFAULT_EMAIL_SUBJECT,
            'body_template' => $settings['email_body_template'] ?? self::DEFAULT_EMAIL_BODY,
        ];
        $emailConfig['ready'] = $emailConfig['host'] !== ''
            && filter_var((string) $emailConfig['user'], FILTER_VALIDATE_EMAIL) !== false
            && filter_var((string) $emailConfig['from_address'], FILTER_VALIDATE_EMAIL) !== false
            && $emailConfig['password_saved'];

        $whatsappConfig = [
            'enabled' => ($settings['notification_whatsapp_enabled'] ?? '0') === '1',
            'webhook' => trim((string) ($settings['notification_whatsapp_webhook'] ?? '')),
            'token_saved' => trim((string) ($settings['notification_whatsapp_token'] ?? '')) !== '',
            'message_template' => $settings['whatsapp_message_template'] ?? self::DEFAULT_WHATSAPP_BODY,
        ];
        $whatsappConfig['ready'] = filter_var($whatsappConfig['webhook'], FILTER_VALIDATE_URL) !== false;

        return view('settings.index', [
            'section' => $section,
            'settings' => $settings,
            'emailConfig' => $emailConfig,
            'whatsappConfig' => $whatsappConfig,
            'currentUser' => auth()->user(),
        ]);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        abort_unless(Gate::allows('manage-settings'), 403);

        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:2000'],
            'document_footer' => ['nullable', 'string', 'max:255'],
            'document_signatory_name' => ['nullable', 'string', 'max:255'],
            'document_signatory_title' => ['nullable', 'string', 'max:255'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'company_logo.mimes' => 'Logo harus JPG, PNG, atau WEBP.',
            'company_logo.max' => 'Logo maksimal 2 MB.',
        ]);

        $uid = auth()->id();
        foreach (['company_name', 'company_address', 'document_footer', 'document_signatory_name', 'document_signatory_title'] as $key) {
            Setting::put($key, trim((string) $request->input($key)), false, $uid);
        }

        if ($request->hasFile('company_logo')) {
            $dir = public_path('uploads/company');
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $name = Str::random(20).'.'.$request->file('company_logo')->getClientOriginalExtension();
            $request->file('company_logo')->move($dir, $name);
            Setting::put('company_logo', 'uploads/company/'.$name, false, $uid);
        }

        return redirect()->route('settings.index', ['section' => 'company'])->with('status', 'Identitas perusahaan berhasil disimpan.');
    }

    public function storeEmail(Request $request): RedirectResponse
    {
        abort_unless(Gate::allows('manage-settings'), 403);

        $request->validate([
            'email_smtp_host' => ['required', 'string', 'max:255'],
            'email_smtp_user' => ['nullable', 'email', 'max:255'],
            'email_smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'email_smtp_crypto' => ['nullable', Rule::in(['tls', 'ssl', ''])],
            'email_from_address' => ['nullable', 'email', 'max:255'],
            'email_from_name' => ['required', 'string', 'max:255'],
            'email_subject_template' => ['required', 'string', 'max:255'],
            'email_body_template' => ['required', 'string', 'max:10000'],
            'notification_email_enabled' => ['boolean'],
        ]);

        $enabled = $request->boolean('notification_email_enabled');
        $host = trim((string) $request->input('email_smtp_host'));
        $user = strtolower(trim((string) $request->input('email_smtp_user')));
        $from = strtolower(trim((string) $request->input('email_from_address')));
        $fromName = trim((string) $request->input('email_from_name'));
        $password = trim((string) $request->input('email_smtp_password'));
        $port = (int) $request->input('email_smtp_port');
        $crypto = (string) $request->input('email_smtp_crypto', 'tls');
        $subjectTemplate = trim((string) $request->input('email_subject_template'));
        $bodyTemplate = trim((string) $request->input('email_body_template'));

        $hasPassword = $password !== '' || $this->savedSecret('email_smtp_password') !== '';
        if ($enabled && ($host === '' || $user === '' || $from === '' || ! $hasPassword)) {
            return redirect()->route('settings.index', ['section' => 'email'])->with('error', 'Lengkapi akun, App Password, dan alamat pengirim sebelum mengaktifkan email.');
        }

        $uid = auth()->id();
        foreach ([
            'notification_email_enabled' => $enabled ? '1' : '0',
            'email_smtp_provider' => 'google_workspace',
            'email_smtp_host' => $host,
            'email_smtp_user' => $user,
            'email_smtp_port' => (string) $port,
            'email_smtp_crypto' => $crypto,
            'email_from_address' => $from,
            'email_from_name' => $fromName,
            'email_subject_template' => $subjectTemplate,
            'email_body_template' => $bodyTemplate,
        ] as $key => $value) {
            Setting::put($key, $value, false, $uid);
        }
        if ($password !== '') {
            Setting::put('email_smtp_password', $password, true, $uid);
        }

        return redirect()->route('settings.index', ['section' => 'email'])->with('status', 'Konfigurasi dan template email berhasil disimpan.');
    }

    public function storeWhatsApp(Request $request): RedirectResponse
    {
        abort_unless(Gate::allows('manage-settings'), 403);

        $request->validate([
            'notification_whatsapp_webhook' => ['nullable', 'url', 'max:255'],
            'notification_whatsapp_token' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_template' => ['required', 'string', 'max:10000'],
            'notification_whatsapp_enabled' => ['boolean'],
        ]);

        $enabled = $request->boolean('notification_whatsapp_enabled');
        $webhook = trim((string) $request->input('notification_whatsapp_webhook'));
        $token = trim((string) $request->input('notification_whatsapp_token'));
        $template = trim((string) $request->input('whatsapp_message_template'));

        if ($enabled && $webhook === '') {
            return redirect()->route('settings.index', ['section' => 'whatsapp'])->with('error', 'Isi webhook sebelum mengaktifkan WhatsApp.');
        }

        $uid = auth()->id();
        Setting::put('notification_whatsapp_enabled', $enabled ? '1' : '0', false, $uid);
        Setting::put('notification_whatsapp_webhook', $webhook, false, $uid);
        Setting::put('whatsapp_message_template', $template, false, $uid);
        if ($token !== '') {
            Setting::put('notification_whatsapp_token', $token, true, $uid);
        }

        return redirect()->route('settings.index', ['section' => 'whatsapp'])->with('status', 'Konfigurasi dan template WhatsApp berhasil disimpan.');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'wa_number' => ['nullable', 'string', 'max:20'],
        ]);

        $digits = preg_replace('/\D+/', '', (string) $request->input('wa_number'));
        if ($digits !== '' && str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif ($digits !== '' && str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        $request->user()->update([
            'email' => $request->input('email') ?: null,
            'wa_number' => $digits ?: null,
        ]);

        return redirect()->route('settings.index', ['section' => 'user'])->with('status', 'Kontak notifikasi berhasil disimpan.');
    }

    /** All settings as a flat map; corrupted encrypted values must not break the page. */
    private function settingsMap(): array
    {
        try {
            return Setting::allAsMap();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Decrypted value of a secret setting, or '' if missing/corrupted. */
    private function savedSecret(string $key): string
    {
        try {
            return (string) Setting::value($key, '');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
