<?php

namespace herbie\plugin\textile;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;

class TextilePlugin extends \Herbie\Plugin
{
    /**
     * @return array
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $config = $this->herbie->getConfig();
        if ((bool)$config->get('plugins.config.textile.twig', false)) {
            $events->attach('twigInitialized', [$this, 'addTwigFunctionAndFilter'], $priority);
        }
        if ((bool)$config->get('plugins.config.textile.shortcode', true)) {
            $events->attach('shortcodeInitialized', [$this, 'onShortcodeInitialized'], $priority);
        }
        $events->attach('renderContent', [$this, 'onRenderContent'], $priority);
    }

    public function addTwigFunctionAndFilter(\Twig_Environment $twig)
    {
        $options = ['is_safe' => ['html']];
        $twig->addFunction(
            new \Twig_SimpleFunction('textile', [$this, 'parseTextile'], $options)
        );
        $twig->addFilter(
            new \Twig_SimpleFilter('textile', [$this, 'parseTextile'], $options)
        );
    }

    public function onShortcodeInitialized(EventInterface $event)
    {
        /** @var herbie\plugin\shortcode\classes\Shortcode $shortcode */
        $shortcode = $event->getTarget();
        $shortcode->add('textile', [$this, 'textileShortcode']);
    }

    public function onRenderContent(EventInterface $event)
    {
        if (!in_array($event->getParam('format'), ['textile'])) {
            return;
        }
        $content = $event->getTarget();
        $parsed = $this->parseTextile($content);
        $content->set($parsed);
    }

    public function parseTextile($value)
    {
        $parser = new \Netcarver\Textile\Parser();
        return $parser->parse($value);
    }

    public function textileShortcode($options, $content)
    {
        return $this->parseTextile($content);
    }
}
