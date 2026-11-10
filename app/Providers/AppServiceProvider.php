<?php

namespace App\Providers;

use App\Integracoes\Contracts\AlertaFiscalizacao;
use App\Integracoes\Contracts\AssinaturaDigital;
use App\Integracoes\Contracts\CadastroUnico;
use App\Integracoes\Contracts\CalendarioOficial;
use App\Integracoes\Drivers;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Seleção dos drivers de integração conforme config/sdgt.php (.env). */
    public function register(): void
    {
        $this->app->bind(CalendarioOficial::class, fn ($app) => config('sdgt.integracoes.calendario.driver') === 'api'
            ? $app->make(Drivers\CalendarioApi::class) : $app->make(Drivers\CalendarioLocal::class));

        $this->app->bind(CadastroUnico::class, fn ($app) => config('sdgt.integracoes.cadun.driver') === 'api'
            ? $app->make(Drivers\CadunApi::class) : $app->make(Drivers\CadunLocal::class));

        $this->app->bind(AssinaturaDigital::class, fn ($app) => config('sdgt.integracoes.assinatura.driver') === 'icp_tce'
            ? $app->make(Drivers\AssinaturaIcpTce::class) : $app->make(Drivers\AssinaturaSimples::class));

        $this->app->bind(AlertaFiscalizacao::class, fn ($app) => config('sdgt.integracoes.sfg.driver') === 'api'
            ? $app->make(Drivers\SfgApi::class) : $app->make(Drivers\SfgLog::class));
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.paginacao');

        // @icon('nome') — ícones SVG do sistema (resources/views/partials/icon.blade.php)
        Blade::directive('icon', fn ($e) => "<?php echo \$__env->make('partials.icon', ['n' => {$e}])->render(); ?>");

        // Helpers de apresentação usados nas views
        Blade::directive('indice', fn ($e) => "<?php echo {$e} === null ? '—' : number_format({$e}, 1, ',', '.').'%'; ?>");

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('notificacoesNaoLidas', $user ? $user->naoLidas() : 0);
        });
    }
}
