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
{% if app.request.query.get('authAll') %}
	<script src="{{ idp_url }}/js/authenticate.js" type="text/javascript"></script>
{% endif %}
``` 

and parameters.yml should contain:
``` bash
idp_url: 'http://YOUR_IDP_GOES_HERE.com'
``` 

For **authenticate.js** file look at [IDP extension](https://github.com/mmilojevic/SingleSignOnIdentityProviderBundle)

Custom UriSigner is introduced because version 2.2 of symfony uses UriSigner with sha1 algorithm but IDP (and generally symfony version > 2.2) uses sha256.

### Note
PHP session names on IDP and all SPs should be different and set in config.yml with ie:
``` bash
framework
	session:
        name: SP1SESSID
```

Single Sign On Service Provider
================================

[![Build Status](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/badges/build.png?b=0.2.x)](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/build-status/0.2.x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/badges/quality-score.png?b=0.2.x)](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/?branch=0.2.x)
[![Code Coverage](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/badges/coverage.png?b=0.2.x)](https://scrutinizer-ci.com/g/korotovsky/SingleSignOnServiceProviderBundle/?branch=0.2.x)
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
````

Modify security settings:

``` yaml
# app/config/security.yml
    firewalls:
        main:
            pattern: ^/
            sso:
                require_previous_session: false
                provider:                 main
                check_path:               /otp/validate/ # Same as in app/config/routing.yml

                sso_scheme:       http              # Required
                sso_host:         idp.example.com   # Required
                sso_otp_scheme:   http              # Optional
                sso_otp_host:     consumer1.com     # Optional
                sso_failure_path: /login
                sso_path:         /sso/login/       # SSO endpoint on IdP.
                sso_service:      consumer1         # Consumer name

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
        name:       http
        managers:
            http:
                provider:    service # Active provider for HTTP OTP manager
                providers:           # Available HTTP providers
                    service:
                        id: acme_bundle.your_own_fetch_service.id

                    guzzle:
                        client:   acme_bundle.guzzle_service.id
                        resource: http://idp.example.com/internal/v1/sso

    otp_parameter:        _otp
    secret_parameter:     secret
```

If you use `service` as a provider to fetch/invalidate OTP tokens, your service must implement the `Krtv\SingleSignOn\Manager\Http\Provider\ProviderInterface` interface.

That's it for Service Provider.
