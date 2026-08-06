<?php

use Liberu\Foundation\Localization\Tests\TestCase;
use Liberu\PackageTestbench\PackageTestCase;

pest()->extend(PackageTestCase::class)->in('Unit');
pest()->extend(TestCase::class)->in('Feature');
