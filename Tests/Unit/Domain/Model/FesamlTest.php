<?php
namespace MiniOrange\MiniorangeSaml\Tests\Unit\Domain\Model;

/**
 * Test case.
 *
 * @author miniOrange <info@miniorange.com>
 */
class FesamlTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \MiniOrange\MiniorangeSaml\Domain\Model\Fesaml
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \MiniOrange\MiniorangeSaml\Domain\Model\Fesaml();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function dummyTestToNotLeaveThisFileEmpty()
    {
        self::markTestIncomplete();
    }
}
