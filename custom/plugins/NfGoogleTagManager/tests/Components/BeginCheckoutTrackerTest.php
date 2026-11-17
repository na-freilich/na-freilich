<?php declare(strict_types=1);

namespace NfGoogleTagManager\Tests\Components;

use NfGoogleTagManager\Components\BeginCheckoutTracker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BeginCheckoutTrackerTest extends TestCase
{
    public function testRegisterSendsOnceAndConfirmStaysSilent(): void
    {
        $tracker = new BeginCheckoutTracker();
        $session = new Session(new MockArraySessionStorage());

        $this->assertTrue($tracker->claimOnRegister($session));
        $this->assertFalse($tracker->claimOnRegister($session));
        $this->assertFalse($tracker->claimOnConfirm($session));
        $this->assertTrue($tracker->claimOnConfirm($session));
    }

    public function testConfirmSendsWhenCheckoutDidNotStartOnRegister(): void
    {
        $tracker = new BeginCheckoutTracker();
        $session = new Session(new MockArraySessionStorage());

        $this->assertTrue($tracker->claimOnConfirm($session));
    }
}
