import type { HttpClient, HttpRequestConfig, HttpResponse } from '@inertiajs/core';
import { flushPromises } from '@vue/test-utils';
import { vi } from 'vitest';

interface WaitingRequest {
    config: HttpRequestConfig;
    answer: (response: HttpResponse) => void;
    fail: (error: Error) => void;
}

const waiting: WaitingRequest[] = [];

/**
 * Stands in for Inertia's HTTP client (set in setup.ts), so useHttp() requests wait for the test to answer them
 * instead of being sent. A cancelled request rejects the way an aborted fetch does.
 */
export const fakeHttp = {
    request: vi.fn(
        (config: HttpRequestConfig) =>
            new Promise<HttpResponse>((resolve, reject) => {
                const request = { config, answer: resolve, fail: reject };
                waiting.push(request);

                config.signal?.addEventListener('abort', () => {
                    if (waiting.includes(request)) {
                        waiting.splice(waiting.indexOf(request), 1);
                    }

                    reject(Object.assign(new Error('The request was aborted.'), { name: 'AbortError' }));
                });
            }),
    ),
} satisfies HttpClient;

/**
 * The URLs of the requests still waiting for an answer, oldest first, e.g. ["/places.search?search=tot"].
 */
export function waitingRequests(): string[] {
    return waiting.map((request) => request.config.url);
}

/**
 * Answers the oldest waiting request with a JSON body, then lets the page react.
 */
export async function respond(status: number, body: unknown): Promise<void> {
    oldestWaiting().answer({ status, data: JSON.stringify(body), headers: {} });
    await flushPromises();
}

/**
 * Fails the oldest waiting request as if the connection dropped, then lets the page react.
 */
export async function dropConnection(): Promise<void> {
    oldestWaiting().fail(new Error('Network Error'));
    await flushPromises();
}

function oldestWaiting(): WaitingRequest {
    const request = waiting.shift();

    if (!request) {
        throw new Error('No request is waiting for an answer.');
    }

    return request;
}

/**
 * Forgets the requests a test left unanswered.
 */
export function resetHttp(): void {
    waiting.length = 0;
}
