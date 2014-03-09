<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Output\Node\EventListener\AST;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\Output\Node\Printer\ExamplePrinter;
use Behat\Behat\Output\Node\Printer\OutlinePrinter;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Symfony\Component\EventDispatcher\Event;

/**
 * Behat expanded outline listener.
 *
 * Listens to expanded outline events and calls appropriate printers.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutlineListener implements EventListener
{
    /**
     * @var OutlinePrinter
     */
    private $outlinePrinter;
    /**
     * @var ExamplePrinter
     */
    private $examplePrinter;
    /**
     * @var StepPrinter
     */
    private $stepPrinter;
    /**
     * @var ExampleNode
     */
    private $example;

    /**
     * Initializes listener.
     *
     * @param OutlinePrinter $outlinePrinter
     * @param ExamplePrinter $examplePrinter
     * @param StepPrinter    $stepPrinter
     */
    public function __construct(
        OutlinePrinter $outlinePrinter,
        ExamplePrinter $examplePrinter,
        StepPrinter $stepPrinter
    ) {
        $this->outlinePrinter = $outlinePrinter;
        $this->examplePrinter = $examplePrinter;
        $this->stepPrinter = $stepPrinter;
    }

    /**
     * {@inheritdoc}
     */
    public function listenEvent(Formatter $formatter, Event $event, $eventName)
    {
        $this->printAndCaptureOutlineHeaderOnBeforeEvent($formatter, $event);
        $this->printAndForgetOutlineFooterOnAfterEvent($formatter, $event);
        $this->printExampleHeaderOnBeforeExampleEvent($formatter, $event, $eventName);
        $this->printExampleFooterOnAfterExampleEvent($formatter, $event, $eventName);
        $this->printStepOnAfterStepEvent($formatter, $event);
    }

    /**
     * Prints outline header and captures outline into ivar on BEFORE event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     */
    private function printAndCaptureOutlineHeaderOnBeforeEvent(Formatter $formatter, Event $event)
    {
        if (!$event instanceof BeforeOutlineTested) {
            return;
        }

        $this->outlinePrinter->printHeader($formatter, $event->getFeature(), $event->getOutline());
    }

    /**
     * Prints outline footer and removes outline from ivar on AFTER event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     */
    private function printAndForgetOutlineFooterOnAfterEvent(Formatter $formatter, Event $event)
    {
        if (!$event instanceof AfterOutlineTested) {
            return;
        }

        $feature = $event->getFeature();
        $outline = $event->getOutline();
        $result = $event->getTestResult();

        $this->outlinePrinter->printFooter($formatter, $feature, $outline, $result);
    }

    /**
     * Prints example header on example BEFORE event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     * @param string    $eventName
     */
    private function printExampleHeaderOnBeforeExampleEvent(Formatter $formatter, Event $event, $eventName)
    {
        if (!$event instanceof ScenarioTested || ExampleTested::BEFORE !== $eventName) {
            return;
        }

        $this->example = $event->getScenario();
        $this->examplePrinter->printHeader($formatter, $event->getFeature(), $this->example);
    }

    /**
     * Prints example footer on example AFTER event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     * @param string    $eventName
     */
    private function printExampleFooterOnAfterExampleEvent(Formatter $formatter, Event $event, $eventName)
    {
        if (!$event instanceof AfterScenarioTested || ExampleTested::AFTER !== $eventName) {
            return;
        }

        $this->examplePrinter->printFooter($formatter, $event->getFeature(), $this->example, $event->getTestResult());
        $this->example = null;
    }

    /**
     * Prints example step on step AFTER event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     *
     * @internal param string $eventName
     */
    private function printStepOnAfterStepEvent(Formatter $formatter, Event $event)
    {
        if (!$event instanceof AfterStepTested) {
            return;
        }

        $this->stepPrinter->printStep($formatter, $this->example, $event->getStep(), $event->getTestResult());
    }
}
