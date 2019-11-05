<?php declare(strict_types=1);

namespace Tests\Surda\Doctrine\Queries;

use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class FakeTest extends TestCase
{
    public function testFoo()
    {
        Assert::true(TRUE);
    }
}

(new FakeTest())->run();