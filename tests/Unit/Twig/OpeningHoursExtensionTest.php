<?php

namespace App\Tests\Unit\Twig;

use App\Entity\Restaurant;
use App\Service\OpeningHoursService;
use App\Twig\OpeningHoursExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class OpeningHoursExtensionTest extends TestCase
{
    public function testRegistersFilterAndFunction(): void
    {
        $extension = new OpeningHoursExtension($this->createStub(OpeningHoursService::class));

        $filters = $extension->getFilters();
        self::assertCount(1, $filters);
        self::assertInstanceOf(TwigFilter::class, $filters[0]);
        self::assertSame('is_open_now', $filters[0]->getName());

        $functions = $extension->getFunctions();
        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('next_opening_time', $functions[0]->getName());
    }

    public function testIsOpenNowDelegatesToService(): void
    {
        $restaurant = new Restaurant();
        $service = $this->createMock(OpeningHoursService::class);
        $service->expects(self::once())
            ->method('isOpenNow')
            ->with($restaurant)
            ->willReturn(true);

        $extension = new OpeningHoursExtension($service);

        self::assertTrue($extension->isOpenNow($restaurant));
    }

    public function testGetNextOpeningTimeDelegatesToService(): void
    {
        $restaurant = new Restaurant();
        $next = ['dayOfWeek' => 3, 'time' => new \DateTime('18:00')];

        $service = $this->createMock(OpeningHoursService::class);
        $service->expects(self::once())
            ->method('getNextOpeningTime')
            ->with($restaurant)
            ->willReturn($next);

        $extension = new OpeningHoursExtension($service);

        self::assertSame($next, $extension->getNextOpeningTime($restaurant));
    }
}
