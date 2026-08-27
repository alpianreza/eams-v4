<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-users');
    }

    private function roleCatalog(): array
    {
        $catalog = config('eams.roles', []);
        foreach (UserRole::orderBy('name')->pluck('name') as $name) {
            if (! isset($catalog[$name])) {
                $catalog[$name] = [
                    'label' => ucwords(str_replace(['_', '-'], ' ', $name)),
                    'description' => 'Role khusus dengan halaman yang dipilih manual.',
                ];
            }
        }
        return $catalog;
    }

    /** Katalog page key unik dari config/menu.php, kecuali grup Admin (digate admin, bukan page_access). */
    private function pageCatalog(): array
    {
        $groups = [];
        $seen = [];
        foreach (config('menu', []) as $group) {
            if (($group['group'] ?? '') === 'Admin') {
                continue;
            }
            $items = [];
            foreach ($group['items'] as $item) {
                $page = $item['page'] ?? null;
                if (! $page || isset($seen[$page])) {
                    continue;
                }
                $seen[$page] = true;
                $items[] = ['page' => $page, 'label' => $item['label']];
            }
            if ($items) {
                $groups[] = ['group' => $group['group'], 'items' => $items];
            }
        }
        return $groups;
    }

    private function allPageKeys(): array
    {
        $keys = [];
        foreach (config('menu', []) as $group) {
            foreach ($group['items'] as $item) {
                if (isset($item['page'])) {
                    $keys[] = $item['page'];
                }
            }
        }
        return array_values(array_unique($keys));
    }

    private function defaultPages(string $role): array
    {
        return config('eams.role_default_pages.'.$role, []);
    }

    private function roleDefaults(): array
    {
        $out = [];
        foreach (array_keys($this->roleCatalog()) as $role) {
            $out[$role] = $role === 'admin' ? $this->allPageKeys() : $this->defaultPages($role);
        }
        return $out;
    }

    private function normalizeRole($value): string
    {
        return Str::slug(strtolower(trim((string) $value)), '_');
    }

    private function normalizePhone($value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }
        return $digits;
    }

    private function selectedPages(Request $request, string $role): array
    {
        if ($role === 'admin') {
            return $this->allPageKeys();
        }
        $pages = $request->input('page_access', []);
        if (! is_array($pages)) {
            return $this->defaultPages($role);
        }
        return array_values(array_intersect($pages, $this->allPageKeys()));
    }

    private function validated(Request $request, ?User $user): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', 'string', 'max:255'],
            'permission' => ['required', Rule::in(['read', 'write'])],
            'wa_number' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => $user ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
        return $request->validate($rules);
    }

    private function storePhoto(Request $request, User $user, ?string $old): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        $dir = public_path('uploads/users');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = Str::random(20).'.'.$request->file('photo')->getClientOriginalExtension();
        $request->file('photo')->move($dir, $name);
        if ($old && is_file($dir.'/'.$old)) {
            @unlink($dir.'/'.$old);
        }
        $user->photo = $name;
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->roleCatalog(),
            'pageCatalog' => $this->pageCatalog(),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => $this->roleCatalog(),
            'pageCatalog' => $this->pageCatalog(),
            'roleDefaults' => $this->roleDefaults(),
            'selectedPages' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $role = $this->normalizeRole($data['role']);
        if (! isset($this->roleCatalog()[$role])) {
            return back()->withInput()->withErrors(['role' => 'Pilih role yang tersedia.']);
        }
        $pages = $this->selectedPages($request, $role);
        if ($pages === []) {
            return back()->withInput()->withErrors(['page_access' => 'Pilih minimal satu halaman.']);
        }

        $user = new User();
        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'role' => $role,
            'permission' => $role === 'admin' ? 'write' : $data['permission'],
            'wa_number' => $this->normalizePhone($data['wa_number'] ?? null),
            'page_access' => $pages,
            'status' => 'active',
        ]);
        $user->password = $data['password'];
        $this->storePhoto($request, $user, null);
        $user->save();

        return redirect()->route('users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function storeRole(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        $role = $this->normalizeRole($request->input('name'));
        if ($role === '') {
            return back()->withErrors(['name' => 'Nama role wajib diisi.']);
        }
        if (UserRole::where('name', $role)->exists()) {
            return back()->withErrors(['name' => 'Role sudah ada.']);
        }
        UserRole::create(['name' => $role]);

        return redirect()->route('users.index')->with('status', 'Role berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => $this->roleCatalog(),
            'pageCatalog' => $this->pageCatalog(),
            'roleDefaults' => $this->roleDefaults(),
            'selectedPages' => $user->page_access ?: $this->defaultPages($user->role),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $role = $this->normalizeRole($data['role']);
        if (! isset($this->roleCatalog()[$role])) {
            return back()->withInput()->withErrors(['role' => 'Pilih role yang tersedia.']);
        }
        $pages = $this->selectedPages($request, $role);
        if ($pages === []) {
            return back()->withInput()->withErrors(['page_access' => 'Pilih minimal satu halaman.']);
        }

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'role' => $role,
            'permission' => $role === 'admin' ? 'write' : $data['permission'],
            'wa_number' => $this->normalizePhone($data['wa_number'] ?? null),
            'page_access' => $pages,
            'status' => $data['status'] ?? $user->status,
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $this->storePhoto($request, $user, $user->photo);
        $user->save();

        return redirect()->route('users.index')->with('status', 'User berhasil diperbarui.');
    }

    public function activate(User $user)
    {
        $user->update(['status' => 'active']);

        return redirect()->route('users.index')->with('status', 'User diaktifkan.');
    }

    public function deactivate(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['status' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.']);
        }
        $user->update(['status' => 'inactive']);

        return redirect()->route('users.index')->with('status', 'User dinonaktifkan.');
    }
}
