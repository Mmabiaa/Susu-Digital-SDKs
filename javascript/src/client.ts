/**
 * The primary entry-point for the Susu Digital JavaScript/TypeScript SDK.
 *
 * @example
 * ```ts
 * import { SusuDigitalClient } from '@susudigital/sdk';
 *
 * const client = new SusuDigitalClient({
 *   apiKey: process.env.SUSU_API_KEY!,
 *   environment: 'production',
 *   organization: process.env.SUSU_ORGANIZATION_ID,
 *   timeout: 30,
 *   retryAttempts: 3,
 * });
 *
 * const customer = await client.customers.create({ ... });
 * const deposit  = await client.transactions.deposit({ ... });
 * const loan     = await client.loans.createApplication({ ... });
 * ```
 */

import { HttpClient } from './http.js';
import {
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
} from './services.js';
import type { SusuDigitalClientConfig } from './types.js';

export class SusuDigitalClient {
    /** @internal */
    private readonly _http: HttpClient;

    readonly customers: CustomerService;
    readonly transactions: TransactionService;
    readonly loans: LoanService;
    readonly savings: SavingsService;
    readonly analytics: AnalyticsService;

    constructor(config: SusuDigitalClientConfig) {
        if (!config.apiKey) {
            throw new Error('SusuDigitalClient: apiKey is required');
        }
        this._http = new HttpClient(config);
        this.customers = new CustomerService(this._http);
        this.transactions = new TransactionService(this._http);
        this.loans = new LoanService(this._http);
        this.savings = new SavingsService(this._http);
        this.analytics = new AnalyticsService(this._http);
    }

    /**
     * Create a client from environment variables.
     *
     * Reads: SUSU_API_KEY, SUSU_ENVIRONMENT, SUSU_ORGANIZATION_ID,
     *        SUSU_TIMEOUT, SUSU_MAX_RETRIES, SUSU_ENABLE_LOGGING
     */
    static fromEnv(): SusuDigitalClient {
        const apiKey = process.env['SUSU_API_KEY'];
        if (!apiKey) {
            throw new Error('SusuDigitalClient.fromEnv(): SUSU_API_KEY environment variable is not set');
        }
        return new SusuDigitalClient({
            apiKey,
            environment: (process.env['SUSU_ENVIRONMENT'] as 'sandbox' | 'production') ?? 'sandbox',
            organization: process.env['SUSU_ORGANIZATION_ID'],
            timeout: process.env['SUSU_TIMEOUT'] ? parseInt(process.env['SUSU_TIMEOUT'], 10) : 30,
            retryAttempts: process.env['SUSU_MAX_RETRIES']
                ? parseInt(process.env['SUSU_MAX_RETRIES'], 10)
                : 3,
            enableLogging: process.env['SUSU_ENABLE_LOGGING'] === 'true',
        });
    }
}
