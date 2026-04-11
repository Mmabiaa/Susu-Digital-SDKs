<?php

declare(strict_types=1);

namespace SusuDigital\Models;

enum CollateralType: string
{
    case Property  = 'property';
    case Vehicle   = 'vehicle';
    case Equipment = 'equipment';
    case Savings   = 'savings';
    case Other     = 'other';
}
