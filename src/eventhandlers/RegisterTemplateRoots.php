<?php

namespace craftunit\craftstripeexpresscheckout\eventhandlers;

use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use craftunit\craftstripeexpresscheckout\interfaces\EventHandlerInterface;

class RegisterTemplateRoots implements EventHandlerInterface
{
    protected array $templateRoots = [
        'stripe-express-checkout' => __DIR__ . '/../templates',
    ];

    public function triggers(): array
    {
        return [
            [View::class => View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS],
        ];
    }

    /**
     * @param RegisterTemplateRootsEvent $event
     * @return void
     */
    public function handle($event): void
    {
        $this->registerTemplateRoots($event);
    }

    private function registerTemplateRoots($e): void
    {
        foreach ($this->templateRoots as $handle => $dir) {
            if (is_string($dir)) {
                $dir = [$dir];
            }

            foreach ($dir as $d) {
                if (is_dir($d .= DIRECTORY_SEPARATOR . 'cp')) {
                    $e->roots[$handle] = $d;
                }
            }
        }
    }
}
