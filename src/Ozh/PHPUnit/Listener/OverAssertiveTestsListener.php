<?php

namespace Ozh\PHPUnit\Listener;

/**
 * MEH
 *
 * Also, this code follows PSR style guide and I fucking hate it.
 */
class OverAssertiveTestsListener implements \PHPUnit\Framework\TestListener
{
    /**
     * Internal tracking for test suites.
     *
     * Increments as more suites are run, then decremented as they finish. All
     * suites have been run when returns to 0.
     *
     * @var integer
     */
    protected $suites = 0;

    /**
     * Number of assertions for one test to be considered over assertive and be
     * reported by this listener.
     *
     * @var int
     */
    protected $alertThreshold;

    /**
     * Number of tests to report on for over assertiveness.
     *
     * @var int
     */
    protected $reportLength;

    /**
     * Collection of over assertive tests.
     *
     * @var array
     */
    protected $assertive = array();

    /**
     * Construct a new instance.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->loadOptions($options);
    }

    /**
     * An error occurred.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Throwable              $e
     * @param float                   $time
     */
    public function addError(\PHPUnit\Framework\Test $test, \Throwable $e, $time) :void
    {
    }

    /**
     * A failure occurred.
     *
     * @param \PHPUnit\Framework\Test                 $test
     * @param \PHPUnit_Framework_AssertionFailedError $e
     * @param float                                   $time
     */
    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, $time) :void
    {
    }

    /**
     * Incomplete test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Throwable              $e
     * @param float                   $time
     */
    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Throwable $e, $time) :void
    {
    }

    /**
     * Risky test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Throwable              $e
     * @param float                   $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Throwable $e, $time) :void
    {
    }

    /**
     * Skipped test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Throwable              $e
     * @param float                   $time
     */
    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Throwable $e, $time) :void
    {
    }

    /**
     * Warning.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Throwable              $e
     * @param float                   $time
     */
    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, $time) :void
    {
    }

    /**
     * A test started.
     *
     * @param \PHPUnit\Framework\Test $test
     */
    public function startTest(\PHPUnit\Framework\Test $test) :void
    {
    }

    /**
     * A test ended.
     *
     * @param \PHPUnit\Framework\Test $test
     */
    public function endTest(\PHPUnit\Framework\Test $test, $time) :void
    {
        if (!$test instanceof \PHPUnit\Framework\TestCase) return;

        $threshold = $this->alertThreshold;
        $assertions = \PHPUnit\Framework\Assert::getCount();

        if ($assertions > $this->alertThreshold) {
            $this->addAssertiveTest($test, $assertions);
        }

    }

    /**
     * A test suite started.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite) :void
    {
        $this->suites++;
    }

    /**
     * A test suite ended.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite) :void
    {
        $this->suites--;

        if (0 === $this->suites && $this->hasAssertiveTest()) {
            arsort($this->assertive); // Sort most assertive tests to the top

            $this->renderHeader();
            $this->renderBody();
            $this->renderFooter();
        }
    }

    /**
     * Stores a test as over assertive.
     *
     * @param \PHPUnit\Framework\TestCase $test
     * @param int                         $assertions Number of assertions
     */
    protected function addAssertiveTest(\PHPUnit\Framework\TestCase $test, $assertions) :void
    {
        $label = $this->makeLabel($test);
        $this->assertive[$label] = $assertions;
    }

    /**
     * Whether at least one test has been considered over assertive.
     *
     * @return bool
     */
    protected function hasAssertiveTest()
    {
        return !empty($this->assertive);
    }

    /**
     * Label for describing a test.
     *
     * @param \PHPUnit\Framework\TestCase $test
     * @return string
     */
    protected function makeLabel(\PHPUnit\Framework\TestCase $test)
    {
        return sprintf('%s:%s', get_class($test), $test->getName());
    }

    /**
     * Calculate number of over assertive tests to report about.
     *
     * @return int
     */
    protected function getReportLength()
    {
        return min(count($this->assertive), $this->reportLength);
    }

    /**
     * Find how many over assertive tests occurred that won't be shown due to list length.
     *
     * @return int Number of hidden over assertive tests
     */
    protected function getHiddenCount()
    {
        $total = count($this->assertive);
        $showing = $this->getReportLength($this->assertive);

        $hidden = 0;
        if ($total > $showing) {
            $hidden = $total - $showing;
        }

        return $hidden;
    }

    /**
     * Pluralize "assertion" if needed
     */
    protected function pluralizeAssertion($count)
    {
        return $count <= 1 ? 'assertion' : 'assertions';
    }


    /**
     * Renders test report header.
     */
    protected function renderHeader()
    {
        echo sprintf("\n\n%s more than %s %s:\n", count($this->assertive) == 1 ? 'This test has' : 'These tests have', $this->alertThreshold, $this->pluralizeAssertion($this->alertThreshold));
    }

    /**
     * Renders test report body.
     */
    protected function renderBody()
    {
        $assertive = $this->assertive;
        $max = strlen(max($assertive));

        $length = $this->getReportLength($assertive);
        for ($i = 1; $i <= $length; ++$i) {
            $label = key($assertive);
            $assertions = array_shift($assertive);
            $display = str_pad($assertions, $max, " ", STR_PAD_LEFT);
            $line = str_pad($i, strlen($length), " ", STR_PAD_LEFT);

            echo sprintf(" %s. %s %s in test %s\n", $line, $display, $this->pluralizeAssertion($assertions), $label);
        }
    }

    /**
     * Renders test report footer.
     */
    protected function renderFooter()
    {
        if ($hidden = $this->getHiddenCount($this->assertive)) {
            echo sprintf("...and there %s %s more above your threshold hidden from view", $hidden == 1 ? 'is' : 'are', $hidden);
        }
    }

    /**
     * Populate options into class internals.
     *
     * @param array $options
     */
    protected function loadOptions(array $options)
    {
        $this->alertThreshold = isset($options['alertThreshold']) ? $options['alertThreshold'] : 10;
        $this->reportLength = isset($options['reportLength']) ? $options['reportLength'] : 10;
    }

}
