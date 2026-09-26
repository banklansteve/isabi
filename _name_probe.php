<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\SupportTicket::with('user')->latest('id')->limit(5)->get() as $t) {
    $u = $t->user;
    $presenter = app(App\Support\SupportChat\SupportPresenter::class);
    $item = $presenter->inboxItem($t);
    echo '#'.$t->id
        .' first='.($u->first_name ?? '-')
        .' name='.($u->name ?? '-')
        .' biz='.($u->business_name ?? '-')
        .' presented='.$item['user']['name']
        .PHP_EOL;
}
