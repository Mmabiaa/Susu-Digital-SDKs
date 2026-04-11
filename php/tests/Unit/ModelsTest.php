<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SusuDigital\Models\Balance;
use SusuDigital\Models\Collateral;
use SusuDigital\Models\CollateralType;
use SusuDigital\Models\Customer;
use SusuDigital\Models\CustomerStatus;
use SusuDigital\Models\Guarantor;
use SusuDigital\Models\Loan;
use SusuDigital\Models\LoanScheduleItem;
use SusuDigital\Models\LoanStatus;
use SusuDigital\Models\PagedResult;
use SusuDigital\Models\SavingsAccount;
use SusuDigital\Models\SavingsAccountType;
use SusuDigital\Models\Transaction;
use SusuDigital\Models\TransactionStatus;
use SusuDigital\Models\TransactionType;
use SusuDigital\Models\WebhookEvent;

final class ModelsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Customer
    // -------------------------------------------------------------------------

    public function test_customer_hydration_from_array(): void
    {
        $customer = new Customer([
            'id'         => 'cust_001',
            'first_name' => 'Kwame',
            'last_name'  => 'Mensah',
            'phone'      => '+233201234567',
            'email'      => 'kwame@example.com',
            'status'     => 'active',
            'created_at' => '2026-01-01T00:00:00Z',
        ]);

        $this->assertSame('cust_001', $customer->id);
        $this->assertSame('Kwame', $customer->first_name);
        $this->assertSame('Mensah', $customer->last_name);
        $this->assertSame('+233201234567', $customer->phone);
        $this->assertSame('kwame@example.com', $customer->email);
    }

    public function test_customer_full_name(): void
    {
        $customer = new Customer(['first_name' => 'Ama', 'last_name' => 'Owusu']);
        $this->assertSame('Ama Owusu', $customer->getFullName());
    }

    public function test_customer_hydrates_nested_address(): void
    {
        $customer = new Customer([
            'id'      => 'c1',
            'address' => [
                'street'  => '12 Ring Road',
                'city'    => 'Accra',
                'region'  => 'Greater Accra',
                'country' => 'Ghana',
            ],
        ]);

        $this->assertNotNull($customer->address);
        $this->assertSame('Accra', $customer->address->city);
        $this->assertSame('Ghana', $customer->address->country);
    }

    public function test_customer_to_array_excludes_nulls(): void
    {
        $customer = new Customer([
            'id'         => 'cust_002',
            'first_name' => 'Esi',
            'last_name'  => 'Asante',
            'phone'      => '+233507654321',
        ]);

        $arr = $customer->toArray();

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayNotHasKey('email', $arr);        // null → excluded
        $this->assertArrayNotHasKey('date_of_birth', $arr); // null → excluded
    }

    public function test_customer_to_json(): void
    {
        $customer = new Customer(['id' => 'c3', 'first_name' => 'Joe', 'last_name' => 'Doe', 'phone' => '+233']);
        $json     = $customer->toJson();
        $decoded  = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertSame('c3', $decoded['id']);
    }

    // -------------------------------------------------------------------------
    // Transaction
    // -------------------------------------------------------------------------

    public function test_transaction_hydration(): void
    {
        $txn = new Transaction([
            'id'          => 'txn_001',
            'customer_id' => 'cust_001',
            'type'        => 'deposit',
            'amount'      => '250.00',
            'currency'    => 'GHS',
            'status'      => 'completed',
        ]);

        $this->assertSame('txn_001', $txn->id);
        $this->assertSame('deposit', $txn->type);
        $this->assertSame('250.00', $txn->amount);
    }

    // -------------------------------------------------------------------------
    // Loan
    // -------------------------------------------------------------------------

    public function test_loan_hydration_with_guarantors(): void
    {
        $loan = new Loan([
            'id'          => 'loan_001',
            'customer_id' => 'cust_001',
            'amount'      => '5000.00',
            'currency'    => 'GHS',
            'term'        => 12,
            'interest_rate' => '15.0',
            'purpose'     => 'business',
            'status'      => 'approved',
            'guarantors'  => [
                ['name' => 'Abena', 'phone' => '+233', 'relationship' => 'spouse'],
            ],
        ]);

        $this->assertSame('loan_001', $loan->id);
        $this->assertCount(1, $loan->guarantors);
        $this->assertSame('Abena', $loan->guarantors[0]->name);
    }

    public function test_loan_hydration_with_collateral(): void
    {
        $loan = new Loan([
            'id'         => 'loan_002',
            'collateral' => [
                'type'  => 'property',
                'value' => '50000.00',
            ],
        ]);

        $this->assertNotNull($loan->collateral);
        $this->assertSame('property', $loan->collateral->type);
        $this->assertSame('50000.00', $loan->collateral->value);
    }

    // -------------------------------------------------------------------------
    // SavingsAccount
    // -------------------------------------------------------------------------

    public function test_savings_account_has_defaults(): void
    {
        $account = new SavingsAccount([
            'id'          => 'sav_001',
            'customer_id' => 'cust_001',
        ]);

        $this->assertSame('regular', $account->account_type);
        $this->assertSame('GHS', $account->currency);
        $this->assertSame('active', $account->status);
    }

    // -------------------------------------------------------------------------
    // PagedResult
    // -------------------------------------------------------------------------

    public function test_paged_result_total_pages(): void
    {
        $result          = new PagedResult();
        $result->total   = 105;
        $result->limit   = 20;

        $this->assertSame(6, $result->getTotalPages());
    }

    public function test_paged_result_zero_limit(): void
    {
        $result        = new PagedResult();
        $result->total = 10;
        $result->limit = 0;

        $this->assertSame(0, $result->getTotalPages());
    }

    // -------------------------------------------------------------------------
    // WebhookEvent
    // -------------------------------------------------------------------------

    public function test_webhook_event_hydration(): void
    {
        $event = new WebhookEvent([
            'id'         => 'evt_001',
            'type'       => 'transaction.completed',
            'created_at' => '2026-04-11T00:00:00Z',
            'data'       => ['transaction_id' => 'txn_001'],
            'api_version' => 'v1',
        ]);

        $this->assertSame('evt_001', $event->id);
        $this->assertSame('transaction.completed', $event->type);
        $this->assertSame(['transaction_id' => 'txn_001'], $event->data);
    }

    // -------------------------------------------------------------------------
    // Enums
    // -------------------------------------------------------------------------

    public function test_customer_status_enum_values(): void
    {
        $this->assertSame('active', CustomerStatus::Active->value);
        $this->assertSame('inactive', CustomerStatus::Inactive->value);
        $this->assertSame('suspended', CustomerStatus::Suspended->value);
        $this->assertSame('pending', CustomerStatus::Pending->value);
    }

    public function test_transaction_type_enum_values(): void
    {
        $this->assertSame('deposit', TransactionType::Deposit->value);
        $this->assertSame('withdrawal', TransactionType::Withdrawal->value);
        $this->assertSame('transfer', TransactionType::Transfer->value);
    }

    public function test_loan_status_enum_values(): void
    {
        $this->assertSame('pending', LoanStatus::Pending->value);
        $this->assertSame('approved', LoanStatus::Approved->value);
        $this->assertSame('defaulted', LoanStatus::Defaulted->value);
    }

    public function test_savings_account_type_enum_values(): void
    {
        $this->assertSame('regular', SavingsAccountType::Regular->value);
        $this->assertSame('fixed', SavingsAccountType::Fixed->value);
        $this->assertSame('susu', SavingsAccountType::Susu->value);
    }

    public function test_collateral_type_enum_values(): void
    {
        $this->assertSame('property', CollateralType::Property->value);
        $this->assertSame('vehicle', CollateralType::Vehicle->value);
        $this->assertSame('other', CollateralType::Other->value);
    }

    // -------------------------------------------------------------------------
    // fromArray static constructor
    // -------------------------------------------------------------------------

    public function test_from_array_static_constructor(): void
    {
        $customer = Customer::fromArray([
            'id'         => 'cust_sta',
            'first_name' => 'Static',
            'last_name'  => 'Test',
            'phone'      => '+1',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('cust_sta', $customer->id);
    }
}
