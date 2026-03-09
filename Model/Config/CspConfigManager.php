<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Model\Config;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;

/**
 * Class CspConfigManager
 * Responsible for reading and writing CSP rules in env.php
 */
class CspConfigManager
{
    private const CSP_CONFIG_PATH = 'csp';

    /**
     * List of allowed CSP directives according to MDN
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy
     */
    private const ALLOWED_DIRECTIVES = [
        'child-src',
        'connect-src',
        'default-src',
        'font-src',
        'frame-src',
        'img-src',
        'manifest-src',
        'media-src',
        'object-src',
        'prefetch-src',
        'script-src',
        'script-src-elem',
        'script-src-attr',
        'style-src',
        'style-src-elem',
        'style-src-attr',
        'worker-src',
        'base-uri',
        'sandbox',
        'form-action',
        'frame-ancestors',
        'navigate-to',
        'report-uri',
        'report-to',
        'block-all-mixed-content',
        'upgrade-insecure-requests',
        'require-trusted-types-for',
        'trusted-types',
        'plugin-types',
    ];

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param Writer $writer
     */
    public function __construct(
        private readonly DeploymentConfig $deploymentConfig,
        private readonly Writer $writer
    ) {
    }

    /**
     * Get all CSP rules from env.php
     *
     * @return array
     */
    public function getRules(): array
    {
        return (array)$this->deploymentConfig->get(self::CSP_CONFIG_PATH, []);
    }

    /**
     * Set a CSP directive to a specific value in env.php (overwrites existing value)
     *
     * @param string $directive
     * @param string $value
     * @return void
     */
    public function setRuleValue(string $directive, string $value): void
    {
        $rules = $this->getRules();
        $rules[$directive] = $value;
        $this->saveRules($rules);
    }

    /**
     * Add a CSP value to a directive in env.php
     *
     * @param string $directive
     * @param string $value
     * @return void
     */
    public function addRuleValue(string $directive, string $value): void
    {
        $rules = $this->getRules();
        $currentValue = $rules[$directive] ?? '';
        $values = array_filter(explode(' ', (string)$currentValue));

        if (!in_array($value, $values, true)) {
            $values[] = $value;
            $rules[$directive] = implode(' ', $values);
            $this->saveRules($rules);
        }
    }

    /**
     * Remove a CSP value from a directive in env.php
     *
     * @param string $directive
     * @param string|null $value
     * @return void
     */
    public function removeRuleValue(string $directive, ?string $value = null): void
    {
        $rules = $this->getRules();
        if (!isset($rules[$directive])) {
            return;
        }

        if ($value === null) {
            unset($rules[$directive]);
            $this->saveRules($rules);
            return;
        }

        $values = array_filter(explode(' ', (string)$rules[$directive]));
        $key = array_search($value, $values, true);

        if ($key !== false) {
            unset($values[$key]);
            if (empty($values)) {
                unset($rules[$directive]);
            } else {
                $rules[$directive] = implode(' ', $values);
            }
            $this->saveRules($rules);
        }
    }

    /**
     * Check if a directive is allowed according to MDN
     *
     * @param string $directive
     * @return bool
     */
    public function isValidDirective(string $directive): bool
    {
        return in_array($directive, self::ALLOWED_DIRECTIVES, true);
    }

    /**
     * Get the list of allowed directives
     *
     * @return string[]
     */
    public function getAllowedDirectives(): array
    {
        return self::ALLOWED_DIRECTIVES;
    }

    /**
     * Save rules to env.php
     *
     * @param array $rules
     * @return void
     */
    protected function saveRules(array $rules): void
    {
        $this->writer->saveConfig([
            ConfigFilePool::APP_ENV => [
                self::CSP_CONFIG_PATH => $rules
            ]
        ]);
    }
}
