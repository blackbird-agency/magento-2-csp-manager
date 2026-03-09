<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Test\Unit\Console\Command;

use Blackbird\CSPManager\Console\Command\AddCspCommand;
use Blackbird\CSPManager\Model\Config\CspConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AddCspCommandTest extends TestCase
{
    private CspConfigManager|MockObject $cspConfigManagerMock;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->cspConfigManagerMock = $this->createMock(CspConfigManager::class);
        $command = new AddCspCommand($this->cspConfigManagerMock);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($application->get('csp:rule:add'));
    }

    public function testExecuteAddsMultipleValues(): void
    {
        $directive = 'img-src';
        $values = ['site1.com', 'site2.com'];

        $this->cspConfigManagerMock->expects($this->once())
            ->method('isValidDirective')
            ->with($directive)
            ->willReturn(true);

        $this->cspConfigManagerMock->expects($this->exactly(2))
            ->method('addRuleValue')
            ->withConsecutive(
                [$directive, 'site1.com'],
                [$directive, 'site2.com']
            );

        $this->commandTester->execute([
            'directive' => $directive,
            'value' => $values
        ]);

        $this->assertStringContainsString('successfully', $this->commandTester->getDisplay());
    }

    public function testExecuteFailsOnInvalidDirective(): void
    {
        $directive = 'invalid';
        $this->cspConfigManagerMock->method('isValidDirective')->willReturn(false);
        $this->cspConfigManagerMock->method('getAllowedDirectives')->willReturn(['img-src']);

        $this->commandTester->execute([
            'directive' => $directive,
            'value' => ['site1.com']
        ]);

        $this->assertStringContainsString('Invalid CSP directive', $this->commandTester->getDisplay());
    }
}
