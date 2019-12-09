<?php
namespace Miniorange\MiniorangeSaml\Tests\Unit\Controller;

/**
 * Test case.
 *
 * @author Miniorange <info@xecurify.com>
 */
class FesamlControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Miniorange\MiniorangeSaml\Controller\FesamlController
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\Miniorange\MiniorangeSaml\Controller\FesamlController::class)
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

        $fesamlRepository = $this->getMockBuilder(\Miniorange\MiniorangeSaml\Domain\Repository\FesamlRepository::class)
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
        $fesaml = new \Miniorange\MiniorangeSaml\Domain\Model\Fesaml();

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $this->inject($this->subject, 'view', $view);
        $view->expects(self::once())->method('assign')->with('fesaml', $fesaml);

        $this->subject->showAction($fesaml);
    }
}
