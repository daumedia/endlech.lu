<?php

namespace App\Tests\Unit\Api;

use App\Api\AssetUrlBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AssetUrlBuilderTest extends TestCase
{
    public function testUsesCurrentRequestHost(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('https://api.example.com/api/v1/me'));
        $builder = new AssetUrlBuilder($stack);

        self::assertSame('https://api.example.com/uploads/restaurants/x.jpg', $builder->absolute('/uploads/restaurants/x.jpg'));
    }

    public function testExplicitBaseUrlOverridesRequest(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('http://localhost/api/v1/me'));
        $builder = new AssetUrlBuilder($stack, 'https://cdn.endlech.lu/');

        self::assertSame('https://cdn.endlech.lu/uploads/avatars/a.png', $builder->absolute('/uploads/avatars/a.png'));
    }

    public function testReturnsNullForEmptyPath(): void
    {
        $builder = new AssetUrlBuilder(new RequestStack());

        self::assertNull($builder->absolute(null));
        self::assertNull($builder->absolute(''));
    }

    public function testFallsBackToRelativeWithoutRequestOrBase(): void
    {
        $builder = new AssetUrlBuilder(new RequestStack());

        self::assertSame('/uploads/restaurants/x.jpg', $builder->absolute('/uploads/restaurants/x.jpg'));
    }
}
