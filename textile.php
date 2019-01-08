<?php

namespace herbie\plugin\textile;

use Herbie\Config;
use Herbie\PluginInterface;
use Herbie\StringValue;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;

class TextilePlugin implements PluginInterface
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
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
     * @param EventInterface $event
     */
    public function onTwigInitialized(EventInterface $event)
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
     * @param EventInterface $event
     */
    public function onShortcodeInitialized(EventInterface $event)
    {
        /** @var herbie\plugin\shortcode\classes\Shortcode $shortcode */
        $shortcode = $event->getTarget();
        $shortcode->add('textile', [$this, 'textileShortcode']);
    }

    /**
     * @param EventInterface $event
     */
    public function onRenderContent(EventInterface $event)
    {
        #echo __METHOD__ . "<br>";
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
