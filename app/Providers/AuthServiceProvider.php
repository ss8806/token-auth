<?php

declare(strict_types=1);

namespace App\Providers;

use App\DataProvider\UserToken;
use App\Foundation\Auth\CacheUserProvider;
use App\Foundation\Auth\UserTokenProvider;
use App\Gate\UserAccess;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Psr\Log\LoggerInterface;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(
        GateContract $gate,
        LoggerInterface $logger
    ): void {
        $this->registerPolicies();
        // $this->app->make('auth')->provider(
        //     'cache_eloquent',
        //     function (Application $app, array $config) {
        //         return new CacheUserProvider(
        //             $app->make('hash'),
        //             $config['model'],
        //             $app->make('cache')->driver()
        //         );
        //     }
        // );
        // ①
        $this->app->make('auth')->provider(
            'user_token',
            function (Application $app, array $config) {
                // ②
                return new UserTokenProvider(new UserToken($app->make('db')));
            }
        );

        $gate->define(
            'user-access',
            function (User $user, $id) {
                return intval($user->getAuthIdentifier()) === intval($id);
            }
        );

        // リスト6.5.2.4：_ _invokeを実装したメソッドを認可処理で利用する例
        // $gate->define('user-access', new UserAccess());

        // リスト6.5.2.5：beforeメソッドを利用した認可処理ロギング
        $gate->before(
            function ($user, $ability) use ($logger) {
                $logger->info(
                    $ability,
                    [
                        'user_id' => $user->getAuthIdentifier()
                    ]
                );
            }
        );
    }
}
