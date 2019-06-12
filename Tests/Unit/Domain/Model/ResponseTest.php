<?php
namespace MiniOrange\MiniorangeSaml\Tests\Unit\Domain\Model;

/**
 * Test case.
 *
 * @author miniOrange <info@miniorange.com>
 */
class ResponseTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \MiniOrange\MiniorangeSaml\Domain\Model\Response
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \MiniOrange\MiniorangeSaml\Domain\Model\Response();
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
