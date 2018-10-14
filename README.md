Single Sign On Service Provider - Extension
================================
Below is forked readme from korotovsky, here will be explained what this extension provides:

With original SSO implementation there was a problem when we are authenticated on IDP and SP1, but not on SP2. So when we visit SP2 public route SSO authentication is not triggered and user sees the public page of SP2 as not logged in user. So SsoAuthenticationController is added which is hit by /sso/authenticate-user path from SP1 ajax call and this ajax call generates session on SP2. Offcourse on IDP, SP1, SP2... some CORS settings on http server should be added. 

So this to be working on SP in routing.yml file should be added:

``` bash
KrtvSingleSignOnServiceProviderBundle:
resource: "@KrtvSingleSignOnServiceProviderBundle/Resources/config/routing.yml"
prefix:   /    
``` 

and in base twig:

``` bash
{% if app.request.query.get('ajax') %}
	<script src="{{ idp_url }}/js/authenticate.js" type="text/javascript"></script>
{% endif %}
``` 

and parameters.yml should contain:
``` bash
idp_url: 'http://YOUR_IDP_GOES_HERE.com'
``` 

For **authenticate.js** file look at [IDP extension](https://github.com/mmilojevic/SingleSignOnIdentityProviderBundle)

SingleSignOnAuthenticationEntryPoint.php had an extra hashing which is commented out.
HttpUtils from symfony core from version 3.2 don't allow redirect to other domain (IDP). So calls like:
``` bash
return $this->httpUtils->createRedirectResponse($request, $redirectUri);
``` 
are changed with:

``` bash
return new RedirectResponse($this->httpUtils->generateUri($request, $redirectUri), 302);
```

Regarding this redirect problem logout target from security.yml is changed from:
``` bash
target:             http://idp.example.com/sso/logout?service=consumer1
```
to:
``` bash
target:             http://isp.example.com/sso/logout-user/consumer1
```
And offcourse appropriate route exists in SsoAuthenticationController.

### Note

PHP session names on IDP and all SPs should be different and set in config.yml with ie:
``` bash
framework
	session:
        name: SP1SESSID
```

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
Installation is a quick 5 steps process:

1. Download SingleSignOnServiceProviderBundle using composer
2. Enable the bundle
3. Configure SingleSignOnServiceProviderBundle
4. Enable the route to validate OTP
5. Modify security settings

### Step 1: Download SingleSignOnServiceProviderBundle using composer

Tell composer to require the package:

``` bash
composer require korotovsky/sso-sp-bundle
```

Composer will install the bundle to your project's `vendor/korotovsky` directory.

### Step 2: Enable the bundle

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ...
        new Krtv\Bundle\SingleSignOnServiceProviderBundle\KrtvSingleSignOnServiceProviderBundle(),
    ];
}
?>
```

### Step 3: Configure SingleSignOnServiceProviderBundle

Add the following settings to your **config.yml**.

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

### Step 4: Enable route to validate OTP

``` yaml
# app/config/routing.yml
otp:
    # this needs to be the same as the check_path, specified later on in security.yml
    path: /otp/validate/
```

### Step 5: Modify security settings

``` yaml
# app/config/security.yml
security:
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

Public API of this bundle
-------------------------

This bundle registers several services into service container. This services will help you customize SSO flow in the you application:

- [sso_service_provider.otp_manager](https://github.com/korotovsky/SingleSignOnLibrary/blob/0.3.x/src/Krtv/SingleSignOn/Manager/OneTimePasswordManagerInterface.php) – Manager for working with OTP-tokens. Checking and receiving.
- [sso_service_provider.uri_signer](https://github.com/symfony/symfony/blob/3.1/src/Symfony/Component/HttpKernel/UriSigner.php) -Service for signing URLs, if you need to redirect users to /sso/login yourself.