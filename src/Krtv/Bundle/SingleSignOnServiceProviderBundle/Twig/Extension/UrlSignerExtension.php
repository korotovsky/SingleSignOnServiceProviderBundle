<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Twig\Extension;

use Symfony\Component\HttpKernel\UriSigner;

/**
 * Class VacancyProposalTrackExtension.
 */
class UrlSignerExtension extends \Twig_Extension
{
    /**
     * @var UriSigner
     */
    private $signer;

    /**
     * @param UriSigner $signer
     */
    public function __construct(UriSigner $signer)
    {
        $this->signer = $signer;
    }

    /**
     * @param string $url
     * @return string
     */
    public function getSignedUrl($url)
    {
        return $this->signer->sign($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sso_url_signer', [$this, 'getSignedUrl']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sso_service_provider.url_signer';
    }
}
