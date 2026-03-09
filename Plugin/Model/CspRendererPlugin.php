<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Plugin\Model;

use Blackbird\CSPManager\Model\Config\SplitterConfig;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\Http\Header\ContentSecurityPolicyReportOnly;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Loader\PluginClassLoader;
use Magento\Csp\Model\CspRenderer;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

/**
 * Plugin for CSP Renderer to split CSP headers
 */
class CspRendererPlugin
{
    private const PLUGINS = [
        'contentsecuritypolicyreportonly' => ContentSecurityPolicyReportOnly::class,
        'contentsecuritypolicy'           => ContentSecurityPolicy::class,
    ];

    /**
     * Names of the CSP headers
     */
    private const HEADER_NAMES = [
        'Content-Security-Policy',
        'Content-Security-Policy-Report-Only'
    ];

    private const DEFAULT_SRC = 'default-src';

    /**
     * @param LoggerInterface $logger
     * @param SplitterConfig $config
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SplitterConfig $config
    ) {
    }

    /**
     * After render plugin for CspRenderer
     *
     * @param CspRenderer $subject
     * @param void $result
     * @param HttpResponse $response
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRender(
        CspRenderer $subject,
        $result,
        HttpResponse $response
    ): void {
        $headerName = $this->getHeaderName($response);
        if (!$headerName) {
            return;
        }

        $header = $response->getHeader($headerName);
        if (!$header instanceof HeaderInterface) {
            return;
        }

        $headerValue = $header->getFieldValue();
        $isHeaderSplittingEnabled = $this->config->isHeaderSplittingEnabled();
        $maxHeaderSize = $this->config->getMaxHeaderSize();
        $currentHeaderSize = strlen($headerValue);

        if ($isHeaderSplittingEnabled) {
            $this->registerCspHeaderPlugins($response);
            $this->splitUpCspHeaders($response, $headerName, $headerValue);
        } elseif ($maxHeaderSize < $currentHeaderSize) {
            $this->logger->error(
                sprintf(
                    'Unable to set the CSP header. The header size of %d bytes exceeds the ' .
                    'maximum size of %d bytes.',
                    $currentHeaderSize,
                    $maxHeaderSize
                )
            );
        }
    }

    /**
     * The CSP headers normally use the GenericHeader class, which does not support multi-header values.
     * The Laminas framework includes multi-value supported special classes for CSP headers.
     * With this registration we enable the usage of the special classes by registering the definitions to the
     * plugin loader class.
     *
     * @param HttpResponse $response
     * @return void
     */
    private function registerCspHeaderPlugins(HttpResponse $response): void
    {
        /** @var AbstractMessage $response */
        $pluginClassLoader = $response->getHeaders()->getPluginClassLoader();
        $pluginClassLoader->registerPlugins(self::PLUGINS);
    }

    /**
     * Make sure that the CSP headers are handled as several headers ("multi-header")
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $headerValue
     * @return void
     */
    private function splitUpCspHeaders(HttpResponse $response, string $headerName, string $headerValue): void
    {
        $maxHeaderSize = $this->config->getMaxHeaderSize();
        $policies = $this->parsePolicies($headerValue);

        if (empty($policies)) {
            return;
        }

        $defaultSrcValue = $policies[self::DEFAULT_SRC] ?? null;
        $allDirectives = array_keys($policies);

        $headerParts = [];
        $currentPartPolicies = [];

        foreach ($policies as $directive => $value) {
            if ($directive === self::DEFAULT_SRC) {
                continue;
            }

            $potentialPartSize = $this->calculatePartSize(
                $currentPartPolicies,
                $directive,
                $value,
                $defaultSrcValue,
                $allDirectives
            );

            if ($potentialPartSize > $maxHeaderSize && !empty($currentPartPolicies)) {
                $headerParts[] = $this->buildHeaderPart($currentPartPolicies, $defaultSrcValue, $allDirectives);
                $currentPartPolicies = [];
            }

            $currentPartPolicies[$directive] = $value;
        }

        if (!empty($currentPartPolicies) || empty($headerParts)) {
            $headerParts[] = $this->buildHeaderPart($currentPartPolicies, $defaultSrcValue, $allDirectives);
        }

        foreach ($headerParts as $index => $headerPart) {
            $response->setHeader($headerName, $headerPart, $index === 0);
        }
    }

    /**
     * Parse CSP header value into an associative array of directives and their values
     *
     * @param string $headerValue
     * @return array
     */
    private function parsePolicies(string $headerValue): array
    {
        $policies = [];
        $parts = explode(';', $headerValue);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            $bits = explode(' ', $part, 2);
            $directive = $bits[0];
            $value = $bits[1] ?? '';
            $policies[$directive] = $value;
        }
        return $policies;
    }

    /**
     * Calculate the size of the header part if a new directive is added
     *
     * @param array $currentPartPolicies
     * @param string $newDirective
     * @param string $newValue
     * @param string|null $defaultSrcValue
     * @param array $allDirectives
     * @return int
     */
    private function calculatePartSize(
        array $currentPartPolicies,
        string $newDirective,
        string $newValue,
        ?string $defaultSrcValue,
        array $allDirectives
    ): int {
        $tempPolicies = $currentPartPolicies;
        $tempPolicies[$newDirective] = $newValue;
        return strlen($this->buildHeaderPart($tempPolicies, $defaultSrcValue, $allDirectives));
    }

    /**
     * Build a single CSP header part with default-src and "opened" directives
     *
     * @param array $currentPartPolicies
     * @param string|null $defaultSrcValue
     * @param array $allDirectives
     * @return string
     */
    private function buildHeaderPart(array $currentPartPolicies, ?string $defaultSrcValue, array $allDirectives): string
    {
        $parts = [];
        if ($defaultSrcValue !== null) {
            $parts[] = self::DEFAULT_SRC . ' ' . $defaultSrcValue . ';';
        }

        foreach ($allDirectives as $directive) {
            if ($directive === self::DEFAULT_SRC) {
                continue;
            }

            $value = $currentPartPolicies[$directive] ?? $this->getOpenPolicyValue($directive);
            $parts[] = $directive . ' ' . $value . ';';
        }

        return implode(' ', $parts);
    }

    /**
     * Get the open policy value for a directive
     *
     * @param string $directive
     * @return string
     */
    private function getOpenPolicyValue(string $directive): string
    {
        $mapping = $this->config->getOpenPolicyMapping();
        return $mapping[$directive] ?? $mapping['DEFAULT'];
    }

    /**
     * Get the CSP header name from the response
     *
     * @param HttpResponse $response
     * @return string
     */
    private function getHeaderName(HttpResponse $response): string
    {
        foreach (self::HEADER_NAMES as $name) {
            if ($response->getHeader($name)) {
                return $name;
            }
        }
        return '';
    }
}
