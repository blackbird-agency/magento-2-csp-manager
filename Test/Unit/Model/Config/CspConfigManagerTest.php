<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Test\Unit\Model\Config;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CspConfigManagerTest extends TestCase
{
    private DeploymentConfig|MockObject $deploymentConfigMock;
    private Writer|MockObject $writerMock;
    private CspConfigManager $model;

    protected function setUp(): void
    {
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->writerMock = $this->createMock(Writer::class);
        $this->model = new CspConfigManager(
            $this->deploymentConfigMock,
            $this->writerMock
        );
    }

    public function testSetRuleValueOverwritesExistingValue(): void
    {
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with('csp', [])
            ->willReturn(['img-src' => 'site1.com']);

        $this->writerMock->expects($this->once())
            ->method('saveConfig')
            ->with([
                ConfigFilePool::APP_ENV => [
                    'csp' => ['img-src' => 'site1.com site2.com']
                ]
            ]);

        $this->model->setRuleValue('img-src', 'site1.com site2.com');
    }

    public function testAddRuleValueAddsNewValue(): void
    {
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with('csp', [])
            ->willReturn(['img-src' => 'site1.com']);

        $this->writerMock->expects($this->once())
            ->method('saveConfig')
            ->with([
                ConfigFilePool::APP_ENV => [
                    'csp' => ['img-src' => 'site1.com site2.com']
                ]
            ]);

        $this->model->addRuleValue('img-src', 'site2.com');
    }

    public function testAddRuleValueDoesNotAddDuplicate(): void
    {
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with('csp', [])
            ->willReturn(['img-src' => 'site1.com site2.com']);

        $this->writerMock->expects($this->never())
            ->method('saveConfig');

        $this->model->addRuleValue('img-src', 'site2.com');
    }

    public function testRemoveRuleValueRemovesSpecificValue(): void
    {
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with('csp', [])
            ->willReturn(['img-src' => 'site1.com site2.com']);

        $this->writerMock->expects($this->once())
            ->method('saveConfig')
            ->with([
                ConfigFilePool::APP_ENV => [
                    'csp' => ['img-src' => 'site1.com']
                ]
            ]);

        $this->model->removeRuleValue('img-src', 'site2.com');
    }

    public function testRemoveRuleValueRemovesDirectiveIfEmpty(): void
    {
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with('csp', [])
            ->willReturn(['img-src' => 'site1.com']);

        $this->writerMock->expects($this->once())
            ->method('saveConfig')
            ->with([
                ConfigFilePool::APP_ENV => [
                    'csp' => []
                ]
            ]);

        $this->model->removeRuleValue('img-src', 'site1.com');
    }

    public function testRemoveRuleValueRemovesWholeDirectiveWhenValueIsNull(): void
    {
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with('csp', [])
            ->willReturn(['img-src' => 'site1.com site2.com']);

        $this->writerMock->expects($this->once())
            ->method('saveConfig')
            ->with([
                ConfigFilePool::APP_ENV => [
                    'csp' => []
                ]
            ]);

        $this->model->removeRuleValue('img-src', null);
    }

    public function testIsValidDirectiveReturnsTrueForValidDirective(): void
    {
        $this->assertTrue($this->model->isValidDirective('img-src'));
        $this->assertTrue($this->model->isValidDirective('script-src'));
    }

    public function testIsValidDirectiveReturnsFalseForInvalidDirective(): void
    {
        $this->assertFalse($this->model->isValidDirective('invalid-directive'));
        $this->assertFalse($this->model->isValidDirective('random'));
    }

    public function testGetAllowedDirectivesReturnsArray(): void
    {
        $directives = $this->model->getAllowedDirectives();
        $this->assertIsArray($directives);
        $this->assertContains('img-src', $directives);
        $this->assertContains('default-src', $directives);
    }
}
