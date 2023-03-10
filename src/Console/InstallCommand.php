<?php

namespace Dywily\Gaw\Console;


class InstallCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dywily:gaw-install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function handle()
    {
        copy(__DIR__ . '/../config/gaw.php', config_path('gaw.php'));
        copy(__DIR__ . '/stubs/gmail.service.provider.stub', app_path('Providers/GmailServiceProvider.php'));
        file_exists(app_path('Facades')) or mkdir(app_path('Facades'), 0777, true);
        copy(__DIR__ . '/stubs/gmail.facade.stub', app_path('Facades/Gmail.php'));
        $handle = fopen(base_path('routes/web.php'), "a+");
        $route = <<<'PHP'

Route::get('/getLabels', function () {
//    /** @var \Dywily\Gaw\Services\GmailService $service */
//    $service = app()->make('gmail');
//    $labels=$service->getLabels();
    $labels=\App\Facades\Gmail::getLabels();
    foreach ($labels as $label) {
        dump($label->getName());
    }
    return 1;
});

PHP;

        $str = fwrite($handle, "$route");
        fclose($handle);
    }
}
