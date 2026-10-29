<?php

// Standalone framework objects only: no application bootstrap or database.
declare(strict_types=1);

use App\Filament\AvatarProviders\LocalAvatarProvider;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\FilamentManager;
use Filament\Panel;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;

require dirname(__DIR__).'/vendor/autoload.php';

$checks = 0;
$check = static function (bool $value) use (&$checks): void {
    if (! $value) {
        throw new RuntimeException('Local avatar check failed');
    }
    $checks++;
};
$root = dirname(__DIR__);
$panelSource = file_get_contents($root.'/app/Providers/Filament/AdminPanelProvider.php');
$before = file_get_contents($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-same-origin-avatar-validation/panel-before.php');
$normalize = static fn (string $value): string => str_replace("\r\n", "\n", $value);
$reversed = str_replace(["use App\\Filament\\AvatarProviders\\LocalAvatarProvider;\n", "            ->defaultAvatarProvider(LocalAvatarProvider::class)\n"], '', $normalize($panelSource));
$check($reversed === $normalize($before));
$check(substr_count($panelSource, '->defaultAvatarProvider(LocalAvatarProvider::class)') === 1);
$check(str_contains($panelSource, 'use App\\Filament\\AvatarProviders\\LocalAvatarProvider;'));
$provider = new LocalAvatarProvider;
$check($provider instanceof AvatarProvider);
$method = new ReflectionMethod(LocalAvatarProvider::class, 'get');
$check((string) $method->getParameters()[0]->getType() === Model::class);
$check((string) $method->getReturnType() === 'string');

// Apply the exact configured avatar selection to the real installed Panel.
// Full application panel discovery/middleware boot is intentionally not run.
$panel = (new Panel)->defaultAvatarProvider(LocalAvatarProvider::class);
$check($panel->getDefaultAvatarProvider() === LocalAvatarProvider::class);
$check($panel->hasUserMenu());
$check($panel->hasTopbar());
$manager = new class($panel) extends FilamentManager
{
    public function __construct(private Panel $fixturePanel) {}

    public function getCurrentOrDefaultPanel(): ?Panel
    {
        return $this->fixturePanel;
    }
};
$record = new class extends Model
{
    public function getAttributeValue($key)
    {
        if ($key !== 'avatar_url') {
            throw new RuntimeException('Personal attribute read forbidden');
        }

        return null;
    }
};
$priorContainer = Container::getInstance();
$fixtureAvatarUrl = null;
try {
    foreach (['http://127.0.0.1:18150', 'https://synthetic.invalid', 'https://synthetic.invalid/osds'] as $base) {
        $container = new Container;
        Container::setInstance($container);
        $generator = new UrlGenerator(new RouteCollection, Request::create($base.'/admin'), 'https://external.invalid/assets');
        $generator->forceRootUrl($base);
        $container->instance('url', $generator);
        $container->instance(Illuminate\Contracts\Routing\UrlGenerator::class, $generator);
        $expected = $base.'/images/osds-avatar.svg';
        if ($fixtureAvatarUrl === null) {
            $fixtureAvatarUrl = $provider->get($record);
        }
        $check($provider->get($record) === $expected);
        $check($manager->getDefaultAvatarProvider() === LocalAvatarProvider::class);
        $check($manager->getUserAvatarUrl($record) === $expected);
        $check(parse_url($expected, PHP_URL_QUERY) === null);
        $check(! str_contains($expected, 'external.invalid'));
    }
} finally {
    Container::setInstance($priorContainer);
}
$source = file_get_contents($root.'/app/Filament/AvatarProviders/LocalAvatarProvider.php');
$check(! preg_match('/https?:|127\.0\.0\.1|\$record->|file_put_contents|Storage::|DB::/', $source));
$svg = file_get_contents($root.'/public/images/osds-avatar.svg');
$check(! preg_match('/<script|<foreignObject|(?:href|src)=|url\(|@import/i', $svg));
$check(str_contains($svg, 'viewBox="0 0 64 64"'));
$avatarView = file_get_contents($root.'/vendor/filament/filament/resources/views/components/avatar/user.blade.php');
$check(str_contains($avatarView, 'filament()->getUserAvatarUrl($user)'));
$check(str_contains($avatarView, ':alt="$alt"'));
$check(str_contains(file_get_contents($root.'/vendor/filament/filament/resources/views/components/user-menu.blade.php'), '<x-filament-panels::avatar.user'));
echo json_encode(['status' => 'PASS', 'checks' => $checks, 'avatarPath' => '/images/osds-avatar.svg', 'syntheticAvatarUrl' => $fixtureAvatarUrl, 'provider' => 'LOCAL_STATIC', 'scope' => 'installed Panel/FilamentManager/URL generator; no application bootstrap or database'], JSON_THROW_ON_ERROR).PHP_EOL;
