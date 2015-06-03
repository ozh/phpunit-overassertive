# phpunit-overassertive

Having several assertions in the same test is fine, but when an assertion fails,
the whole test aborts and other assertions in the same test are not tested.

Depending on what you test and how you coded it, you may want to split some tests
in several sub tests.

**OverAssertive** is a PHPUnit extension that reports right in the console
which tests have "too many" assertions, where "too many" is what you define, to
help you inspect and maybe refactor some tests.

![overassertive](https://cloud.githubusercontent.com/assets/223647/7969423/0d90aaee-0a37-11e5-9f40-a7d29c613017.png)

## Usage

Enable it with all defaults by adding the following to your test suite's `phpunit.xml` file:

```xml
<phpunit bootstrap="vendor/autoload.php">
...
    <listeners>
        <listener class="Ozh\PHPUnit\Listener\OverAssertiveTestsListener" />
    </listeners>
</phpunit>
```

If you're not using an autoloader you can also specify the library location:

```xml
<phpunit bootstrap="boostrap.php">
...
    <listeners>
        <listener class="Ozh\PHPUnit\Listener\OverAssertiveTestsListener" file="/path/to/OverAssertiveTestsListener.php" />
    </listeners>
</phpunit>
```

Now run your test suite as normal. OverAssertive will report over assertive tests in the console after the suite completes.

## Configuration

OverAssertive has two configurable parameters:

* **alertThreshold** - Number of assertions that will make a test over assertive (default: 10 assertions)
* **reportLength** - Number of over assertive tests included in the report (default: 10 tests)

These configuration parameters are set in `phpunit.xml` when adding the listener:

```xml
<phpunit ...>
    <!-- ... other suite configuration here ... -->

    <listeners>
        <listener class="Ozh\PHPUnit\Listener\OverAssertiveTestsListener">
            <arguments>
                <array>
                    <element key="alertThreshold">
                        <integer>10</integer>
                    </element>
                    <element key="reportLength">
                        <integer>10</integer>
                    </element>
                </array>
            </arguments>
        </listener>
    </listeners>
</phpunit>
```

## Inspiration

Much thanks to [phpunit-speedtrap](https://github.com/johnkary/phpunit-speedtrap)

## License

Do whatever the hell you want to.
