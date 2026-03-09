<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Test\Unit\Model\Collector;

use Blackbird\CSPManager\Model\Collector\EnvPolicyCollector;
use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Magento\Csp\Model\Policy\FetchPolicy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EnvPolicyCollectorTest extends TestCase
{
    private CspConfigManager|MockObject $cspConfigManagerMock;
    private EnvPolicyCollector $model;

    protected function setUp(): void
    {
        $this->cspConfigManagerMock = $this->createMock(CspConfigManager::class);
        $this->model = new EnvPolicyCollector($this->cspConfigManagerMock);
    }

    public function testCollectReturnsPolicies(): void
    {
        $rules = [
            'img-src' => 'monsite.com cdn.monsite.dam',
            'script-src' => 'scripts.cdn.com'
        ];

        $this->cspConfigManagerMock->expects($this->once())
            ->method('getRules')
            ->willReturn($rules);

        $result = $this->model->collect();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(FetchPolicy::class, $result[0]);
        $this->assertEquals('img-src', $result[0]->getId());
        $this->assertEquals(['monsite.com', 'cdn.monsite.dam'], $result[0]->getHostSources());
        $this->assertEquals('script-src', $result[1]->getId());
        $this->assertEquals(['scripts.cdn.com'], $result[1]->getHostSources());
    }

    public function testCollectReturnsEmptyWhenNoRules(): void
    {
        $this->cspConfigManagerMock->expects($this->once())
            ->method('getRules')
            ->willReturn([]);

        $result = $this->model->collect();

        $this->assertEmpty($result);
    }
}
