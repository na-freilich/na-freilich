<?php declare(strict_types=1);

namespace NfGoogleTagManager\Components;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BeginCheckoutTracker
{
    public const SESSION_KEY = 'nf_gtm_begin_checkout';

    public function claimOnRegister(SessionInterface $session): bool
    {
        if ($session->get(self::SESSION_KEY) === true) {
            return false;
        }

        $session->set(self::SESSION_KEY, true);

        return true;
    }

    public function claimOnConfirm(SessionInterface $session): bool
    {
        if ($session->get(self::SESSION_KEY) === true) {
            $session->remove(self::SESSION_KEY);

            return false;
        }

        return true;
    }
}
