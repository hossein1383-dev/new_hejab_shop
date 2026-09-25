<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

/**
 * ⚠️ فقط برای هاست‌هایی که SSH/ترمینال ندارند (مثل بعضی cPanel ها).
 * این کنترلر عملاً یک "درِ پشتی" اجرای دستور روی سرور شماست — پس با یک
 * رمز جدا (DEPLOY_SECRET در .env) محافظت شده، نه صرفاً باز و عمومی.
 * بعد از اتمام کارِ Deploy، پیشنهاد می‌شود این فایل و Route هایش کاملاً
 * حذف شوند (یا حداقل رمز را طولانی/پیچیده نگه دارید).
 */
class DeployController extends Controller
{
    private function checkSecret(Request $request): void
    {
        $expected = env('DEPLOY_SECRET');

        if (! $expected || $request->query('token') !== $expected) {
            abort(403, 'دسترسی مجاز نیست. توکن را در .env بگذارید و در آدرس هم وارد کنید.');
        }
    }

    /** php artisan migrate --force */
    public function migrate(Request $request)
    {
        $this->checkSecret($request);
        Artisan::call('migrate', ['--force' => true]);

        return '<pre style="direction:ltr; text-align:left;">' . e(Artisan::output()) . '</pre>';
    }

    /** php artisan migrate:rollback --force */
    public function rollback(Request $request)
    {
        $this->checkSecret($request);
        Artisan::call('migrate:rollback', ['--force' => true]);

        return '<pre style="direction:ltr; text-align:left;">' . e(Artisan::output()) . '</pre>';
    }
    
    /**
     * php artisan db:seed — بخش ۶۱. مقادیر اولیه سایت (نقش‌ها/دسترسی‌ها و
     * هرچه در DatabaseSeeder باشد) را بارگذاری می‌کند. بدون این، اصلاً
     * نمی‌توانید به‌عنوان سوپر ادمین وارد پنل شوید. با ?class=... می‌توانید
     * فقط یک Seeder خاص را هم اجرا کنید (مثلاً فقط RolePermissionSeeder).
     */
    public function seed(Request $request)
    {
        $this->checkSecret($request);

        $options = ['--force' => true];
        if ($request->query('class')) {
            $options['--class'] = $request->query('class');
        }

        Artisan::call('db:seed', $options);

        return '<pre style="direction:ltr; text-align:left;">' . e(Artisan::output()) . '</pre>';
    }

    /** php artisan config:clear + cache:clear + view:clear */
    public function clearCache(Request $request)
    {
        $this->checkSecret($request);
        $output = '';
        foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $command) {
            Artisan::call($command);
            $output .= "$ php artisan {$command}\n" . Artisan::output() . "\n";
        }

        return '<pre style="direction:ltr; text-align:left;">' . e($output) . '</pre>';
    }

    /**
     * npm run build — این یک دستور Node.js است، نه Artisan؛ فقط اگر
     * روی هاست شما Node.js/npm نصب باشد کار می‌کند (در بیشتر cPanel ها
     * باید اول از بخش "Setup Node.js App" فعالش کنید).
     */
    public function npmBuild(Request $request)
    {
        $this->checkSecret($request);

        return $this->runShellCommand('npm run build', $request->query('npm_path'));
    }

    /** composer install --no-dev --optimize-autoloader */
    public function composerInstall(Request $request)
    {
        $this->checkSecret($request);

        return $this->runShellCommand('composer install --no-dev --optimize-autoloader', $request->query('composer_path'));
    }

    private function runShellCommand(string $command, ?string $binaryPathPrefix = null): string
    {
        // بعضی هاست‌ها npm/composer را در PATH عادی ندارند؛ اگر لازم بود،
        // مسیر کامل باینری را با ?npm_path=/home/user/.nvm/.../npm بدهید.
        if ($binaryPathPrefix) {
            $command = escapeshellarg($binaryPathPrefix) . ' ' . substr($command, strpos($command, ' ') + 1);
        }

        $process = Process::fromShellCommandline($command, base_path());
        $process->setTimeout(600); // ۱۰ دقیقه — Build ها گاهی طول می‌کشند
        $process->run();

        $output = "$ {$command}\n\n" . $process->getOutput() . "\n" . $process->getErrorOutput();
        $status = $process->isSuccessful() ? '✅ موفق' : '❌ ناموفق (کد خروج: ' . $process->getExitCode() . ')';

        return '<pre style="direction:ltr; text-align:left; white-space:pre-wrap;">' . $status . "\n\n" . e($output) . '</pre>';
    }
    
     /**
     * php artisan storage:link — بخش ۶۳. بدون این، هیچ عکس آپلودی
     * (بنر، تصویر محصول و...) از storage/app/public قابل‌دسترسی نیست.
     */
    public function storageLink(Request $request)
    {
        $this->checkSecret($request);
        Artisan::call('storage:link');

        return '<pre style="direction:ltr; text-align:left;">' . e(Artisan::output()) . '</pre>';
    }

        public function syncHeropostCities(Request $request)
    {
        $this->checkSecret($request);
        Artisan::call('heropost:sync-cities');

        return '<pre style="direction:ltr; text-align:left;">' . e(Artisan::output()) . '</pre>';
    }
}
