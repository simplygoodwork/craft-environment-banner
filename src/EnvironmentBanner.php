<?php

namespace simplygoodwork\environmentbanner;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\web\View;
use simplygoodwork\environmentbanner\models\Settings;
use yii\base\Event;

/**
 * Marks non-production environments so it's obvious which one you're looking at:
 * a coloured banner across the top of the CP, and a matching coloured symbol at the
 * start of every page title (CP and front end), so browser tabs can be told apart.
 *
 * The banner is a `::before` on #page-container, which sits above #global-header.
 * If a future Craft release reshuffles the CP markup, that's the selector to revisit.
 *
 * @method Settings getSettings()
 */
class EnvironmentBanner extends Plugin
{
    public bool $hasCpSettings = false;

    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function() {
            $this->registerLabel();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    private function registerLabel(): void
    {
        $settings = $this->getSettings();
        $environment = $settings->environment ?? App::env('CRAFT_ENVIRONMENT') ?? Craft::$app->env;
        $config = $settings->environments[$environment] ?? null;

        if (!$config || !Craft::$app->getRequest()->getIsSiteRequest() && !Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        $label = (string)($config['label'] ?? $environment);
        $color = (string)($config['color'] ?? '#444444');
        $symbol = (string)($config['symbol'] ?? '');

        if ($settings->prefixTitles && $symbol !== '') {
            // Prefix the <title> of every full page render, after SEOmatic has added its own
            Event::on(View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE, function(TemplateEvent $event) use ($symbol) {
                $event->output = preg_replace('/<title(\s[^>]*)?>/i', '$0' . $symbol . ' ', $event->output, 1);
            });
        }

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function() use ($settings, $label, $color, $symbol) {
            if (!Craft::$app->getRequest()->getIsCpRequest() || !Craft::$app->getUser()->getIdentity()) {
                return;
            }

            $view = Craft::$app->getView();

            if ($settings->prefixTitles && $symbol !== '') {
                // CP JavaScript rebuilds the title from document.title: the element index splits it on " - "
                // and expects the source name first ("Webinars, 1–100 of 141 entries - Entries - Site"),
                // and the element editor resets it after a save. So scripts read the title without the
                // leading prefix from page load (our symbol plus anything SEOmatic adds like 🚧⚙️), and the
                // tab always shows it once at the front: the getter strips it, the setter and the observer
                // (for scripts that write <title> directly) add it back.
                $view->registerJs("
                    (function() {
                        const symbol = " . json_encode($symbol . ' ') . ";
                        const titleEl = document.querySelector('title');
                        const native = Object.getOwnPropertyDescriptor(Document.prototype, 'title');
                        if (!titleEl || !native) return;
                        let lead = native.get.call(document).match(/^[^\\p{L}\\p{N}]*/u)[0];
                        if (!lead.startsWith(symbol)) lead = symbol;
                        const strip = (t) => String(t).split(lead).join('');
                        const show = () => {
                            const current = native.get.call(document);
                            const fixed = lead + strip(current);
                            if (current !== fixed) native.set.call(document, fixed);
                        };
                        Object.defineProperty(document, 'title', {
                            configurable: true,
                            get: () => strip(native.get.call(document)),
                            set: (value) => native.set.call(document, lead + strip(value)),
                        });
                        show();
                        new MutationObserver(show).observe(titleEl, {childList: true, characterData: true, subtree: true});
                    })();
                ");
            }

            if ($settings->showBanner) {
                $view->registerCss("
                    #page-container::before {
                        content: " . json_encode(mb_strtoupper($label)) . ";
                        display: flex;
                        align-items: center;
                        min-height: var(--header-height);
                        padding: calc(var(--padding)/2) var(--padding);
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                        background-color: {$color};
                        color: #ffffff;
                        font-weight: 700;
                        z-index: 9;
                    }
                ");
            }
        });
    }
}
