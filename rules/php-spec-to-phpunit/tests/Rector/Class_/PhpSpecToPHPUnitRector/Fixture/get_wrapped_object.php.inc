<?php

namespace spec\Rector\PhpSpecToPHPUnit\Tests\Rector\Class_\PhpSpecToPHPUnitRector\Fixture;

use PhpSpec\ObjectBehavior;

class BlablaSpec extends ObjectBehavior
{
    public function it_is_me(SomeType $someType)
    {
        $assignMe = $someType->getWrappedObject();
    }
}

?>
-----
<?php

namespace Tests\Rector\PhpSpecToPHPUnit\Tests\Rector\Class_\PhpSpecToPHPUnitRector\Fixture;

use PhpSpec\ObjectBehavior;

class BlablaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Rector\PhpSpecToPHPUnit\Tests\Rector\Class_\PhpSpecToPHPUnitRector\Fixture\Blabla
     */
    private $blabla;
    protected function setUp()
    {
        $this->blabla = new \Rector\PhpSpecToPHPUnit\Tests\Rector\Class_\PhpSpecToPHPUnitRector\Fixture\Blabla();
    }
    public function testMe()
    {
        /** @var SomeType|\PHPUnit\Framework\MockObject\MockObject $someType */
        $someType = $this->createMock(SomeType::class);
        $assignMe = $someType;
    }
}

?>
