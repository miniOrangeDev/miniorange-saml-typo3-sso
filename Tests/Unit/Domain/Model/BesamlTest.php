<?php
namespace Miniorange\MiniorangeSaml\Tests\Unit\Domain\Model;

/**
 * Test case.
 *
 * @author Miniorange <info@xecurify.com>
 */
class BesamlTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Miniorange\MiniorangeSaml\Domain\Model\Besaml
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \Miniorange\MiniorangeSaml\Domain\Model\Besaml();
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
