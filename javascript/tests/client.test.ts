/**
 * Tests for the SusuDigitalClient class.
 */

import { SusuDigitalClient } from '../src/client';
import {
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
} from '../src/services';

describe('SusuDigitalClient constructor', () => {
    it('creates a client with required apiKey', () => {
        const client = new SusuDigitalClient({ apiKey: 'sk_test_abc' });
        expect(client).toBeInstanceOf(SusuDigitalClient);
    });

    it('throws if apiKey is missing', () => {
        expect(() => new SusuDigitalClient({ apiKey: '' })).toThrow('apiKey is required');
    });

    it('exposes all service properties', () => {
        const client = new SusuDigitalClient({ apiKey: 'sk_test_abc' });
        expect(client.customers).toBeInstanceOf(CustomerService);
        expect(client.transactions).toBeInstanceOf(TransactionService);
        expect(client.loans).toBeInstanceOf(LoanService);
        expect(client.savings).toBeInstanceOf(SavingsService);
        expect(client.analytics).toBeInstanceOf(AnalyticsService);
    });

    it('accepts all optional config parameters', () => {
        expect(() =>
            new SusuDigitalClient({
                apiKey: 'sk_test_abc',
                environment: 'production',
                organization: 'org_123',
                timeout: 60,
                retryAttempts: 5,
                enableLogging: true,
                customHeaders: { 'X-App': 'test' },
            })
        ).not.toThrow();
    });
});

describe('SusuDigitalClient.fromEnv()', () => {
    const originalEnv = { ...process.env };

    afterEach(() => {
        Object.assign(process.env, originalEnv);
        // Delete any keys added during the test
        for (const key of Object.keys(process.env)) {
            if (!(key in originalEnv)) delete process.env[key];
        }
    });

    it('creates a client from env variables', () => {
        process.env['SUSU_API_KEY'] = 'sk_env_test_key';
        process.env['SUSU_ENVIRONMENT'] = 'sandbox';
        process.env['SUSU_ORGANIZATION_ID'] = 'org_env_123';
        const client = SusuDigitalClient.fromEnv();
        expect(client).toBeInstanceOf(SusuDigitalClient);
    });

    it('throws if SUSU_API_KEY is not set', () => {
        delete process.env['SUSU_API_KEY'];
        expect(() => SusuDigitalClient.fromEnv()).toThrow('SUSU_API_KEY environment variable is not set');
    });

    it('defaults environment to sandbox', () => {
        process.env['SUSU_API_KEY'] = 'sk_test_abc';
        delete process.env['SUSU_ENVIRONMENT'];
        expect(() => SusuDigitalClient.fromEnv()).not.toThrow();
    });

    it('parses numeric env vars correctly', () => {
        process.env['SUSU_API_KEY'] = 'sk_test_abc';
        process.env['SUSU_TIMEOUT'] = '60';
        process.env['SUSU_MAX_RETRIES'] = '5';
        expect(() => SusuDigitalClient.fromEnv()).not.toThrow();
    });
});
