<?php namespace Zephyrus\Security;

use RuntimeException;
use Zephyrus\Exceptions\Security\UnauthorizedAccessException;
use Zephyrus\Network\Request;

class AuthorizationGuard
{
    private Request $request;
    private AuthorizationRepository $repository;

    public function __construct(Request $request)
    {
        $this->repository = AuthorizationRepository::getInstance();
        $this->request = $request;
    }

    /**
     * @throws UnauthorizedAccessException
     */
    public function run(): void
    {
        $failedRules = [];
        $successRules = [];
        $isStrict = $this->request->getRouteDefinition()->isStrictRuleInterpretation();
        $results = $this->getCorrespondingRuleResults();
        foreach ($results as $rule => $result) {
            if (!$result) {
                $failedRules[] = $rule;
            } else {
                $successRules[] = $rule;
            }
        }
        if (!empty($failedRules)) {
            if ($isStrict || empty($successRules)) {
                throw new UnauthorizedAccessException($this->request->getMethod(), $this->request->getRoute(), $failedRules);
            }
        }
    }

    private function getCorrespondingRuleResults(): array
    {
        $rules = $this->request->getRouteDefinition()->getAuthorizationRules();
        $results = [];
        foreach ($rules as $rule) {
            if (!$this->repository->exists($rule)) {
                // TODO: Custom exception
                throw new RuntimeException("The specified authorization rule [$rule] has not been defined.");
            }
            $results[$rule] = $this->repository->isAuthorized($rule, $this->request);
        }
        return $results;
    }
}
