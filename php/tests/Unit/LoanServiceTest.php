<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SusuDigital\Http\HttpClient;
use SusuDigital\Models\Loan;
use SusuDigital\Models\LoanScheduleItem;
use SusuDigital\Models\PagedResult;
use SusuDigital\Services\LoanService;

final class LoanServiceTest extends TestCase
{
    private function makeService(array $responses): LoanService
    {
        $mock   = new MockHandler($responses);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);
        $http   = new HttpClient(apiKey: 'sk_test', httpClient: $guzzle);
        return new LoanService($http);
    }

    private function json(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function loanPayload(string $id = 'loan_001'): array
    {
        return [
            'id'            => $id,
            'customer_id'   => 'cust_001',
            'amount'        => '5000.00',
            'currency'      => 'GHS',
            'term'          => 12,
            'interest_rate' => '15.0',
            'purpose'       => 'business',
            'status'        => 'pending',
        ];
    }

    public function test_create_application_returns_loan(): void
    {
        $service = $this->makeService([$this->json($this->loanPayload(), 201)]);
        $loan    = $service->createApplication([
            'customer_id'   => 'cust_001',
            'amount'        => '5000.00',
            'term'          => 12,
            'interest_rate' => '15.0',
            'purpose'       => 'business',
            'currency'      => 'GHS',
        ]);

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertSame('loan_001', $loan->id);
        $this->assertSame('5000.00', $loan->amount);
    }

    public function test_approve_returns_updated_loan(): void
    {
        $payload           = $this->loanPayload();
        $payload['status'] = 'approved';
        $service           = $this->makeService([$this->json($payload)]);
        $loan              = $service->approve('loan_001', [
            'approved_amount' => '5000.00',
            'approved_term'   => 12,
            'approved_rate'   => '15.0',
        ]);

        $this->assertSame('approved', $loan->status);
    }

    public function test_disburse_returns_updated_loan(): void
    {
        $payload           = $this->loanPayload();
        $payload['status'] = 'disbursed';
        $service           = $this->makeService([$this->json($payload)]);
        $loan              = $service->disburse('loan_001', [
            'disbursement_method' => 'mobile_money',
        ]);

        $this->assertSame('disbursed', $loan->status);
    }

    public function test_record_repayment_returns_array(): void
    {
        $service = $this->makeService([$this->json(['recorded' => true])]);
        $result  = $service->recordRepayment('loan_001', [
            'amount'         => '416.67',
            'payment_date'   => '2026-05-01',
            'payment_method' => 'mobile_money',
        ]);

        $this->assertTrue($result['recorded']);
    }

    public function test_get_returns_loan(): void
    {
        $service = $this->makeService([$this->json($this->loanPayload())]);
        $loan    = $service->get('loan_001');

        $this->assertSame('loan_001', $loan->id);
    }

    public function test_get_schedule_returns_schedule_items(): void
    {
        $service = $this->makeService([$this->json([
            'data' => [
                [
                    'installment_number' => 1,
                    'due_date'           => '2026-06-01',
                    'principal'          => '400.00',
                    'interest'           => '62.50',
                    'total'              => '462.50',
                    'outstanding_balance' => '4600.00',
                    'status'             => 'pending',
                ],
            ],
        ])]);

        $schedule = $service->getSchedule('loan_001');

        $this->assertCount(1, $schedule);
        $this->assertInstanceOf(LoanScheduleItem::class, $schedule[0]);
        $this->assertSame(1, $schedule[0]->installment_number);
    }

    public function test_list_returns_paged_result(): void
    {
        $service = $this->makeService([$this->json([
            'data'     => [$this->loanPayload()],
            'total'    => 1,
            'page'     => 1,
            'limit'    => 20,
            'has_next' => false,
            'has_prev' => false,
        ])]);

        $result = $service->list();

        $this->assertInstanceOf(PagedResult::class, $result);
        $this->assertCount(1, $result->data);
    }
}
