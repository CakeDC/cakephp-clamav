<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Clamd\Test\Validation;

use CakeDC\Clamav\Network\Socket;
use CakeDC\Clamav\Validation\ClamdValidation;
use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use Cake\Core\Configure;
use Cake\Network\Exception\SocketException;
use Cake\TestSuite\TestCase;

/**
 * @property ClamdValidation ClamdValidation
 */
class ClamdValidationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->ClamdValidation = new ClamdValidation();
        Cache::setConfig('_cake_core_', [
            'className' => NullEngine::class,
        ]);
    }

    public function tearDown()
    {
        $this->ClamdValidation = null;
        Cache::drop('_cake_core_');
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testValidationReturnTrueByDefaultYouNeedToEnableItInConfiguration()
    {
        $result = $this->ClamdValidation->fileHasNoVirusesFound('not-found');
        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function testValidationFileNotFound()
    {
        Configure::write('CakeDC/Clamav.enabled', true);

        $result = $this->ClamdValidation->fileHasNoVirusesFound('not-found');
        $this->assertSame("Path to uploaded file not found", $result);
    }

    /**
     * @test
     * @return void
     */
    public function testValidationVirusFound()
    {
        Configure::write('CakeDC/Clamav.enabled', true);
        $check = ['tmp_name' => TESTS . 'Fixture/files/testfile.txt'];
        $clamdValidatorMock = $this->getMockBuilder(ClamdValidation::class)
            ->setMethods(['clamdScan'])
            ->getMock();
        $clamdValidatorMock->expects($this->once())
            ->method('clamdScan')
            ->with(reset($check))
            ->willReturn('virus.exe some virus FOUND' . PHP_EOL);
        $this->assertSame('Virus found!', $clamdValidatorMock->fileHasNoVirusesFound($check));
    }

    /**
     * @test
     * @return void
     */
    public function testValidationNoVirusFound()
    {
        Configure::write('CakeDC/Clamav.enabled', true);
        $filePath = TESTS . 'Fixture/files/testfile.txt';
        $check = ['tmp_name' => $filePath];
        $clamdValidatorMock = $this->getMockBuilder(ClamdValidation::class)
            ->setMethods(['clamdScan'])
            ->getMock();
        $clamdValidatorMock->expects($this->once())
            ->method('clamdScan')
            ->with($filePath)
            ->willThrowException(new SocketException('Something went wrong...'));
        $result = $clamdValidatorMock->fileHasNoVirusesFound($check);
        $this->assertContains("Exception while checking the file {$filePath} for viruses: Something went wrong...", $result);
    }

    /**
     * @test
     * @return void
     */
    public function testValidationExceptionChecking()
    {
        Configure::write('CakeDC/Clamav.enabled', true);
        $check = ['tmp_name' => TESTS . 'Fixture/files/testfile.txt'];
        $clamdValidatorMock = $this->getMockBuilder(ClamdValidation::class)
            ->setMethods(['clamdScan'])
            ->getMock();
        $clamdValidatorMock->expects($this->once())
            ->method('clamdScan')
            ->with(reset($check))
            ->willReturn('virus.exe some virus OK' . PHP_EOL);
        $this->assertSame(true, $clamdValidatorMock->fileHasNoVirusesFound($check));
    }

    /**
     * @test
     * @return void
     */
    public function testValidationCheckModeScan()
    {
        Configure::write('CakeDC/Clamav.enabled', true);
        Configure::write('CakeDC/Clamav.socketConfig', ['socketConfig']);
        Configure::write('CakeDC/Clamav.mode', $this->ClamdValidation::MODE_SCAN);
        $filePath = TESTS . 'Fixture/files/testfile.txt';
        $check = ['tmp_name' => $filePath];
        $socketMock = $this->getMockBuilder(Socket::class)
            ->setMethods(['write', 'read'])
            ->getMock();
        $socketMock->expects($this->at(0))
            ->method('write')
            ->with("SCAN " . $filePath)
            ->willReturn(true);
        $socketMock->expects($this->at(1))
            ->method('read')
            ->willReturn('virus.exe some virus OK' . PHP_EOL);
        $clamdValidatorMock = $this->getMockBuilder(ClamdValidation::class)
            ->setMethods(['getSocketInstance'])
            ->getMock();
        $clamdValidatorMock->expects($this->once())
            ->method('getSocketInstance')
            ->with(['socketConfig'])
            ->willReturn($socketMock);
        $this->assertSame(true, $clamdValidatorMock->fileHasNoVirusesFound($check));
    }

    /**
     * @test
     * @return void
     */
    public function testValidationCheckModeInStream()
    {
        Configure::write('CakeDC/Clamav.enabled', true);
        Configure::write('CakeDC/Clamav.socketConfig', ['socketConfig']);
        Configure::write('CakeDC/Clamav.mode', $this->ClamdValidation::MODE_INSTREAM);
        $filePath = TESTS . 'Fixture/files/testfile.txt';
        $check = ['tmp_name' => $filePath];
        $socketMock = $this->getMockBuilder(Socket::class)
            ->setMethods(['write', 'read'])
            ->getMock();
        $socketMock->expects($this->at(0))
            ->method('write')
            ->with("nINSTREAM" . PHP_EOL)
            ->willReturn(true);
        $socketMock->expects($this->at(1))
            ->method('write')
            ->with("\000\000\000!this file might contain a v1rus ?")
            ->willReturn(true);
        $socketMock->expects($this->at(2))
            ->method('write')
            ->with("\000\000\000\000")
            ->willReturn(true);
        $socketMock->expects($this->at(3))
            ->method('read')
            ->willReturn('virus.exe some virus OK' . PHP_EOL);
        $clamdValidatorMock = $this->getMockBuilder(ClamdValidation::class)
            ->setMethods(['getSocketInstance'])
            ->getMock();
        $clamdValidatorMock->expects($this->once())
            ->method('getSocketInstance')
            ->with(['socketConfig'])
            ->willReturn($socketMock);
        $this->assertSame(true, $clamdValidatorMock->fileHasNoVirusesFound($check));
    }
}
