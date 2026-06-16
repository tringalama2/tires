<?php

use App\Models\Placement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('detects center wear when center is 2+ below avg(inner, outer)', function () {
    $p = new Placement(['tread_center' => 4.0, 'tread_inner' => 7.0, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeTrue()
        ->and($p->isEdgeWear())->toBeFalse();
});

it('detects edge wear when center is 2+ above avg(inner, outer)', function () {
    $p = new Placement(['tread_center' => 8.0, 'tread_inner' => 5.0, 'tread_outer' => 5.0]);

    expect($p->isEdgeWear())->toBeTrue()
        ->and($p->isCenterWear())->toBeFalse();
});

it('does not flag center wear when difference is under 2', function () {
    $p = new Placement(['tread_center' => 5.5, 'tread_inner' => 7.0, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeFalse();
});

it('does not flag wear when inner or outer is null', function () {
    $p = new Placement(['tread_center' => 4.0, 'tread_inner' => null, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeFalse()
        ->and($p->isEdgeWear())->toBeFalse();
});

it('flags exactly 2/32" difference as center wear', function () {
    $p = new Placement(['tread_center' => 5.0, 'tread_inner' => 7.0, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeTrue();
});
