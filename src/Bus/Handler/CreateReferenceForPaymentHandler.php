<?php
/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusAdyenPlugin\Bus\Handler;

use BitBag\SyliusAdyenPlugin\Bus\Command\CreateReferenceForPayment;
use BitBag\SyliusAdyenPlugin\Entity\AdyenReferenceInterface;
use BitBag\SyliusAdyenPlugin\Factory\AdyenReferenceFactoryInterface;
use BitBag\SyliusAdyenPlugin\Repository\AdyenReferenceRepositoryInterface;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Webmozart\Assert\Assert;

final class CreateReferenceForPaymentHandler implements MessageHandlerInterface
{
    /** @var AdyenReferenceRepositoryInterface */
    private $adyenReferenceRepository;

    /** @var AdyenReferenceFactoryInterface */
    private $adyenReferenceFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AdyenReferenceRepositoryInterface $adyenReferenceRepository,
        AdyenReferenceFactoryInterface $adyenReferenceFactory,
        LoggerInterface $logger
    ) {
        $this->adyenReferenceRepository = $adyenReferenceRepository;
        $this->adyenReferenceFactory = $adyenReferenceFactory;
        $this->logger = $logger;
    }

    private function getExisting(AdyenReferenceInterface $adyenReference): ?AdyenReferenceInterface
    {
        $payment = $adyenReference->getPayment();
        Assert::notNull($payment);

        $method = $payment->getMethod();
        Assert::notNull($method);

        $code = (string) $method->getCode();

        try {
            return $this->adyenReferenceRepository->getOneByCodeAndReference(
                $code,
                (string) $adyenReference->getPspReference()
            );
        } catch (NoResultException $ex) {
            $this->logger->error(sprintf('No result found for code %s and reference %s', $code, $adyenReference->getPspReference()));
            $this->logger->error($ex->getMessage());

            return null;
        }
    }

    public function __invoke(CreateReferenceForPayment $referenceCommand): void
    {
        $this->logger->info(sprintf('Handling "CreateReferenceForPayment" command for payment with ID %d', [$referenceCommand->getPayment()->getId()]));

        $object = $this->adyenReferenceFactory->createForPayment($referenceCommand->getPayment());
        $existing = $this->getExisting($object);

        if (null !== $existing) {
            $existing->touch();
            $object = $existing;
        }

        $this->adyenReferenceRepository->add($object);
    }
}
