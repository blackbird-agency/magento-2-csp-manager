<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Test\Unit\Plugin\Model;

use Blackbird\CSPManager\Model\Config\SplitterConfig;
use Blackbird\CSPManager\Plugin\Model\CspRendererPlugin;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Magento\Csp\Model\CspRenderer;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CspRendererPluginTest extends TestCase
{
    private CspRendererPlugin $plugin;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|SplitterConfig $configMock;
    private MockObject|CspRenderer $subjectMock;
    private MockObject|HttpResponse $responseMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->configMock = $this->getMockBuilder(SplitterConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(CspRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseMock = $this->getMockBuilder(HttpResponse::class)
            ->addMethods(['getHeaders'])
            ->getMockForAbstractClass();

        $this->plugin = new CspRendererPlugin(
            $this->loggerMock,
            $this->configMock
        );
    }

    public function testAfterRenderDoesNothingIfNoCspHeader(): void
    {
        $this->responseMock->expects($this->exactly(2))
            ->method('getHeader')
            ->willReturn(false);

        $this->plugin->afterRender($this->subjectMock, null, $this->responseMock);
    }

    public function testAfterRenderLogsErrorIfDisabledAndHeaderTooLarge(): void
    {
        $headerName = 'Content-Security-Policy';
        $headerValue = 'default-src \'self\';';

        $headerMock = $this->getMockBuilder(HeaderInterface::class)->getMock();
        $headerMock->method('getFieldValue')->willReturn($headerValue);

        $this->responseMock->method('getHeader')
            ->with($headerName)
            ->willReturn($headerMock);

        $this->configMock->method('isHeaderSplittingEnabled')->willReturn(false);
        $this->configMock->method('getMaxHeaderSize')->willReturn(10);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('exceeds the maximum size'));

        $this->plugin->afterRender($this->subjectMock, null, $this->responseMock);
    }
}
