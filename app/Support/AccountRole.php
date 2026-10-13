<?php

namespace App\Support;

enum AccountRole: string
{
    case SuperUser = 'Super User';
    case Admin = 'Admin';
    case Osds = 'OSDS';
    case Chedro = 'CHEDRO';
    case School = 'SCHOOL';
}
