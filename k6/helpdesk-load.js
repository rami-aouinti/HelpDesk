import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

const baseUrl = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const listDuration = new Trend('ticket_list_duration', true);
const detailDuration = new Trend('ticket_detail_duration', true);
const createDuration = new Trend('ticket_create_duration', true);
const createTickets = (__ENV.CREATE_TICKETS || 'false').toLowerCase() === 'true';
const thinkTime = Number(__ENV.THINK_TIME_SECONDS || 5);

export const options = {
  stages: [
    { duration: '20s', target: 10 },
    { duration: '30s', target: 10 },
    { duration: '20s', target: 25 },
    { duration: '30s', target: 25 },
    { duration: '20s', target: 50 },
    { duration: '30s', target: 50 },
    { duration: '20s', target: 100 },
    { duration: '30s', target: 100 },
    { duration: '20s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    http_req_duration: ['p(95)<1200', 'p(99)<2500'],
    ticket_list_duration: ['p(95)<1000'],
    ticket_detail_duration: ['p(95)<1000'],
    ...(createTickets ? { ticket_create_duration: ['p(95)<1500'] } : {}),
  },
  userAgent: 'HelpDesk-k6-load-test/1.0',
};

export function setup() {
  let tokens = [];

  if (__ENV.K6_TOKENS_JSON) {
    try {
      tokens = JSON.parse(__ENV.K6_TOKENS_JSON);
    } catch (_) {
      throw new Error('K6_TOKENS_JSON must be a valid JSON array of Sanctum tokens.');
    }
  } else if (__ENV.API_TOKEN) {
    tokens = [__ENV.API_TOKEN];
  }

  if (!Array.isArray(tokens) || tokens.length === 0 || tokens.some((token) => typeof token !== 'string' || token.trim() === '')) {
    throw new Error('Provide API_TOKEN or K6_TOKENS_JSON with one or more Sanctum bearer tokens.');
  }

  if (thinkTime < 1 || !Number.isFinite(thinkTime)) {
    throw new Error('THINK_TIME_SECONDS must be a number greater than or equal to 1.');
  }

  return { tokens };
}

export default function (data) {
  // Prefer one distinct token per seeded user. This keeps Laravel's per-user
  // API rate limiter from turning a load test into a rate-limit test.
  const token = data.tokens[(__VU - 1) % data.tokens.length];
  const headers = {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  const listResponse = http.get(
    `${baseUrl}/api/v1/tickets?per_page=15&sort_by=created_at&sort_direction=desc`,
    { headers, tags: { endpoint: 'tickets.index' } },
  );
  listDuration.add(listResponse.timings.duration);
  const listOk = check(listResponse, {
    'ticket list returns 200': (response) => response.status === 200,
    'ticket list returns JSON': (response) => (response.headers['Content-Type'] || '').includes('application/json'),
  });

  let ticketId = null;
  if (listOk) {
    try {
      const payload = listResponse.json();
      ticketId = payload?.data?.[0]?.id ?? null;
    } catch (_) {
      // The checks above report malformed responses; continue with the next iteration.
    }
  }

  if (ticketId !== null) {
    const detailResponse = http.get(`${baseUrl}/api/v1/tickets/${ticketId}`, {
      headers,
      tags: { endpoint: 'tickets.show' },
    });
    detailDuration.add(detailResponse.timings.duration);
    check(detailResponse, {
      'ticket detail returns 200': (response) => response.status === 200,
      'ticket detail contains the requested ticket': (response) => {
        try {
          return response.json()?.data?.id === ticketId;
        } catch (_) {
          return false;
        }
      },
    });
  }

  if (createTickets && __ITER % 5 === 0) {
    const payload = JSON.stringify({
      subject: `k6 load-test ticket VU-${__VU} iteration-${__ITER}`,
      description: 'Automated performance test ticket. Safe to delete after the load test.',
      priority: 'normal',
    });
    const createResponse = http.post(`${baseUrl}/api/v1/tickets`, payload, {
      headers,
      tags: { endpoint: 'tickets.store' },
    });
    createDuration.add(createResponse.timings.duration);
    check(createResponse, {
      'ticket creation returns 201': (response) => response.status === 201,
    });
  }

  sleep(thinkTime);
}
