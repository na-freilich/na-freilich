<?php declare(strict_types=1);

namespace NfAgeVerification;

use Shopware\Core\Framework\Plugin;
use NfAgeVerification\Service\CustomFieldService;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class NfAgeVerification extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldService = new CustomFieldService($customFieldSetRepository);

        $customFieldService->createCustomFieldSet($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldService = new CustomFieldService($customFieldSetRepository);

        $customFieldService->deleteCustomFieldSet($uninstallContext->getContext());
    }
}
