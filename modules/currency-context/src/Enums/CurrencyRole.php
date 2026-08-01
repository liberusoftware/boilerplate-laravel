<?php

namespace Liberu\Foundation\Currency\Enums;

enum CurrencyRole: string
{
    case Base = 'base';
    case Transaction = 'transaction';
    case Settlement = 'settlement';
    case Display = 'display';
}
