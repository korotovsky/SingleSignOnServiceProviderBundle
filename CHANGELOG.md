CHANGELOG
===================

This changelog references the relevant changes (bug, feature and security fixes)

* 0.3.0 (2015-29-10)
  * Added `sso_url_signer()` twig function for signing any url that interact with IdP.
  * Added `\Krtv\Bundle\SingleSignOnServiceProviderBundle\Context\AuthenticationContextFactory` and `\Krtv\Bundle\SingleSignOnServiceProviderBundle\Context\AuthenticationContext`
    Use `AuthenticationContext` to customize your SSO-flow. For example:
      * Pass trusted extra parameters to IdP using `setServiceExtra('foo', 'bar')`.
      * Pass trusted extra parameters to IdP with come back behaviour using `setServiceExtra('foo', 'bar', true)`.
      * Override `_target_path` value using `setContext(AuthenticationContext::CTX_TARGET_PATH, 'http://sp.tld/page')`.
      * Override `_failure_path` value using `setContext(AuthenticationContext::CTX_FAILURE_PATH, 'http://sp.tld/page')`.
  * Added `AuthenticationContextSubscriber` which adds `AuthenticationContext` instance to request attributes.
  * Added the following firewall options: `sso_service_extra`, `sso_service_extra_parameter`, `sso_login_required` and `sso_login_required_parameter`.
  * `SingleSignOnAuthenticationEntryPoint` has been refactored.

* 0.2.3 (2015-22-03)
  * Ability to set custom OTP fetch implementation as a service,
    must implement `Krtv\SingleSignOn\Manager\Http\Provider\ProviderInterface` and return `Krtv\SingleSignOn\Model\OneTimePasswordInterface` instance if OTP is valid

* 0.2.2 (2015-12-03)
  * Added two new options to firewall config: `ssh_otp_host` and `sso_otp_scheme` to configure host in the redirect uri.
    Useful if you have service provider with 3rd level domains.

* 0.2.1 (2015-02-03)
  * Fixed dependency on `korotovsky/sso-library@0.2.*`

* 0.2.0 (2015-06-02)
  * Added `UserCheckerInterface` calls

* 0.1.1 (2015-12-01)
  * Added SP name in URL when entry point was invoked

* 0.1.0 (2014-23-11)
  * Split components to library
