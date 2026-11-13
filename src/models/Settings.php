<?php

namespace simplygoodwork\environmentbanner\models;

use craft\base\Model;

/**
 * Set these in config/environment-banner.php. There is no settings screen on purpose.
 */
class Settings extends Model
{
    /**
     * The current environment name. Leave null to use Craft's own (`CRAFT_ENVIRONMENT`).
     * Projects that name it differently can pass App::env('ENVIRONMENT') here.
     */
    public ?string $environment = null;

    /**
     * Environment name => ['label' => ..., 'color' => ..., 'symbol' => ...].
     * Environments that are not listed get no banner and no symbol.
     * Blue diamond vs red circle: different in both colour and shape, so they work for colour-blind users too.
     *
     * @var array<string, array{label: string, color: string, symbol: string}>
     */
    public array $environments = [
        'dev' => ['label' => 'Local development', 'color' => '#2563EB', 'symbol' => '🔷'],
        'development' => ['label' => 'Local development', 'color' => '#2563EB', 'symbol' => '🔷'],
        'staging' => ['label' => 'Staging', 'color' => '#D80621', 'symbol' => '🔴'],
    ];

    /** Show the banner across the top of the control panel. */
    public bool $showBanner = true;

    /** Prefix the symbol to every page <title>, in the control panel and on the front end. */
    public bool $prefixTitles = true;
}
