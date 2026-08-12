<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Storefront\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

readonly class CheckoutFinishSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SystemConfigService $systemConfigService,
        private SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        private EntityRepository $newsletterRecipientRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onFinishPageLoaded'
        ];
    }

    public function onFinishPageLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $cmsPageId = $this->systemConfigService->get('NfCustomFinishPage.config.cmsPageId', $event->getSalesChannelContext()->getSalesChannelId());

        if (!$cmsPageId) {
            return;
        }

        $order = $event->getPage()->getOrder();

        $request = $event->getRequest();
        $criteria = new Criteria([$cmsPageId]);

        $pages = $this->cmsPageLoader->load(
            $request,
            $criteria,
            $event->getSalesChannelContext()
        );

        $cmsPage = $pages->first();

        if ($cmsPage instanceof CmsPageEntity) {
            $variables = [
                'order' => $order,
                'context' => $event->getContext(),
            ];
            $this->replacePlaceholders($cmsPage, $variables);
            $event->getPage()->addExtension('customCmsPage', $cmsPage);
        }

        //
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();

        $isSubscribed = false;
        if ($customer) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('email', $customer->getEmail()));

            $isSubscribed = $this->newsletterRecipientRepository
                    ->searchIds($criteria, $context->getContext())
                    ->getTotal() > 0;
        }

        $event->getPage()->assign(['isNewsletterSubscribed' => $isSubscribed]);
    }

    private function replacePlaceholders(CmsPageEntity $cmsPage, array $variables): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        foreach ($cmsPage->getSections() as $section) {
            foreach ($section->getBlocks() as $block) {
                foreach ($block->getSlots() as $slot) {
                    $config = $slot->getConfig();
                    if (!isset($config['content']['value'])) continue;

                    $content = $config['content']['value'];
                    $newContent = preg_replace_callback('/{{\s*(.*?)\s*}}/', function($matches) use ($propertyAccessor, $variables) {
                        $path = $matches[1];

                        try {
                            if (str_contains($path, '.')) {
                                $parts = explode('.', $path);
                                $root = array_shift($parts);
                                $rest = implode('.', $parts);
                                $formattedPath = '[' . $root . '].' . $rest;
                            } else {
                                $formattedPath = '[' . $path . ']';
                            }
                            $value = $propertyAccessor->getValue($variables, $formattedPath);

                            if ($value instanceof \DateTimeInterface) {
                                return $value->format('d.m.Y H:i');
                            }
                            return (string)$value;
                        } catch (\Exception $e) {
                            return $matches[0];
                        }
                    }, $content);

                    $config['content']['value'] = $content;
                    $slot->setConfig($config);

                    $data = $slot->getData();
                    if ($data && method_exists($data, 'setContent')) {
                        $data->setContent($newContent);
                    }
                }
            }
        }
    }
}