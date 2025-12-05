<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Twig\Extension;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Media\MediaEntity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaUrlExtension extends AbstractExtension
{

    public function __construct(
        private readonly EntityRepository $mediaRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sw_media_url_by_id', [$this, 'getMediaUrlById']),
        ];
    }

    public function getMediaUrlById(string $mediaId): ?string
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria([$mediaId]);

        /** @var MediaEntity|null $media */
        $media = $this->mediaRepository->search($criteria, $context)->get($mediaId);

        if (!$media || !$media->getFileName() || !$media->getFileExtension()) {
            return null;
        }

        return $media->getUrl();
    }

    public function getName(): string
    {
        return 'nf_admin_media_url_extension';
    }
}