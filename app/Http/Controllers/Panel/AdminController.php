<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * مدیریت مدیران — فقط مدیر اصلی به این بخش دسترسی دارد.
 */
class AdminController extends Controller
{
    public function index(): View
    {
        $admins = Admin::query()
            ->withCount('challenges')
            ->orderByDesc('is_super')
            ->orderBy('name')
            ->get();

        return view('panel.admins.index', [
            'admins' => $admins,
            'current' => $this->currentAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9._-]+$/', $this->usernameIsFree()],
            'password' => ['required', 'string', 'min:6', 'max:190', 'confirmed'],
            'is_super' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'نام مدیر را وارد کنید.',
            'username.required' => 'نام کاربری را وارد کنید.',
            'username.regex' => 'نام کاربری فقط با حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور یکسان نیست.',
        ], [
            'name' => 'نام مدیر',
            'username' => 'نام کاربری',
            'password' => 'رمز عبور',
        ]);

        $admin = Admin::create([
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'password' => trim($validated['password']),
            'is_super' => $request->boolean('is_super'),
            'is_active' => true,
        ]);

        return redirect()
            ->route('panel.admins.index')
            ->with('status', 'مدیر «'.$admin->name.'» ساخته شد. از این پس با نام کاربری '
                .$admin->username.' وارد می‌شود و چالش‌های خودش را می‌سازد.');
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->normalizeInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9._-]+$/', $this->usernameIsFree($admin->getKey())],
            'password' => ['nullable', 'string', 'min:6', 'max:190', 'confirmed'],
        ], [
            'name.required' => 'نام مدیر را وارد کنید.',
            'username.required' => 'نام کاربری را وارد کنید.',
            'username.regex' => 'نام کاربری فقط با حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور یکسان نیست.',
        ], [
            'name' => 'نام مدیر',
            'username' => 'نام کاربری',
            'password' => 'رمز عبور جدید',
        ]);

        $data = [
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
        ];

        if (filled($validated['password'] ?? null)) {
            $data['password'] = trim($validated['password']);
        }

        // مدیر اصلی نمی‌تواند نقش خودش را از «مدیر اصلی» به «مدیر چالش» تغییر دهد
        if (! $this->isSelf($admin)) {
            $data['is_super'] = $request->boolean('is_super');
        }

        $admin->update($data);

        return redirect()
            ->route('panel.admins.index')
            ->with('status', 'اطلاعات مدیر «'.$admin->name.'» ذخیره شد.');
    }

    /**
     * فاصله‌های اضافهٔ ابتدا و انتهای نام/نام کاربری/رمز را می‌گیرد؛ منبع
     * رایج «ساختم ولی نمی‌تواند وارد شود» همین فاصله‌های ناخواسته است.
     */
    private function normalizeInput(Request $request): void
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'username' => trim((string) $request->input('username')),
            'password' => trim((string) $request->input('password')),
            'password_confirmation' => trim((string) $request->input('password_confirmation')),
        ]);
    }

    /**
     * نام کاربری یکتا باشد، بدون حساسیت به بزرگی و کوچکی حروف
     * (SQLite در مقایسهٔ متن به بزرگی/کوچکی حساس است).
     */
    private function usernameIsFree(?int $ignoreId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreId): void {
            $taken = Admin::query()
                ->whereRaw('LOWER(username) = ?', [mb_strtolower((string) $value)])
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->exists();

            if ($taken) {
                $fail('این نام کاربری قبلاً ثبت شده است.');
            }
        };
    }

    public function toggle(Admin $admin): RedirectResponse
    {
        $current = $this->currentAdmin();

        if ($this->isSelf($admin)) {
            return back()->with('error', 'حساب خودت را نمی‌توانی غیرفعال کنی.');
        }

        if ($admin->isSuper() && $admin->isActive() && $this->superAdminsCount() <= 1) {
            return back()->with('error', 'باید همیشه حداقل یک مدیر اصلی فعال بماند.');
        }

        $admin->update(['is_active' => ! $admin->isActive()]);

        return back()->with('status', $admin->isActive()
            ? 'حساب «'.$admin->name.'» فعال شد.'
            : 'حساب «'.$admin->name.'» غیرفعال شد؛ دیگر نمی‌تواند وارد پنل شود.');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        if ($this->isSelf($admin)) {
            return back()->with('error', 'حساب خودت را نمی‌توانی حذف کنی.');
        }

        if ($admin->isSuper() && $this->superAdminsCount() <= 1) {
            return back()->with('error', 'باید همیشه حداقل یک مدیر اصلی بماند.');
        }

        $name = $admin->name;

        // چالش‌ها و شرکت‌کننده‌های او حذف نمی‌شوند؛ به مدیر اصلی جاری منتقل می‌شوند
        DB::transaction(function () use ($admin): void {
            $admin->challenges()->update(['admin_id' => $this->currentAdmin()->getKey()]);
            $admin->delete();
        });

        return redirect()
            ->route('panel.admins.index')
            ->with('status', 'مدیر «'.$name.'» حذف شد و چالش‌هایش به حساب شما منتقل شد.');
    }

    private function currentAdmin(): Admin
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return $admin;
    }

    private function isSelf(Admin $admin): bool
    {
        return (int) $admin->getKey() === (int) $this->currentAdmin()->getKey();
    }

    private function superAdminsCount(): int
    {
        return Admin::query()->super()->count();
    }
}
