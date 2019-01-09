<?php

namespace herbie\plugin\textile;

use Herbie\Config;
use Herbie\Event;
use Herbie\EventManager;
use Herbie\PluginInterface;
use Herbie\StringValue;

class TextilePlugin implements PluginInterface
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param EventManager $events
     * @param int $priority
     */
    public function attach(EventManager $events, int $priority = 1): void
    {
        if ((bool)$this->config->get('plugins.config.textile.twig', false)) {
            $events->attach('onTwigInitialized', [$this, 'onTwigInitialized'], $priority);
        }
        if ((bool)$this->config->get('plugins.config.textile.shortcode', true)) {
            $events->attach('onShortcodeInitialized', [$this, 'onShortcodeInitialized'], $priority);
        }
        $events->attach('onRenderContent', [$this, 'onRenderContent'], $priority);
    }

    /**
     * @param Event $event
     */
    public function onTwigInitialized(Event $event)
    {
        /** @var Twig_Environment $twig */
        $twig = $event->getTarget();
        $options = ['is_safe' => ['html']];
        $twig->addFunction(
            new \Twig_SimpleFunction('textile', [$this, 'parseTextile'], $options)
        );
        $twig->addFilter(
            new \Twig_SimpleFilter('textile', [$this, 'parseTextile'], $options)
        );
    }

    /**
     * @param Event $event
     */
    public function onShortcodeInitialized(Event $event)
    {
        /** @var herbie\plugin\shortcode\classes\Shortcode $shortcode */
        $shortcode = $event->getTarget();
        $shortcode->add('textile', [$this, 'textileShortcode']);
    }

    /**
     * @param Event $event
     */
    public function onRenderContent(Event $event)
    {
        if (!in_array($event->getParam('format'), ['textile'])) {
            return;
        }
        /** @var StringValue $stringValue */
        $stringValue = $event->getTarget();
        $parsed = $this->parseTextile($stringValue->get());
        $stringValue->set($parsed);
    }

    /**
     * @param $value
     * @return string
     */
    public function parseTextile(string $value): string
    {
        $parser = new \Netcarver\Textile\Parser();
        return $parser->parse($value);
    }

    /**
     * @param mixed $options
     * @param string $content
     * @return string
     */
    public function textileShortcode($options, string $content): string
    {
        return $this->parseTextile($content);
    }
}
