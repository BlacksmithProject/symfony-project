<?php declare(strict_types=1);

namespace App\Infrastructure\Symfony;

use Assert\Assert;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class RequestListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        Assert::that($request->getContent())->string();

        $data = json_decode($request->getContent(), true);

        $request->request->replace(is_array($data) ? $data : []);
    }
}
