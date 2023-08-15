<?php

namespace RazerPay\Payment\Plugin\Magento\Framework\Stdlib\Cookie\PhpCookieManager;

class ForceNoneSameSite
{
    protected $affectedKeys = [
        'PHPSESSID',
        'form_key',
        'private_content_version',
        'X-Magento-Vary',
    ];

    /**
     * @param  \Magento\Framework\Stdlib\Cookie\PhpCookieManager  $subject
     * @param $name
     * @param $value
     * @param  \Magento\Framework\Stdlib\Cookie\PublicCookieMetadata|null  $metadata
     *
     * @return array
     */
    public function beforeSetPublicCookie(
        \Magento\Framework\Stdlib\Cookie\PhpCookieManager $subject,
        $name,
        $value,
        \Magento\Framework\Stdlib\Cookie\PublicCookieMetadata $metadata = null
    ) {
        if (in_array($name, $this->affectedKeys) && $metadata && method_exists($metadata, 'setSameSite')) {
            $metadata->setSameSite('None');
        }

        return [$name, $value, $metadata];
    }
}
