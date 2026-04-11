<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class LoanScheduleItem extends BaseModel
{
    public int $installment_number = 0;
    public string $due_date        = '';
    public string $principal       = '0.00';
    public string $interest        = '0.00';
    public string $total           = '0.00';
    public string $outstanding_balance = '0.00';
    public string $status          = '';
}
