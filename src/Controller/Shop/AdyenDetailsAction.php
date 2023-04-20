<?php
/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusAdyenPlugin\Controller\Shop;

use BitBag\SyliusAdyenPlugin\Resolver\Payment\PaymentDetailsResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdyenDetailsAction
{
    public const REFERENCE_ID_KEY = 'referenceId';

    /** @var PaymentDetailsResolverInterface */
    private $paymentDetailsResolver;

    public function __construct(
        PaymentDetailsResolverInterface $paymentDetailsResolver,
        private LoggerInterface $logger
    ) {
        $this->paymentDetailsResolver = $paymentDetailsResolver;
    }

    public function __invoke(Request $request, string $code): Response
    {
        /** @var ?string $referenceId */
        $referenceId = $request->query->get(self::REFERENCE_ID_KEY);

        if (null === $referenceId) {
            $this->logger->error('Reference ID is missing in AdyenDetailsAction');

            return new Response('Reference ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Resolving payment for reference ID ' . substr($referenceId, 0, 20));

        $payment = $this->paymentDetailsResolver->resolve($code, $referenceId);

        return new JsonResponse($payment->getDetails());
    }
}
