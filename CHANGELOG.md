CHANGELOG
===================

This changelog references the relevant changes (bug, feature and security fixes)

* 0.1.3 (2015-22-03)
  * Ability to set custom OTP fetch implementation as a service,
    must implement `Krtv\SingleSignOn\Manager\Http\Provider\ProviderInterface` and return `Krtv\SingleSignOn\Model\OneTimePasswordInterface` instance if OTP is valid

* 0.1.2 (2015-12-03)
  * Added two new options to firewall config: `ssh_otp_host` and `sso_otp_scheme` to configure host in the redirect uri.
    Useful if you have service provider with 3rd level domains.

* 0.1.1 (2015-12-01)
  * Added SP name in URL when entry point was invoked

* 0.1.0 (2014-23-11)
  * Split components to library
