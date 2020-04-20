<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\User\UserIdentity;

interface IIdentityEndpointResolverService
{
	public function getIdentityByToken(AccessTokenInterface $token): UserIdentity;
}
