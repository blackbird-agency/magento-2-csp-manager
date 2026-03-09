<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class SplitterConfig
 * Configuration for CSP Header Splitter
 */
class SplitterConfig
{
    private const XML_PATH_CSP_SPLITTER_ENABLED = 'csp/splitter/enabled';
    private const XML_PATH_CSP_SPLITTER_MAX_HEADER_SIZE = 'csp/splitter/max_header_size';
    private const DEFAULT_MAX_HEADER_SIZE = 8192; // 8KB
    protected const DEFAULT_OPEN_POLICY_VALUES = [
        'script-src' => "* data: blob: 'unsafe-inline' 'unsafe-eval'",
        'style-src'  => "* data: blob: 'unsafe-inline'",
        'DEFAULT'    => "* data: blob:"
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if header splitting is enabled
     *
     * @return bool
     */
    public function isHeaderSplittingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CSP_SPLITTER_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get maximum header size in bytes
     *
     * @return int
     */
    public function getMaxHeaderSize(): int
    {
        $size = (int)$this->scopeConfig->getValue(
            self::XML_PATH_CSP_SPLITTER_MAX_HEADER_SIZE,
            ScopeInterface::SCOPE_STORE
        );

        return $size > 0 ? $size : self::DEFAULT_MAX_HEADER_SIZE;
    }

    /**
     * Get the open policy mapping for directives
     *
     * @return array
     */
    public function getOpenPolicyMapping(): array
    {
        return static::DEFAULT_OPEN_POLICY_VALUES;
    }
}
