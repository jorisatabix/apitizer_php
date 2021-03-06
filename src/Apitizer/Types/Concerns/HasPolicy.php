<?php

namespace Apitizer\Types\Concerns;

use Apitizer\Policies\AnyPolicy;
use Apitizer\Policies\Policy;

trait HasPolicy
{
    /**
     * @var Policy[] the policies that should be called to verify if a field can
     * be shown to the caller.
     */
    protected $policies = [];

    /**
     * Register one or more new policies
     *
     * This function can safely be called multiple times without overwriting the
     * already defined policies.
     *
     * If the policy is applied to an association but is not dependent on the
     * association data, you will probably want to use the CachedPolicy because
     * the associations are called for each row in the association.
     */
    public function policy(Policy ...$policies): self
    {
        $this->policies = array_merge($this->policies, $policies);

        return $this;
    }

    /**
     * Register a policy that passes if any of the given policies pass.
     *
     * If this function is called multiple times, the given policies will not be
     * grouped together with those of previous calls. The end results looks like
     * the following, wherein each group (parenthesis) is a separate call to
     * policyAny:
     *
     * (policy1 OR policy2 OR ...policyN) AND (policy1 OR policy2 OR ...policyN) AND ...
     *
     * @see HasPolicy::policy
     */
    public function policyAny(Policy ...$policies): self
    {
        $this->policies[] = new AnyPolicy(...$policies);

        return $this;
    }

    /**
     * Check if the current field/value passes the policies.
     */
    protected function passesPolicy($value, $row, $fieldOrAssoc): bool
    {
        foreach ($this->policies as $policy) {
            if (! $policy->passes($value, $row, $fieldOrAssoc)) {
                return false;
            }
        }

        return true;
    }
}
