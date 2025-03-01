<?php

namespace SocialiteProviders\Flexmls;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FLEXMLS';

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept'                => 'application/json',
                'User-Agent'            => config('app.name'),
                'X-SparkApi-User-Agent' => 'ThinkerySocialite',
            ],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://sparkplatform.com/oauth2', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://sparkapi.com/v1/oauth2/grant';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://sparkapi.com/v1/my/account', [
            RequestOptions::HEADERS => [
                'Authorization'         => 'Bearer '.$token,
                'User-Agent'            => config('app.name'),
                'X-SparkApi-User-Agent' => 'ThinkerySocialite',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $profile = $user['D']['Results'][0];

        return (new User)->setRaw($profile)->map([
            'id'       => $profile['Id'],
            'name'     => $profile['Name'],
            'email'    => $profile['Emails'][0]['Address'],
        ]);
    }
}
