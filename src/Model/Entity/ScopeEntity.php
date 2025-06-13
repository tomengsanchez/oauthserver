<?php

namespace Ecosys\OAuth\Model\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;

    /**
     * Specify data which should be serialized to JSON.
     * This method is required by the JsonSerializable interface and was added
     * to resolve the "must implement abstract methods" fatal error.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->getIdentifier();
    }
}
