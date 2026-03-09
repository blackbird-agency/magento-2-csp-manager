<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Model\Collector;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;

/**
 * Class EnvPolicyCollector
 * Collects CSP policies defined in env.php
 */
class EnvPolicyCollector implements PolicyCollectorInterface
{
    /**
     * @param CspConfigManager $cspConfigManager
     */
    public function __construct(
        private readonly CspConfigManager $cspConfigManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function collect(array $currentPolicies = []): array
    {
        $rules = $this->cspConfigManager->getRules();
        $policies = [];

        foreach ($rules as $directive => $value) {
            $hosts = array_filter(explode(' ', (string)$value));
            if (!empty($hosts)) {
                $policies[] = new FetchPolicy(
                    $directive,
                    false,
                    $hosts,
                    [],
                    false,
                    false,
                    false,
                    []
                );
            }
        }

        return $policies;
    }
}
