<?php

namespace Ecosys\OAuth\Model\Entity;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * AccessTokenEntity Class
 *
 * This class was created to resolve the "class not found" fatal error.
 * It implements the required interface from the league/oauth2-server library
 * and uses the provided traits to handle access token functionality.
 */
class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait, EntityTrait, TokenEntityTrait;
}
