Single Sign On Service Provider
================================

[![Build Status](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/badges/build.png?b=0.3.x)](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/build-status/0.3.x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/badges/quality-score.png?b=0.3.x)](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/?branch=0.3.x)
[![Code Coverage](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/badges/coverage.png?b=0.3.x)](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/?branch=0.3.x)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d68cc257-6cfc-4e66-9c51-28be57b347c4/mini.png?v=1)](https://insight.sensiolabs.com/projects/d68cc257-6cfc-4e66-9c51-28be57b347c4)

Disclaimer
--------
I am by no means a security expert. I'm not bad at it either, but I cannot vouch for the security of this bundle.
You can use this in production if you want, but please do so at your own risk.
That said, if you'd like to contribute to make this bundle better/safer, you can always [create an issue](https://github.com/korotovsky/SingleSignOnServiceProviderBundle/issues) or send [a pull request](https://github.com/korotovsky/SingleSignOnServiceProviderBundle/pulls).

Description
-----------
This bundle provides an easy way to integrate a single-sign-on in your website. It uses an existing ('main') firewall for the actual authentication,
and redirects all configured SSO-routes to authenticate via a one-time-password.

Installation
------------
Install using composer:

```
php composer.phar require "korotovsky/sso-sp-bundle"
```

Enable the bundle in the kernel:

``` php
// app/AppKernel.php
$bundles[] = new \Krtv\Bundle\SingleSignOnServiceProviderBundle\SingleSignOnServiceProviderBundle();
```

Configuration
-------------

Enable route to validate OTP:

``` yaml
# app/config/routing.yml
otp:
    # this needs to be the same as the check_path, specified later on in security.yml
    path: /otp/validate/
```

Modify security settings:

``` yaml
# app/config/security.yml
    firewalls:
        main:
            pattern: ^/
            sso:
                require_previous_session: false
                provider:                 main
                check_path:               /otp/validate/     # Same as in app/config/routing.yml

                sso_scheme:               http               # Required
                sso_host:                 idp.example.com    # Required

                sso_otp_scheme:           http               # Optional
                sso_otp_host:             consumer1.com      # Optional

                sso_failure_path:         /login             # Can also be as an absolute path to service provider
                sso_path:                 /sso/login/        # SSO endpoint on IdP.

                sso_service_extra:           null            # Default service extra parameters. Optional.
                sso_service_extra_parameter: service_extra   # Parameter name. Optional

                sso_login_required:           1              # Optional
                sso_login_required_parameter: login_required # Optional

                sso_service:                  consumer1      # Consumer name

            logout:
                invalidate_session: true
                path:               /logout
                target:             http://idp.example.com/sso/logout?service=consumer1
```

Configure SingleSignOnServiceProvider bundle:

``` yaml
# app/config/config.yml
krtv_single_sign_on_service_provider:
    host:                 idp.example.com
    host_scheme:          http

    login_path:           /sso/login/

    # Configuration for OTP managers
    otp_manager:
        name: http
        managers:
            http:
                provider: guzzle     # Active provider for HTTP OTP manager
                providers:           # Available HTTP providers
                    service:
                        # the service must implement Krtv\SingleSignOn\Manager\Http\Provider\ProviderInterface
                        id: krtv_single_sign_on_service_provider.security.authentication.otp_manager.http.provider.guzzle

                    guzzle:
                        # in case you don't have a guzzle client, you must create one
                        client:   acme_bundle.guzzle_service
                        # the route that was created in the IdP bundle
                        resource: http://idp.example.com/internal/v1/sso

    otp_parameter:        _otp
    secret_parameter:     secret
```

Public API of this bundle
-------------------------

This bundle registers several services into service container. This services will help you customize SSO flow in the you application:

- [sso_service_provider.otp_manager](/src/Krtv/SingleSignOn/Manager/OneTimePasswordManagerInterface.php) – Manager for working with OTP-tokens. Checking and receiving.
- [sso_service_provider.uri_signer](https://github.com/symfony/symfony/blob/3.1/src/Symfony/Component/HttpKernel/UriSigner.php) -Service for signing URLs, if you need to redirect users to /sso/login yourself.
