<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\EventDispatcher\Tester;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\Tester\ScenarioTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface as Scenario;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Behat event-dispatching scenario tester.
 *
 * Scenario tester dispatching BEFORE/AFTER events during tests.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class EventDispatchingScenarioTester implements ScenarioTester
{
    /**
     * @var ScenarioTester
     */
    private $baseTester;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var string
     */
    private $beforeEventName;
    /**
     * @var string
     */
    private $afterEventName;

    /**
     * Initializes tester.
     *
     * @param ScenarioTester           $baseTester
     * @param EventDispatcherInterface $eventDispatcher
     * @param string                   $beforeEventName
     * @param string                   $afterEventName
     */
    public function __construct(
        ScenarioTester $baseTester,
        EventDispatcherInterface $eventDispatcher,
        $beforeEventName = ScenarioTested::BEFORE,
        $afterEventName = ScenarioTested::AFTER
    ) {
        $this->baseTester = $baseTester;
        $this->eventDispatcher = $eventDispatcher;
        $this->beforeEventName = $beforeEventName;
        $this->afterEventName = $afterEventName;
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(Environment $env, FeatureNode $feature, Scenario $scenario, $skip)
    {
        $setup = $this->baseTester->setUp($env, $feature, $scenario, $skip);

        $event = new BeforeScenarioTested($env, $feature, $scenario, $setup);
        $this->eventDispatcher->dispatch($this->beforeEventName, $event);

        return $setup;
    }

    /**
     * {@inheritdoc}
     */
    public function test(Environment $env, FeatureNode $feature, Scenario $scenario, $skip)
    {
        return $this->baseTester->test($env, $feature, $scenario, $skip);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(Environment $env, FeatureNode $feature, Scenario $scenario, $skip, TestResult $result)
    {
        $teardown = $this->baseTester->tearDown($env, $feature, $scenario, $skip, $result);

        $event = new AfterScenarioTested($env, $feature, $scenario, $result, $teardown);
        $this->eventDispatcher->dispatch($this->afterEventName, $event);

        return $teardown;
    }
}
