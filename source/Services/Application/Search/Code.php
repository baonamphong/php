<?php
/**
 * 2007-2016 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    PagSeguro Internet Ltda.
 * @copyright 2007-2016 PagSeguro Internet Ltda.
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 *
 */

namespace PagSeguro\Services\Application\Search;


use PagSeguro\Domains\Account\Credentials;
use PagSeguro\Parsers\Authorization\Search\Code\Request;
use PagSeguro\Resources\Connection;
use PagSeguro\Resources\Http;
use PagSeguro\Resources\Responsibility;

/**
 * Class Payment
 * @package PagSeguro\Services\Checkout
 */
class Code
{

    /**
     * @param \PagSeguro\Domains\Account\Credentials $credentials
     * @param $code
     * @return string
     * @throws \Exception
     */
    public static function search(Credentials $credentials, $code)
    {
        try {
            $connection = new Connection\Data($credentials);
            $http = new Http();
            $http->get(
                self::request($connection, $code)
            );

            return Responsibility::http(
                $http,
                new Request
            );

        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @param Connection\Data $connection
     * @return string
     */
    private static function request(Connection\Data $connection, $code)
    {

        return sprintf(
            "%1s/%2s/?%3s",
            $connection->buildAuthorizationSearchRequestUrl(),
            $code,
            $connection->buildCredentialsQuery()
        );
    }
}
