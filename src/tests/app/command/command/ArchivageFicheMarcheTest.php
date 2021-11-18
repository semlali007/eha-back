<?php
namespace Tests\App\Command\Command; 

use App\Command\Command\ArchivageFicheMarche;
use Core\Util\Date;
use App\Models\Sheet;
use App\Repository\SheetRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author HGDDEDBN
 */
class ArchivageFicheMarcheTest extends TestCase {

    /** @var ArchivageFicheMarche|PHPUnit_Framework_MockObject */
    private $archivageFicheMarcheMock;

    /** @var CommandTester */
    private $commandTester;

    public function setUp(): void {
        $this->archivageFicheMarcheMock = $this->getMockBuilder(SheetRepository::class)
                ->setMethods(['archiverByEndDatePlus'])
                ->disableOriginalConstructor()
                ->getMock();

        $application = new Application();
        $application->add(new ArchivageFicheMarche());
        $command = $application->find('app:archivage:start');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void {
        $this->archivageFicheMarcheMock = null;
        $this->commandTester = null;
    }

    /**
     * @covers
     * @return void
     */
    public function testExecute():void {
        $date = Date::generateDatePlus();
        $sheetCount = (new Sheet())->desarchiverFichesByDate($date);

        $this->archivageFicheMarcheMock
                ->expects($this->once())
                ->method('archiverByEndDatePlus')
                ->with(30)
                ->willReturn($sheetCount);

        $this->commandTester->execute([1]);
        $output = explode(" ", $this->commandTester->getDisplay());
        $count = array_key_exists(103, $output) ? trim($output[103]): $sheetCount ;
        $this->assertEquals($count, $this->archivageFicheMarcheMock->archiverByEndDatePlus(30));
    }

   /**
    * @covers
    * @return void
    */
    public function testExecuteShouldThrowExceptionForInvalidFicheMarche():void {  
        $this->archivageFicheMarcheMock
                ->expects($this->once())
                ->method('archiverByEndDatePlus')
                ->with(30)
                ->willReturn(0);


        $this->commandTester->execute([1]);
        $this->assertEquals(0, $this->archivageFicheMarcheMock->archiverByEndDatePlus(30));
    }

}
