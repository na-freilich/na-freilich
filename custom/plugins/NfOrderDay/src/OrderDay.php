<?php declare(strict_types=1);

namespace Nf\OrderDay;

use Nf\OrderDay\Service\CustomFieldService;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class OrderDay extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        // Do stuff such as creating a new payment method
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        // Remove or deactivate the data created by the plugin
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $customFieldService = new CustomFieldService($this->container);
        $customFieldService->createAdditionalManufacturerCustomFieldSet($activateContext->getContext());
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        $customFieldService = new CustomFieldService($this->container);
        $customFieldService->deleteCustomFieldSet(CustomFieldService::ADDITIONAL_ORDER_DAY_CUSTOM_FIELD_SET, $deactivateContext->getContext());

    }

    public function update(UpdateContext $updateContext): void
    {
        // Update necessary stuff, mostly non-database related
    }

}
