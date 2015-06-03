<?php

namespace Ozh\PHPUnit\Listener;

/**
 * This PHPUnit extension reports PHPUnit tests which contain "too many" assertions
 *
 * Also, this code follows PSR style guide and I fucking loathe it.
 */
class OverAssertiveTestsListener implements \PHPUnit_Framework_TestListener
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
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * A failure occurred.
     *
     * @param \PHPUnit_Framework_Test                 $test
     * @param \PHPUnit_Framework_AssertionFailedError $e
     * @param float                                   $time
     */
    public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time)
    {
    }

    /**
     * Incomplete test.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * Risky test.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * Skipped test.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * A test started.
     *
     * @param \PHPUnit_Framework_Test $test
     */
    public function startTest(\PHPUnit_Framework_Test $test)
    {
    }

    /**
     * A test ended.
     *
     * @param \PHPUnit_Framework_Test $test
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        if (!$test instanceof \PHPUnit_Framework_TestCase) return;

        $threshold = $this->alertThreshold;
        $assertions = \PHPUnit_Framework_Assert::getCount();
        
        if ($assertions > $this->alertThreshold) {
            $this->addAssertiveTest($test, $assertions);
        }
        
    }

    /**
     * A test suite started.
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->suites++;
    }

    /**
     * A test suite ended.
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
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
     * @param \PHPUnit_Framework_TestCase $test
     * @param int                         $assertions Number of assertions
     */
    protected function addAssertiveTest(\PHPUnit_Framework_TestCase $test, $assertions)
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
     * @param \PHPUnit_Framework_TestCase $test
     * @return string
     */
    protected function makeLabel(\PHPUnit_Framework_TestCase $test)
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
