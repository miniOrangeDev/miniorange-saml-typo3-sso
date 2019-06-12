<?php
namespace MiniOrange\MiniorangeSaml\Tests\Unit\Controller;

/**
 * Test case.
 *
 * @author miniOrange <info@miniorange.com>
 */
class FesamlControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \MiniOrange\MiniorangeSaml\Controller\FesamlController
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\MiniOrange\MiniorangeSaml\Controller\FesamlController::class)
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
    public function listActionFetchesAllFesamlsFromRepositoryAndAssignsThemToView()
    {

        $allFesamls = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fesamlRepository = $this->getMockBuilder(\MiniOrange\MiniorangeSaml\Domain\Repository\FesamlRepository::class)
            ->setMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $fesamlRepository->expects(self::once())->method('findAll')->will(self::returnValue($allFesamls));
        $this->inject($this->subject, 'fesamlRepository', $fesamlRepository);

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('fesamls', $allFesamls);
        $this->inject($this->subject, 'view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenFesamlToView()
    {
        $fesaml = new \MiniOrange\MiniorangeSaml\Domain\Model\Fesaml();

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $this->inject($this->subject, 'view', $view);
        $view->expects(self::once())->method('assign')->with('fesaml', $fesaml);

        $this->subject->showAction($fesaml);
    }
}
