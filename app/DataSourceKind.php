<?php

namespace App;

enum DataSourceKind: string
{
    case Internal = 'internal';
    case Provider = 'provider';
}
