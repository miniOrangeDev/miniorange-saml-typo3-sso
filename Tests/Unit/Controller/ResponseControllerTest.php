<?php
namespace Miniorange\MiniorangeSaml\Tests\Unit\Controller;

/**
 * Test case.
 *
 * @author Miniorange <info@xecurify.com>
 */
class ResponseControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Miniorange\MiniorangeSaml\Controller\ResponseController
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\Miniorange\MiniorangeSaml\Controller\ResponseController::class)
            ->setMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

}
