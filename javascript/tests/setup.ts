/**
 * Jest global setup – mock the native fetch API.
 * This runs before every test file.
 */

// Provide a global fetch stub so tests don't need to polyfill
// The actual implementation is replaced per-test via jest.fn()
global.fetch = jest.fn();

// Provide crypto.randomUUID stub
if (!global.crypto) {
    Object.defineProperty(global, 'crypto', {
        value: {
            randomUUID: () => 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                const r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            }),
        },
    });
}

// Reset all mocks before each test
beforeEach(() => {
    jest.clearAllMocks();
});
