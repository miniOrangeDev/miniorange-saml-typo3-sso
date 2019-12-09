<?php
namespace Miniorange\MiniorangeSaml\Tests\Unit\Controller;

/**
 * Test case.
 *
 * @author Miniorange <info@xecurify.com>
 */
class BesamlControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Miniorange\MiniorangeSaml\Controller\BesamlController
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\Miniorange\MiniorangeSaml\Controller\BesamlController::class)
            ->setMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function listActionFetchesAllBesamlsFromRepositoryAndAssignsThemToView()
    {

        $allBesamls = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $besamlRepository = $this->getMockBuilder(\Miniorange\MiniorangeSaml\Domain\Repository\BesamlRepository::class)
            ->setMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $besamlRepository->expects(self::once())->method('findAll')->will(self::returnValue($allBesamls));
        $this->inject($this->subject, 'besamlRepository', $besamlRepository);

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('besamls', $allBesamls);
        $this->inject($this->subject, 'view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenBesamlToView()
    {
        $besaml = new \Miniorange\MiniorangeSaml\Domain\Model\Besaml();

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $this->inject($this->subject, 'view', $view);
        $view->expects(self::once())->method('assign')->with('besaml', $besaml);

        $this->subject->showAction($besaml);
    }
}
